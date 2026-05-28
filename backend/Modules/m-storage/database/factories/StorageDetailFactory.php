<?php declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StorageDetailFactory extends Factory
{
    protected $model = \Modules\Storage\Models\StorageDetail::class;

    public function definition(): array
    {
        return [
            'id_sloc' => \Modules\Storage\Models\StorageTank::factory(),
            'tf_number' => $this->faker->unique()->bothify('TF-####'),
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
