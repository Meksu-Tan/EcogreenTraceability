<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        $conn = DB::connection($this->connection);
        $driver = $conn->getDriverName();

        // PostgreSQL and SQLite use standard CREATE INDEX / DROP INDEX syntax
        // MySQL-specific backtick + ALTER TABLE ADD INDEX syntax is skipped for non-mysql

        if ($driver === 'mysql') {
            // ---- t_trace_header -------------------------------------------------
            try {
                $conn->statement('DROP INDEX `t_trace_header_entry_date_index` ON `t_trace_header`');
            } catch (Exception $e) {
            }
            try {
                $conn->statement('DROP INDEX `t_trace_header_id_material_index` ON `t_trace_header`');
            } catch (Exception $e) {
            }
            try {
                $conn->statement('DROP INDEX `t_trace_header_id_sloc_index` ON `t_trace_header`');
            } catch (Exception $e) {
            }

            $conn->statement('ALTER TABLE `t_trace_header` ADD INDEX `t_trace_header_entry_date_index`(`entry_date`)');
            $conn->statement('ALTER TABLE `t_trace_header` ADD INDEX `t_trace_header_id_material_index`(`id_material`)');
            $conn->statement('ALTER TABLE `t_trace_header` ADD INDEX `t_trace_header_id_sloc_index`(`id_sloc`(191))');

            // ---- t_balance_header -----------------------------------------------
            try {
                $conn->statement('DROP INDEX `t_balance_header_composite_index` ON `t_balance_header`');
            } catch (Exception $e) {
            }
            $conn->statement('ALTER TABLE `t_balance_header` ADD INDEX `t_balance_header_composite_index`(`id_material`,`id_sloc`(191),`entry_date`)');
        } else {
            // Standard SQL: CREATE INDEX IF NOT EXISTS / DROP INDEX IF EXISTS
            $indexes = [
                't_trace_header' => [
                    't_trace_header_entry_date_index' => ['entry_date'],
                    't_trace_header_id_material_index' => ['id_material'],
                    't_trace_header_id_sloc_index' => ['id_sloc'],
                ],
            ];

            // t_balance_header composite index — skip if id_sloc was dropped (m-tank migration)
            if (Schema::connection($this->connection)->hasColumn('t_balance_header', 'id_sloc')) {
                $indexes['t_balance_header'] = [
                    't_balance_header_composite_index' => ['id_material', 'id_sloc', 'entry_date'],
                ];
            }

            foreach ($indexes as $table => $idxDefs) {
                if (! Schema::connection($this->connection)->hasTable($table)) {
                    continue;
                }
                foreach ($idxDefs as $idxName => $columns) {
                    $cols = implode(', ', $columns);
                    $conn->statement("DROP INDEX IF EXISTS {$idxName}");
                    $conn->statement("CREATE INDEX {$idxName} ON {$table}({$cols})");
                }
            }
        }

        // ---- m_sloc (works on all drivers) -------------------------------------
        try {
            Schema::connection($this->connection)->table('m_sloc', function (Blueprint $table) {
                $table->index(['id_plant']);
            });
        } catch (Exception $e) {
        }
    }

    public function down(): void
    {
        $driver = DB::connection($this->connection)->getDriverName();

        if ($driver === 'mysql') {
            try {
                DB::connection($this->connection)->statement('ALTER TABLE `t_trace_header` DROP INDEX `t_trace_header_entry_date_index`');
            } catch (Exception $e) {
            }
            try {
                DB::connection($this->connection)->statement('ALTER TABLE `t_trace_header` DROP INDEX `t_trace_header_id_material_index`');
            } catch (Exception $e) {
            }
            try {
                DB::connection($this->connection)->statement('ALTER TABLE `t_trace_header` DROP INDEX `t_trace_header_id_sloc_index`');
            } catch (Exception $e) {
            }
            try {
                DB::connection($this->connection)->statement('ALTER TABLE `t_balance_header` DROP INDEX `t_balance_header_composite_index`');
            } catch (Exception $e) {
            }
        } else {
            $indexes = ['t_trace_header_entry_date_index', 't_trace_header_id_material_index', 't_trace_header_id_sloc_index', 't_balance_header_composite_index'];
            foreach ($indexes as $idx) {
                try {
                    DB::connection($this->connection)->statement("DROP INDEX IF EXISTS {$idx}");
                } catch (Exception $e) {
                }
            }
        }

        try {
            Schema::connection($this->connection)->table('m_sloc', function (Blueprint $table) {
                $table->dropIndex(['id_plant']);
            });
        } catch (Exception $e) {
        }
    }
};
