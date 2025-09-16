<?php

namespace Database\Factories;

use App\Models\SupplierSetup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class SupplierFactory extends Factory
{
    protected $model = SupplierSetup::class;

    public function definition(): array
    {
        return [
            'code' => fake()->numerify('########'),
            'batch_code' => fake()->lexify(),
            'description' => fake()->company(),
            'type' => fake()->numberBetween(1, 2),
            'created_by' => 'santo'
        ];
    }
}
