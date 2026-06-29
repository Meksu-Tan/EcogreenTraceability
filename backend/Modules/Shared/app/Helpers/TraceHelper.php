<?php declare(strict_types=1);

namespace Modules\Shared\Helpers;

/**
 * SQL fragment builder for trace number field extraction.
 *
 * The system has two trace number formats in the database:
 *   11-digit (legacy): type(1) + YYMMDD(6) + plant(2)     + seq(2)
 *   14-digit (current): type(1) + YYMMDD(6) + warehouse(3) + plant(2) + seq(2)
 *
 * Queries that parse positional fields must handle both.
 *
 * NOTE: All length checks use CAST(col AS TEXT) — not CHAR.
 * In PostgreSQL, CAST(x AS CHAR) = CAST(x AS character(1)) which truncates to
 * 1 character, making CHAR_LENGTH always return 1. VARCHAR/TEXT are correct.
 */
class TraceHelper
{
    /**
     * Build a SQL condition comparing the warehouse/section field.
     *
     * 11-digit: warehouse doesn't exist; pos 8-9 = plant code (2 digits)
     * 14-digit: pos 8-10 = warehouse code (3 digits, e.g. "000", "001")
     *
     * @param string $col   Column expression, e.g. "a.to_trace_no" or "bb.trace_no"
     * @param string $op    '=' or '<>'
     * @param string $value 3-digit warehouse value to compare against, e.g. '000'
     */
    public static function warehouseCondition(string $col, string $op = '<>', string $value = '000'): string
    {
        $v2 = substr($value, 0, 2);

        return "(
            (CHAR_LENGTH(CAST({$col} AS TEXT)) >= 14 AND SUBSTRING(CAST({$col} AS TEXT),8,3) {$op} '{$value}')
            OR
            (CHAR_LENGTH(CAST({$col} AS TEXT)) < 14 AND SUBSTRING(CAST({$col} AS TEXT),8,2) {$op} '{$v2}')
        )";
    }

    /**
     * Build a SQL condition comparing the plant code field.
     *
     * 11-digit: plant at pos 8-9
     * 14-digit: plant at pos 11-12
     *
     * @param string   $col    Column expression, e.g. "a.to_trace_no"
     * @param string[] $plants 2-digit plant codes, e.g. ['01', '02']
     * @param string   $op     'IN' or 'NOT IN'
     */
    public static function plantCondition(string $col, array $plants, string $op = 'IN'): string
    {
        $list = implode(',', array_map(fn(string $p): string => "'{$p}'", $plants));

        return "(
            (CHAR_LENGTH(CAST({$col} AS TEXT)) >= 14 AND SUBSTRING(CAST({$col} AS TEXT),11,2) {$op} ({$list}))
            OR
            (CHAR_LENGTH(CAST({$col} AS TEXT)) < 14 AND SUBSTRING(CAST({$col} AS TEXT),8,2) {$op} ({$list}))
        )";
    }

    /**
     * Build a SQL condition that restricts to 14-digit trace numbers only.
     *
     * Use for WIP/Blending rundown matching where the warehouse field must exist
     * (pos 8-10) and 11-digit legacy data should be silently excluded.
     *
     * @param string $col Column expression, e.g. "a.to_trace_no"
     */
    /**
     * Build a SQL expression returning the 2-digit plant code from a trace number.
     * Returns SUBSTRING(col,11,2) for 14-digit, SUBSTRING(col,8,2) for 11-digit.
     * Use when you need the raw plant code value (not a human-readable name).
     *
     * @param string $col Column expression, e.g. "a.trace_no"
     */
    public static function plantCodeExpression(string $col): string
    {
        return "(CASE WHEN CHAR_LENGTH(CAST({$col} AS TEXT)) >= 14
                      THEN SUBSTRING(CAST({$col} AS TEXT), 11, 2)
                      ELSE SUBSTRING(CAST({$col} AS TEXT), 8, 2) END)";
    }

    public static function only14Digit(string $col): string
    {
        return "CHAR_LENGTH(CAST({$col} AS TEXT)) >= 14";
    }

    /**
     * Match storage / RM-entry records for both formats.
     *
     * 14-digit: warehouse at pos 8-10 must equal '000' (storage tank, not production line)
     * 11-digit: no warehouse field — every 11-digit record was a storage-tank entry by definition
     *
     * Use wherever the original code had SUBSTRING(col,8,3) = '000' as a
     * "this is a storage entry, not a line/production entry" filter.
     *
     * @param string $col Column expression, e.g. "trace_no" or "a.trace_no"
     */
    public static function isStorageOrLegacy(string $col): string
    {
        return "(
            (CHAR_LENGTH(CAST({$col} AS TEXT)) >= 14 AND SUBSTRING(CAST({$col} AS TEXT),8,3) = '000')
            OR
            CHAR_LENGTH(CAST({$col} AS TEXT)) < 14
        )";
    }

    /**
     * Build a SQL expression resolving a from_trace_no to a readable plant name,
     * using m_plant.code_2 when available (LEFT JOIN), with inline CASE fallback.
     *
     * 11-digit: plant at pos 8-9 → e.g. SUBSTRING(col, 8, 2)
     * 14-digit: plant at pos 11-12 → e.g. SUBSTRING(col, 11, 2)
     *
     * Produces COALESCE(p_from.code_2, CASE ... END) AS alias.
     * The caller must LEFT JOIN m_plant p_from on t_from.id_plant = p_from.code_3.
     *
     * @param string $col Column expression for the from_trace_no, e.g. "b.from_trace_no"
     */
    public static function fromPlantNameExpression(string $col): string
    {
        $plantCode = "(CASE WHEN CHAR_LENGTH(CAST({$col} AS VARCHAR)) >= 14 THEN SUBSTRING({$col}, 11, 2) ELSE SUBSTRING({$col}, 8, 2) END)";

        return "COALESCE(p_from.code_2,
            CASE {$plantCode}
                WHEN '01' THEN 'EOMB'
                WHEN '02' THEN 'EOB1'
                WHEN '03' THEN 'EOB2'
                WHEN '05' THEN 'EOB5'
                WHEN '07' THEN 'EOB3'
                ELSE ''
            END
        )";
    }

    /**
     * Build a SQL condition comparing the warehouse/section field to an arbitrary value.
     *
     * Unlike warehouseCondition() which only accepts 3-digit values, this accepts
     * any warehouse/section string (e.g. '012', '006-01') and correctly handles:
     *   14-digit: pos 8-10 = warehouse/section (3 digits)
     *   11-digit: no warehouse field → always false (legacy can't match)
     *
     * Use for next_process detection, latest trace matching, etc.
     *
     * @param string $col   Column expression, e.g. "from_trace_no"
     * @param string $value 3-digit warehouse value to compare against
     */
    public static function warehouseConditionFor(string $col, string $value): string
    {
        $v3 = str_pad(substr($value, 0, 3), 3, '0', STR_PAD_LEFT);
        return "(
            CHAR_LENGTH(CAST({$col} AS TEXT)) >= 14
            AND SUBSTRING(CAST({$col} AS TEXT), 8, 3) = '{$v3}'
        )";
    }

    /**
     * Build a SQL CASE expression that resolves a trace number's plant code
     * to a human-readable plant abbreviation, dual-format aware.
     *
     * 11-digit: plant code at pos 8–9
     * 14-digit: plant code at pos 11–12
     *
     * Returns the 2-digit plant code when no known mapping exists.
     *
     * Usage in SELECT:
     *   TraceHelper::plantNameExpression('a.trace_no') . ' AS plant_name'
     *
     * @param string $col Column expression, e.g. "a.trace_no"
     */
    public static function plantNameExpression(string $col): string
    {
        $plantCode = "(CASE WHEN CHAR_LENGTH(CAST({$col} AS TEXT)) >= 14
                           THEN SUBSTRING(CAST({$col} AS TEXT), 11, 2)
                           ELSE SUBSTRING(CAST({$col} AS TEXT), 8, 2) END)";

        return "CASE ({$plantCode})
                    WHEN '01' THEN 'EOMB'
                    WHEN '02' THEN 'EOB1'
                    WHEN '03' THEN 'EOB2'
                    WHEN '05' THEN 'EOB5'
                    WHEN '07' THEN 'EOB3'
                    ELSE {$plantCode}
                END";
    }
}
