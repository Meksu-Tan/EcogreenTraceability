<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Modules\TsStock\Repositories\StockRepository;

$repo = app(StockRepository::class);

$materialId = 56; // ACID 24
$plantId = 1; // EOB-1

echo "Plant ID: 1\n";
echo "Material ID: 56\n";

echo "\n--- STOCK ON HAND DATA (getStockList / Detail) ---\n";
$filters = [
    'id_plant' => $plantId,
    'material_id' => 'WIP|'.$materialId,
    'date_from' => '2025-11-01',
    'date_to' => '2026-07-31',
    'mode' => 'NORMAL',
    'report_type' => 'detail',
];

$stockData = $repo->getStockList($filters);
// print ALL entry dates
$dates = [];
foreach ($stockData as $row) {
    if (isset($row->entry_date)) {
        $dates[] = $row->entry_date.' -> in: '.$row->in.', out: '.$row->out.', desc: '.$row->description;
    }
}
print_r($dates);
