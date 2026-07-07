<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tank\Models\Tank;

class TankSeeder extends Seeder
{
    public function run(): void
    {
        // Skip tank seeder - requires manual data entry due to complex schema
        // Tanks need id_sloc, code, and other specific fields
        $this->command->info('Tank seeder skipped - requires manual data entry');
    }
}
