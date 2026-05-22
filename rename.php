<?php
$feDir = __DIR__ . '/frontend/src';
$beDir = __DIR__ . '/backend';

function renameFolder($from, $to) {
    if (is_dir($from)) {
        rename($from, $to);
        echo "Renamed: $from -> $to\n";
    }
}

// 1. Rename FE folders
renameFolder("$feDir/modules/transaction", "$feDir/modules/ts-raw");
renameFolder("$feDir/views/transaction", "$feDir/views/ts-raw");

// 2. Replace FE strings
function replaceInDir($dir, $searchReplacePairs, $extensions) {
    if (!is_dir($dir)) return;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
            if (in_array($ext, $extensions)) {
                $content = file_get_contents($file->getPathname());
                $newContent = str_replace(array_keys($searchReplacePairs), array_values($searchReplacePairs), $content);
                if ($content !== $newContent) {
                    file_put_contents($file->getPathname(), $newContent);
                    echo "Updated: " . $file->getPathname() . "\n";
                }
            }
        }
    }
}

$feReplacements = [
    'modules/transaction' => 'modules/ts-raw',
    'views/transaction' => 'views/ts-raw',
    'useTransactionRmEntryStore' => 'useTsRawRmEntryStore',
    'useTransactionTransferStore' => 'useTsRawTransferStore',
    'useTransactionShipmentStore' => 'useTsRawShipmentStore',
    'useTransactionPackageStore' => 'useTsRawPackageStore',
    'useTransactionWipStore' => 'useTsRawWipStore',
    'useTransactionBlendingStore' => 'useTsRawBlendingStore',
];

replaceInDir($feDir, $feReplacements, ['js', 'vue']);

// 3. Rename BE folders
$beModuleDir = "$beDir/Modules/Transaction";
$newBeModuleDir = "$beDir/Modules/TsRaw";

if (is_dir($beModuleDir)) {
    // Rename provider first
    if (file_exists("$beModuleDir/app/Providers/TransactionServiceProvider.php")) {
        rename("$beModuleDir/app/Providers/TransactionServiceProvider.php", "$beModuleDir/app/Providers/TsRawServiceProvider.php");
        echo "Renamed Provider\n";
    }
    
    // Rename RouteServiceProvider
    if (file_exists("$beModuleDir/app/Providers/RouteServiceProvider.php")) {
        // Will just replace content later
    }

    rename($beModuleDir, $newBeModuleDir);
    echo "Renamed BE Module to TsRaw\n";
}

// 4. Replace BE strings in ALL Modules and App
$beReplacements = [
    'Modules\Transaction' => 'Modules\TsRaw',
    'Modules/Transaction' => 'Modules/TsRaw',
    'transaction::' => 'tsraw::',
    'TransactionServiceProvider' => 'TsRawServiceProvider',
    '"name": "Transaction"' => '"name": "TsRaw"',
    "'name' => 'Transaction'" => "'name' => 'TsRaw'",
    "'alias' => 'transaction'" => "'alias' => 'tsraw'",
];

replaceInDir("$beDir/Modules", $beReplacements, ['php', 'json', 'yml', 'stub']);
replaceInDir("$beDir/app", $beReplacements, ['php']);
replaceInDir("$beDir/database", $beReplacements, ['php']);

// We also need to fix frontend store names
echo "Done script.\n";
