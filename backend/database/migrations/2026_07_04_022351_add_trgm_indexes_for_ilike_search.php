<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

            DB::statement('CREATE INDEX IF NOT EXISTS idx_m_material_description_trgm ON m_material USING gin (description gin_trgm_ops)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_m_material_pck_description_trgm ON m_material_pck USING gin (description gin_trgm_ops)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_m_sloc_description_trgm ON m_sloc USING gin (description gin_trgm_ops)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_m_sloc_code_2_trgm ON m_sloc USING gin (code_2 gin_trgm_ops)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_m_sloc_code_3_trgm ON m_sloc USING gin (code_3 gin_trgm_ops)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_t_shipment_header_so_no_trgm ON t_shipment_header USING gin (so_no gin_trgm_ops)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_m_material_description_trgm');
            DB::statement('DROP INDEX IF EXISTS idx_m_material_pck_description_trgm');
            DB::statement('DROP INDEX IF EXISTS idx_m_sloc_description_trgm');
            DB::statement('DROP INDEX IF EXISTS idx_m_sloc_code_2_trgm');
            DB::statement('DROP INDEX IF EXISTS idx_m_sloc_code_3_trgm');
            DB::statement('DROP INDEX IF EXISTS idx_t_shipment_header_so_no_trgm');
        }
    }
};
