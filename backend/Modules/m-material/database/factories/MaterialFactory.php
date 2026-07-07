<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Material\Models\Material;

class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition(): array
    {
        return [
            'code' => 'MAT-'.$this->faker->unique()->numerify('####'),
            'code_noneudr' => $this->faker->bothify('NE-??-####'),
            'description' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['feed', 'rundown']),
            'yield' => $this->faker->optional()->randomFloat(2, 0, 100),
            'qtf_feed' => $this->faker->optional()->randomFloat(2, 0, 100),
            'qtf_rundown' => $this->faker->optional()->randomFloat(2, 0, 100),
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
