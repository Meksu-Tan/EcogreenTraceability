<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialPackagingFactory extends Factory
{
    protected $model = \Modules\Material\Models\MaterialPackaging::class;

    public function definition(): array
    {
        return [
            'code' => 'PCK-' . $this->faker->unique()->numerify('####'),
            'code_noneudr' => $this->faker->optional()->bothify('NE-??-####'),
            'description' => $this->faker->words(2, true),
            'id_material' => \Modules\Material\Models\Material::factory(),
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
