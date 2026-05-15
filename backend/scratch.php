<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "users:\n";
echo json_encode(Schema::getColumnListing('users')) . "\n";

echo "m_supplier:\n";
echo json_encode(Schema::getColumnListing('m_supplier')) . "\n";

echo "m_material:\n";
echo json_encode(Schema::getColumnListing('m_material')) . "\n";

echo "m_tank:\n";
echo json_encode(Schema::getColumnListing('m_tank')) . "\n";

echo "m_warehouse:\n";
echo json_encode(Schema::getColumnListing('m_warehouse')) . "\n";
