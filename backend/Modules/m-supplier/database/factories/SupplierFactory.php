<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Supplier;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'code' => 'SUP-'.$this->faker->unique()->numerify('####'),
            'batch_code' => $this->faker->bothify('BATCH-??-####'),
            'description' => $this->faker->company(),
            'type' => $this->faker->randomElement(['local', 'import']),
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
