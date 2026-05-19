<?php

try {
    config([
        'database.connections.mysql.port' => 3309,
        'database.connections.mysql.password' => '',
    ]);

    DB::reconnect();
    DB::connection()->getPdo();

    echo 'Connected to 3309 with no password';
} catch (Exception $e) {
    echo $e->getMessage();
}
