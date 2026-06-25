<?php
declare(strict_types=1);
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TankDetailFactory extends Factory
{
    protected $model = \Modules\Tank\Models\TankDetail::class;

    public function definition(): array
    {
        return [
            'id_sloc' => \Modules\Tank\Models\Tank::factory(),
            'tf_number' => $this->faker->unique()->bothify('TF-####'),
            'status' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
