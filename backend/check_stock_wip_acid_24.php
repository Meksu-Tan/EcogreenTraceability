<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Modules\Material\Services\Contracts\MaterialServiceInterface;
use Modules\TsStock\Repositories\StockRepository;

$repo = app(StockRepository::class);
$materialService = app(MaterialServiceInterface::class);

$materialId = 56; // ACID 24
$plantId = 1; // EOB-1

echo "Plant ID: 1\n";
echo "Material ID: 56\n";

echo "\n--- 1. TRANSFER MODULE DATA (fetchBalance) ---\n";
$balance = $materialService->fetchBalance($plantId, $materialId);
print_r($balance);

echo "\n--- 2. STOCK ON HAND DATA (getStockList / Summary) ---\n";
$filters = [
    'id_plant' => $plantId,
    'material_id' => 'WIP|'.$materialId,
    'date_from' => '2025-11-01',
    'date_to' => '2026-07-31',
    'mode' => 'NORMAL',
    'report_type' => 'summary',
];

$stockSummary = $repo->getStockList($filters);
print_r($stockSummary);

echo "\n--- 3. STOCK ON HAND DATA (getStockList / Detail) ---\n";
$filters['report_type'] = 'detail';
$stockData = $repo->getStockList($filters);
if (! empty($stockData)) {
    $last = end($stockData);
    print_r($last);
} else {
    echo "No stock detail found.\n";
}
