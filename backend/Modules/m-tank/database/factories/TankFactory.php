<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tank\Models\Tank;

class TankFactory extends Factory
{
    protected $model = Tank::class;

    public function definition(): array
    {
        return [
            'plant_code' => $this->faker->unique()->regexify('[A-Z0-9]{3}'),
            'plant_name' => $this->faker->city(),
            'tank_number' => $this->faker->unique()->bothify('TN-####'),
            'description' => $this->faker->words(3, true),
            'tank_height' => $this->faker->optional()->randomFloat(2, 1, 20),
            'status' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
