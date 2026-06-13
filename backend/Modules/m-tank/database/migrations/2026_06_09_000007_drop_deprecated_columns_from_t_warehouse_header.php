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

        $cols = ['id_tank', 'id_tank_tail'];
        foreach ($cols as $col) {
            if (Schema::connection('eudr_ts')->hasColumn('t_warehouse_header', $col)) {
                DB::connection('eudr_ts')->statement("ALTER TABLE t_warehouse_header DROP COLUMN `{$col}`");
            }
        }
    }

    public function down(): void
    {
        // Irreversible
    }
};
