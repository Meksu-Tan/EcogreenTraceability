<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Material\Models\Material;
use Modules\Material\Models\MaterialPackaging;

class MaterialPackagingFactory extends Factory
{
    protected $model = MaterialPackaging::class;

    public function definition(): array
    {
        return [
            'code' => 'PCK-'.$this->faker->unique()->numerify('####'),
            'code_noneudr' => $this->faker->optional()->bothify('NE-??-####'),
            'description' => $this->faker->words(2, true),
            'id_material' => Material::factory(),
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
