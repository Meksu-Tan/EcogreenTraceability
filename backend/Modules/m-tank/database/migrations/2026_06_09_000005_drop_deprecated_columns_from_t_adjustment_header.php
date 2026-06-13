<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        if (DB::connection('eudr_ts')->getDriverName() === 'sqlite') {
            return;
        }

        // CAUTION: t_adjustment_header has id_sloc (int - OLD) and no longtext id_sloc yet.
        // Dropping id_sloc removes the old int column, keeping schema ready for new longtext.
        $cols = ['id_tank', 'id_tank_tail', 'id_sloc', 'id_sloc_tail'];
        foreach ($cols as $col) {
            if (Schema::connection('eudr_ts')->hasColumn('t_adjustment_header', $col)) {
                DB::connection('eudr_ts')->statement("ALTER TABLE t_adjustment_header DROP COLUMN `{$col}`");
            }
        }
    }

    public function down(): void
    {
        // Irreversible
    }
};
