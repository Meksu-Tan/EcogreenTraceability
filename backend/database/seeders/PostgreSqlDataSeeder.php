<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class PostgreSqlDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Dynamically configure MySQL legacy connection
        config(['database.connections.mysql_legacy' => [
            'driver' => 'mysql',
            'host' => '172.16.11.101',
            'port' => '3309',
            'database' => 'eudr_ts',
            'username' => 'eo_trace',
            'password' => 'eoTrace_admin123++',
            'charset' => 'utf8mb4',
        ]]);

        // Ordered carefully to satisfy foreign key constraints during migration
        $allTables = [
            'permissions',
            'roles',
            'permission_role',
            'users',
            'role_user',
            'permission_user',
            'm_plant',
            'm_plant_user',
            'm_manufacturer',
            'm_material',
            'm_material_flow',
            'm_material_pck',
            'm_supplier',
            'm_sloc',
            'm_warehouse',
            't_balance_header',
            't_balance_detail',
            't_balance_temporary',
            't_trace_header',
            't_trace_detail',
            't_adjustment_header',
            't_adjustment_detail',
            't_warehouse_header',
            't_warehouse_detail',
            't_shipment_header',
            't_shipment_detail',
            't_material_document',
            't_report_pspa_head',
            'log_transactions',
        ];

        echo "========================================\n";
        echo "PostgreSQL Data Migration\n";
        echo "Source: mysql_legacy (172.16.11.101:3309)\n";
        echo "Target: eudr_ts (PostgreSQL)\n";
        echo "========================================\n\n";

        // Step 0: Alter PG tables schema to drop nullable constraints that legacy MySQL doesn't have
        try {
            DB::connection('eudr_ts')->statement("ALTER TABLE t_adjustment_detail ALTER COLUMN id_sloc DROP NOT NULL");
            DB::connection('eudr_ts')->statement("ALTER TABLE t_balance_temporary ALTER COLUMN id_supplier DROP NOT NULL");
            DB::connection('eudr_ts')->statement("ALTER TABLE t_balance_temporary ALTER COLUMN id_sloc DROP NOT NULL");
            echo "✓ Altered schema constraints for legacy NULL safety\n\n";
        } catch (\Exception $e) {

            echo "⚠️  Could not alter schema constraints: {$e->getMessage()}\n\n";
        }


        // Step 1: Clear existing data using TRUNCATE CASCADE to resolve dependencies
        echo "Step 1: Clearing existing data...\n";
        foreach ($allTables as $table) {
            try {
                DB::connection('eudr_ts')->statement("TRUNCATE TABLE {$table} CASCADE");
                echo "  ✓ TRUNCATED (CASCADE): $table\n";
            } catch (\Exception $e) {
                echo "  ✗ Could not truncate $table: {$e->getMessage()}\n";
            }
        }
        echo "\n";

        $totalMigrated = 0;
        $startTime = microtime(true);

        foreach ($allTables as $table) {
            try {
                // Check if table exists in target PG
                $targetExists = DB::connection('eudr_ts')->select("
                    SELECT EXISTS (
                        SELECT FROM information_schema.tables
                        WHERE table_schema = 'public' AND table_name = ?
                    )", [$table]);

                if (!$targetExists[0]->exists) {
                    echo "⚠️  SKIP: $table (table doesn't exist in PostgreSQL)\n\n";
                    continue;
                }

                // Query from MySQL source connection
                if ($table === 'm_sloc') {
                    // m_sloc maps to joined m_sloc and m_sloc_detail in MySQL
                    $rows = DB::connection('mysql_legacy')->select("
                        SELECT 
                            td.id_sloc_tail AS id_sloc,
                            t.id_plant,
                            p.description AS plant_name,
                            td.tf_number AS id_sloc,
                            t.code,
                            t.code_2,
                            t.code_3,
                            t.code_4,
                            t.description,
                            0.00 AS tank_height,
                            td.status,
                            td.created_by,
                            td.updated_by,
                            td.created_at,
                            td.updated_at
                        FROM m_sloc_detail td
                        JOIN m_sloc t ON td.id_sloc = t.id_sloc
                        LEFT JOIN m_plant p ON t.id_plant = p.id_plant
                    ");
                    $rows = collect($rows);
                    $sourceCount = $rows->count();
                } elseif ($table === 'log_transactions') {
                    $rows = DB::connection('mysql_legacy')->table('log_transactions')
                        ->select('id_log as id', 'log_module', 'log_type', 'log_description', 'created_by', 'created_at')
                        ->get();
                    $sourceCount = $rows->count();
                } else {
                    $sourceTable = $table;
                    $sourceCount = DB::connection('mysql_legacy')->table($sourceTable)->count();
                    if ($sourceCount === 0) {
                        echo "⚠️  SKIP: $table (0 records in legacy MySQL)\n\n";
                        continue;
                    }
                    $rows = DB::connection('mysql_legacy')->table($sourceTable)->get();
                }

                if ($sourceCount === 0) {
                    echo "⚠️  SKIP: $table (0 records to migrate)\n\n";
                    continue;
                }

                // Transform for PostgreSQL compatibility
                $transformedRows = $this->transformRowsForPgsql($table, $rows);

                // Insert into PostgreSQL
                $insertCount = $this->insertInBatches($table, $transformedRows);

                echo "✓ MIGRATED: $table\n";
                echo "  Source: {$sourceCount} records\n";
                echo "  Target: {$insertCount} inserted\n";
                echo "\n";

                $totalMigrated += $insertCount;

            } catch (\Exception $e) {
                echo "❌ ERROR: $table\n";
                echo "  Message: {$e->getMessage()}\n\n";
            }
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        echo "========================================\n";
        echo "Migration Complete!\n";
        echo "Total Records Migrated: {$totalMigrated}\n";
        echo "Time Elapsed: {$elapsed}s\n";
        echo "========================================\n\n";

        // Reset sequences in PostgreSQL database
        echo "Resetting PostgreSQL sequences...\n";
        $this->resetSequences();

        // Ensure EOB5 1005 exists in m_plant and all users have access
        echo "Ensuring EOB5 (1005) master data and plant user mappings exist...\n";
        DB::connection('eudr_ts')->table('m_plant')->updateOrInsert(
            ['code_3' => '1005'],
            [
                'code' => 'P04',
                'code_2' => 'EOB-5',
                'id_sloc' => 'T000',
                'description' => 'EOB-5/1005',
                'status' => 1,
                'created_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $users = DB::connection('eudr_ts')->table('users')->pluck('id');
        foreach ($users as $userId) {
            DB::connection('eudr_ts')->table('m_plant_user')->updateOrInsert(
                ['id_plant' => '1005', 'user_id' => $userId],
                ['id_plant' => '1005', 'user_id' => $userId]
            );
        }
        echo "✓ EOB5/1005 configured.\n";
    }

    private function transformRowsForPgsql(string $table, Collection $rows): Collection
    {
        return $rows->map(function ($row) use ($table) {
            $data = (array) $row;

            // Handle JSON/JSONB columns
            $jsonColumns = [
                't_balance_header' => ['id_sloc_tail', 'id_sloc_tail'],
                't_balance_detail' => ['id_sloc_tail', 'id_sloc_tail'],
                't_trace_header' => ['id_sloc_tail', 'id_sloc_tail'],
                't_trace_detail' => ['id_sloc_tail', 'id_sloc_tail'],
                't_adjustment_header' => ['id_sloc_tail'],
                't_adjustment_detail' => ['id_sloc_tail'],
                't_warehouse_header' => ['id_sloc_tail'],
                't_warehouse_detail' => ['id_sloc_tail'],
            ];

            if (isset($jsonColumns[$table])) {
                foreach ($jsonColumns[$table] as $col) {
                    if (isset($data[$col]) && is_string($data[$col])) {
                        $decoded = json_decode($data[$col], true);
                        $data[$col] = is_array($decoded) ? json_encode($decoded) : null;
                    }
                }
            }

            // Exclude user_type for role_user as it is not present in target PG schema
            if ($table === 'role_user') {
                unset($data['user_type']);
            }

            // Map columns from MySQL id_sloc/id_sloc_tail to PostgreSQL id_sloc
            if ($table === 'm_plant') {
                if (array_key_exists('id_sloc', $data)) {
                    $data['id_sloc'] = $data['id_sloc'] !== null ? (string) $data['id_sloc'] : null;
                    unset($data['id_sloc']);
                }
            }

            if ($table === 't_balance_header') {
                if (array_key_exists('id_sloc', $data)) {
                    $data['id_sloc'] = $data['id_sloc'] !== null ? (int) $data['id_sloc'] : null;
                    unset($data['id_sloc']);
                }
                unset($data['id_sloc_tail']);
            }

            if ($table === 't_balance_temporary') {
                if (array_key_exists('id_sloc', $data)) {
                    $data['id_sloc'] = $data['id_sloc'] !== null ? (int) $data['id_sloc'] : null;
                    unset($data['id_sloc']);
                }
            }

            $jsonbSlocTables = [
                't_balance_detail',
                't_trace_header',
                't_trace_detail',
                't_warehouse_header',
                't_warehouse_detail',
            ];
            if (in_array($table, $jsonbSlocTables)) {
                $tail = isset($data['id_sloc_tail']) ? json_decode($data['id_sloc_tail'], true) : null;
                $slocArray = is_array($tail) ? $tail : (isset($data['id_sloc']) ? [(int)$data['id_sloc']] : []);
                $data['id_sloc'] = json_encode($slocArray);
                unset($data['id_sloc']);
                unset($data['id_sloc_tail']);
            }

            if ($table === 't_adjustment_header') {
                $tail = isset($data['id_sloc_tail']) ? json_decode($data['id_sloc_tail'], true) : null;
                $slocArray = is_array($tail) ? $tail : (isset($data['id_sloc']) ? [(int)$data['id_sloc']] : []);
                $data['id_sloc'] = json_encode($slocArray);
                unset($data['id_sloc']);
                unset($data['id_sloc_tail']);
            }

            // Handle specific columns with JSON structure in other tables
            if ($table === 't_adjustment_detail' && isset($data['id_sloc_tail']) && is_string($data['id_sloc_tail'])) {
                $decoded = json_decode($data['id_sloc_tail'], true);
                $data['id_sloc_tail'] = is_array($decoded) ? json_encode($decoded) : null;
            }

            // Handle specific column type mismatches
            $typeTransforms = [
                'm_material' => [
                    'id_feed' => fn($v) => is_numeric($v) ? (int)$v : null,
                    'qtf_feed' => fn($v) => empty($v) ? null : (string)$v,
                    'id_rundown' => fn($v) => is_numeric($v) ? (int)$v : null,
                    'qtf_rundown' => fn($v) => empty($v) ? null : (string)$v,
                    'yield' => fn($v) => is_numeric($v) ? (float)$v : 100.0,
                ],
                't_balance_detail' => [
                    'batch_sap' => fn($v) => $v ?: null,
                ],
                't_trace_detail' => [
                    'batch_sap' => fn($v) => $v ?: null,
                ],
            ];

            if (isset($typeTransforms[$table])) {
                foreach ($typeTransforms[$table] as $col => $transform) {
                    if (isset($data[$col])) {
                        try {
                            $data[$col] = $transform($data[$col]);
                        } catch (\Exception $e) {
                            // Suppress and keep original
                        }
                    }
                }
            }

            return $data;
        });
    }

    private function insertInBatches(string $table, Collection $rows): int
    {
        $batchSize = 1000;
        $batch = [];
        $inserted = 0;

        foreach ($rows as $row) {
            $batch[] = $row;

            if (count($batch) >= $batchSize) {
                DB::connection('eudr_ts')->table($table)->insert($batch);
                $inserted += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::connection('eudr_ts')->table($table)->insert($batch);
            $inserted += count($batch);
        }

        return $inserted;
    }

    private function resetSequences(): void
    {
        $tables = DB::connection('eudr_ts')->select("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
        ");

        foreach ($tables as $t) {
            $table = $t->table_name;
            
            // Get the primary key column name
            $pkResult = DB::connection('eudr_ts')->select("
                SELECT a.attname
                FROM   pg_index i
                JOIN   pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
                WHERE  i.indrelid = ?::regclass AND i.indisprimary
            ", [$table]);

            if (empty($pkResult)) {
                continue;
            }

            $pk = $pkResult[0]->attname;

            // Get current max value
            $maxResult = DB::connection('eudr_ts')->select("SELECT MAX({$pk}) as max_val FROM {$table}");
            $maxVal = $maxResult[0]->max_val;

            if ($maxVal === null) {
                continue;
            }

            // Check if sequence exists
            $seqResult = DB::connection('eudr_ts')->select("
                SELECT pg_get_serial_sequence(?, ?) as seq_name
            ", [$table, $pk]);

            $seqName = $seqResult[0]->seq_name ?? null;

            if ($seqName) {
                DB::connection('eudr_ts')->statement("SELECT setval(?, ?, true)", [$seqName, $maxVal]);
                echo "  Sequence reset for {$table}.{$pk} to {$maxVal}\n";
            }
        }
    }
}
