<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BalanceHeaderFactory extends Factory
{
    protected $model = \Modules\Transaction\Models\BalanceHeader::class;

    public function definition(): array
    {
        return [
            'entry_date' => $this->faker->date(),
            'trace_no' => $this->faker->unique()->bothify('TR-####'),
            'id_material' => \Modules\Material\Models\Material::factory(),
            'id_sloc' => \Modules\Tank\Models\Tank::factory(),
            'id_plant' => \Modules\Plant\Models\Plant::factory(),
            'qty' => $this->faker->randomFloat(4, 0, 10000),
            'in_qty' => $this->faker->randomFloat(4, 0, 10000),
            'out_qty' => $this->faker->randomFloat(4, 0, 10000),
            'init_qty' => $this->faker->randomFloat(4, 0, 10000),
            'status' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
