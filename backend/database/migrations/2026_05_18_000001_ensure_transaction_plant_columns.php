<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->plantScopedTables() as $tableName => $afterColumn) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'id_plant')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($afterColumn) {
                $column = $table->string('id_plant', 10)->nullable();

                if ($afterColumn !== null) {
                    $column->after($afterColumn);
                }

                $table->index('id_plant');
            });
        }
    }

    public function down(): void
    {
        // Intentionally no-op. This migration only ensures the multirepo can
        // run against databases that already came from the monorepo migration.
    }

    protected function plantScopedTables(): array
    {
        return [
            't_trace_header' => 'id_material',
            't_trace_detail' => 'id_material',
            't_balance_header' => 'id_tank',
            't_balance_detail' => 'id_material',
            't_balance_temporary' => 'id_material',
            't_adjustment_header' => 'id_tank',
            't_adjustment_detail' => 'id_material',
            't_warehouse_header' => 'id_section',
            't_warehouse_detail' => 'id_supplier',
            't_shipment_header' => 'id_material_fg',
            't_shipment_detail' => 'id_supplier',
        ];
    }
};
