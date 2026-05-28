<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $request = Illuminate\Http\Request::create('/api/v1/transactions/rm-entries/feed-log', 'GET', ['id_plant' => 0]);
    $response = $app->handle($request);
    $html = $response->getContent();
    
    // search for body or heading to find error details
    if (preg_match('/<div class="exception-message">(.+?)<\/div>/s', $html, $matches)) {
        echo "Exception Message: " . trim($matches[1]) . "\n";
    } elseif (preg_match('/<h1>(.+?)<\/h1>/s', $html, $matches)) {
        echo "Heading: " . trim($matches[1]) . "\n";
    } else {
        echo "Could not find specific pattern. First 1000 chars:\n" . substr($html, 0, 1000) . "\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
