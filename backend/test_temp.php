<?php
$log = file_get_contents('storage/logs/laravel.log');
$lines = explode("\n", $log);
$lastLines = array_slice($lines, -100);
foreach ($lastLines as $line) {
    if (strpos($line, 'local.ERROR') !== false || strpos($line, 'exception') !== false || strpos($line, 'Exception') !== false) {
        echo $line . "\n";
    }
}
