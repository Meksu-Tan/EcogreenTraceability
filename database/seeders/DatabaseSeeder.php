<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        $this->call(LaratrustSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(TankSeeder::class);
        $this->call(MaterialSeeder::class);
        $this->call(SupplierSeeder::class);
        $this->call(MaterialFlowSeeder::class);
        $this->call(WarehouseSeeder::class);
        $this->call(TankDetailSeeder::class);
    }
}
