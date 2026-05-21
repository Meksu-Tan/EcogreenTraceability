<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TraceHeaderFactory extends Factory
{
    protected $model = \Modules\Transaction\Models\TraceHeader::class;

    public function definition(): array
    {
        return [
            'id_balance_head' => \Modules\Transaction\Models\BalanceHeader::factory(),
            'entry_date' => $this->faker->date(),
            'from_trace_no' => $this->faker->bothify('TR-####'),
            'to_trace_no' => $this->faker->bothify('TR-####'),
            'id_material' => \Modules\Material\Models\Material::factory(),
            'id_sloc' => \Modules\Tank\Models\Tank::factory(),
            'id_plant' => \Modules\Plant\Models\Plant::factory(),
            'in_qty' => $this->faker->randomFloat(4, 0, 10000),
            'out_qty' => $this->faker->randomFloat(4, 0, 10000),
            'status' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
