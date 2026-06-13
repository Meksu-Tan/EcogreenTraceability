<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        // SQLITE GUARD: DROP COLUMN is not supported in SQLite.
        // Tests use SQLite in-memory (config in phpunit.xml), so this migration
        // must be skipped there. A future migration strategy should split column
        // drops into a test-compatible approach (e.g. recreate table) if test
        // coverage of these columns is needed.

        $cols = ['id_tank', 'id_tank_tail', 'id_sloc_tail'];
        foreach ($cols as $col) {
            if (Schema::connection('eudr_ts')->hasColumn('t_balance_header', $col)) {
                DB::connection('eudr_ts')->statement("ALTER TABLE t_balance_header DROP COLUMN `{$col}`");
            }
        }
    }

    public function down(): void
    {
        // Irreversible — columns already removed
    }
};
