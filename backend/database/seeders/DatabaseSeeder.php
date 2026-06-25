<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Database\Seeders\RolePermissionSeeder::class,
            // \Database\Seeders\TankSeeder::class, // Skipped - requires manual data entry
            \Database\Seeders\MaterialSeeder::class,
            \Database\Seeders\SupplierSeeder::class,
            \Database\Seeders\PlantSeeder::class,
            // \Database\Seeders\StorageSeeder::class,
        ]);
    }
}
