<?php

namespace Database\Seeders\Fake;

use App\Models\SupplierSetup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierFakeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SupplierSetup::factory()->count(20)->create();
    }
}
