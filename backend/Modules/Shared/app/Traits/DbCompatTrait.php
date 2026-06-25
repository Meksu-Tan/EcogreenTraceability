<?php
declare(strict_types=1);
namespace Modules\Shared\Traits;

/**
 * Provides helper methods for generating PostgreSQL SQL fragments.
 * All methods return PostgreSQL syntax directly (Phase 3 migration complete).
 */
trait DbCompatTrait
{
    protected function dbGroupConcat(
        string $expr,
        string $sep = ',',
        bool $distinct = false,
        ?string $orderBy = null
    ): string {
        if ($distinct && str_starts_with(ltrim($expr), 'DISTINCT ')) {
            $expr = preg_replace('/^\s*DISTINCT\s+/', '', $expr);
        }
        $d = $distinct ? 'DISTINCT ' : '';
        $o = $orderBy ? " ORDER BY {$orderBy}" : '';
        return "STRING_AGG({$d}{$expr}, '{$sep}'{$o})";
    }

    protected function dbDateFormat(string $col, string $mysqlFmt): string
    {
        $pgFmt = str_replace(
            ['%Y', '%y', '%m', '%d', '%H', '%i', '%s'],
            ['YYYY', 'YY', 'MM', 'DD', 'HH24', 'MI', 'SS'],
            $mysqlFmt
        );
        return "TO_CHAR({$col}, '{$pgFmt}')";
    }

    protected function dbCurDate(): string
    {
        return 'CURRENT_DATE';
    }

    protected function dbExtractYear(string $col): string
    {
        return "EXTRACT(YEAR FROM {$col})";
    }

    protected function dbExtractMonth(string $col): string
    {
        return "EXTRACT(MONTH FROM {$col})";
    }

    protected function dbNow(): string
    {
        return 'CURRENT_TIMESTAMP';
    }

    protected function dbJsonContains(string $col, string $val): string
    {
        return "{$col} @> to_jsonb({$val})";
    }

    protected function dbJsonValid(string $col): string
    {
        return "{$col} IS NOT NULL";
    }

    protected function dbNumberFormat(string $col, int $decimals): string
    {
        $zeros = str_repeat('0', $decimals);
        $pgCol = preg_replace('/\bROUND\(([^,]+),(\d+)\)/', 'ROUND(CAST($1 AS numeric),$2)', $col);
        return "TO_CHAR(ROUND(CAST({$pgCol} AS numeric), {$decimals}), 'FM999999999999990.{$zeros}')";
    }

    /**
     * Generate a JSON-aware WHERE/ON clause for sloc columns that can be either
     * a scalar or a JSON array. For PostgreSQL the column is JSONB.
     *
     * PgSQL: col::text = CAST(? AS TEXT) OR (col IS NOT NULL AND col @> to_jsonb(CAST(? AS TEXT)))
     */
    protected function dbSlocJsonClause(string $col, string $param = '?'): string
    {
        return "CAST({$col} AS TEXT) = CAST({$param} AS TEXT) OR (to_jsonb({$col}) @> to_jsonb(CAST({$param} AS TEXT)))";
    }

    /**
     * Generate a JSON-aware JOIN condition between two sloc columns.
     * Both sides are TEXT/JSONB cast to TEXT for safety.
     */
    protected function dbSlocColumnClause(string $col1, string $col2): string
    {
        return "CAST({$col1} AS TEXT) = CAST({$col2} AS TEXT)";
    }

    /**
     * Returns a SQL CASE expression that maps trace_no (position 11-12 plant code)
     * and id_plant (code_3 format e.g. '1001') to a display name like 'EOMB', 'EOB-1'.
     * Centralised here so adding a new plant requires changing only this method.
     *
     * Primary path: extract 2-digit plant code from trace_no pos 11-12 (14-digit format).
     * Fallback: map code_3 value of id_plant column.
     */
    protected function buildPlantNameSql(string $traceNoCol, string $idPlantCol): string
    {
        $textType = 'TEXT';
        $cast     = "CAST({$traceNoCol} AS {$textType})";
        $castId   = "CAST({$idPlantCol} AS {$textType})";

        return "CASE
            WHEN LENGTH({$cast}) >= 14 THEN
                CASE SUBSTRING({$cast}, 11, 2)
                    WHEN '01' THEN 'EOMB'
                    WHEN '02' THEN 'EOB-1'
                    WHEN '03' THEN 'EOB-2'
                    WHEN '05' THEN 'EOB-5'
                    WHEN '07' THEN 'EOB-3'
                    ELSE COALESCE({$castId}, 'N/A')
                END
            ELSE
                CASE {$castId}
                    WHEN '1001' THEN 'EOMB'
                    WHEN '1002' THEN 'EOB-1'
                    WHEN '1003' THEN 'EOB-2'
                    WHEN '1005' THEN 'EOB-5'
                    WHEN '1007' THEN 'EOB-3'
                    ELSE COALESCE({$castId}, 'N/A')
                END
        END";
    }

    protected function isPgsql(): bool
    {
        $conn = property_exists($this, 'connection') ? ($this->connection ?? config('database.default')) : config('database.default');
        return config("database.connections.{$conn}.driver") === 'pgsql';
    }
}
