<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        $csvPath = 'C:\\Users\\michaelu\\Downloads\\Daftar Bahan Baku.csv';
        if (! file_exists($csvPath)) {
            return;
        }

        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file);

        $updates = [];
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 7) {
                continue;
            }

            $category = trim($row[2]);
            $materialName = trim($row[5]);
            $manufacturer = trim($row[6]);

            if (empty($manufacturer)) {
                continue;
            }

            $updates[$manufacturer] = [
                'category' => $category,
                'material_type' => $materialName,
            ];
        }
        fclose($file);

        foreach ($updates as $mfgName => $data) {
            DB::connection('eudr_ts')
                ->table('m_manufacturer')
                ->where('description', $mfgName)
                ->update([
                    'category' => DB::raw('COALESCE(category, '.DB::connection('eudr_ts')->getPdo()->quote($data['category']).')'),
                    'material_type' => DB::raw('COALESCE(material_type, '.DB::connection('eudr_ts')->getPdo()->quote($data['material_type']).')'),
                    'updated_by' => 'Michael Sutanto',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void {}
};
