<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Check current type and fix if JSONB
        $result = DB::selectOne("
            SELECT data_type FROM information_schema.columns
            WHERE table_name = 't_balance_header' AND column_name = 'id_sloc'
        ");

        if ($result && $result->data_type === 'jsonb') {
            // Add temporary integer column
            DB::statement('ALTER TABLE t_balance_header ADD COLUMN id_sloc_temp integer');

            // Migrate data: extract first value and cast
            DB::statement('
                UPDATE t_balance_header SET id_sloc_temp =
                CASE
                    WHEN id_sloc IS NULL THEN NULL
                    WHEN id_sloc::text LIKE \'[%\' THEN (id_sloc->>0)::text::integer
                    ELSE (id_sloc::text)::integer
                END
            ');

            // Drop old JSONB column and rename temp integer column
            DB::statement('ALTER TABLE t_balance_header DROP COLUMN id_sloc CASCADE');
            DB::statement('ALTER TABLE t_balance_header RENAME COLUMN id_sloc_temp TO id_sloc');
        }
    }

    public function down(): void
    {
        // Rollback tidak disarankan - keep integer type
    }
};
