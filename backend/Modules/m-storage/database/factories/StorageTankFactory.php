<?php declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StorageTankFactory extends Factory
{
    protected $model = \Modules\Storage\Models\StorageTank::class;

    public function definition(): array
    {
        return [
            'code_2' => $this->faker->unique()->regexify('[A-Z0-9]{2}'),
            'code_3' => $this->faker->unique()->regexify('[A-Z0-9]{3}'),
            'code_4' => $this->faker->unique()->regexify('[A-Z0-9]{4}'),
            'id_plant' => \Modules\Plant\Models\Plant::factory(),
            'description' => $this->faker->words(2, true),
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
