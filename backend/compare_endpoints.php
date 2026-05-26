<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\TsRaw\Repositories\RmEntryRepository;
use Modules\TsTransfer\Repositories\TransferRepository;

$rmRepo = new RmEntryRepository();
$rmList = $rmRepo->getRmList(1002);
echo "Fields in getRmList:\n";
if (!empty($rmList)) {
    print_r(array_keys((array)$rmList[0]));
    echo "Sample data:\n";
    print_r($rmList[0]);
} else {
    echo "No data in getRmList\n";
}

echo "\n=============================================\n";

$trfRepo = new TransferRepository();
$trfList = $trfRepo->getStorageLog(1002);
echo "Fields in getStorageLog:\n";
if (!empty($trfList)) {
    print_r(array_keys((array)$trfList[0]));
    echo "Sample data:\n";
    print_r($trfList[0]);
} else {
    echo "No data in getStorageLog\n";
}
