<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlantFactory extends Factory
{
    protected $model = \Modules\Plant\Models\Plant::class;

    public function definition(): array
    {
        return [
            'code_2' => $this->faker->unique()->regexify('[A-Z0-9]{2}'),
            'code_3' => $this->faker->unique()->regexify('[A-Z0-9]{3}'),
            'description' => $this->faker->city() . ' Plant',
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
