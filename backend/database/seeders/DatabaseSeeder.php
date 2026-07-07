<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            // \Database\Seeders\TankSeeder::class, // Skipped - requires manual data entry
            MaterialSeeder::class,
            SupplierSeeder::class,
            PlantSeeder::class,
            // \Database\Seeders\StorageSeeder::class,
            QuantifierSeeder::class,
        ]);
    }
}
