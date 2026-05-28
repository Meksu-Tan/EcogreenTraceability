<?php declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = \Modules\Storage\Models\Warehouse::class;

    public function definition(): array
    {
        return [
            'code' => 'WH-' . $this->faker->unique()->numerify('####'),
            'description' => $this->faker->words(2, true) . ' Warehouse',
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
