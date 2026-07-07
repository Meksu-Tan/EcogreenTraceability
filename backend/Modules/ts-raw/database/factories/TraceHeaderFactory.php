<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Material\Models\Material;
use Modules\Plant\Models\Plant;
use Modules\Tank\Models\Tank;
use Modules\TsRaw\Models\BalanceHeader;
use Modules\TsRaw\Models\TraceHeader;

class TraceHeaderFactory extends Factory
{
    protected $model = TraceHeader::class;

    public function definition(): array
    {
        return [
            'id_balance_head' => BalanceHeader::factory(),
            'entry_date' => $this->faker->date(),
            'from_trace_no' => $this->faker->bothify('TR-####'),
            'to_trace_no' => $this->faker->bothify('TR-####'),
            'id_material' => Material::factory(),
            'id_sloc' => Tank::factory(),
            'id_plant' => Plant::factory(),
            'in_qty' => $this->faker->randomFloat(4, 0, 10000),
            'out_qty' => $this->faker->randomFloat(4, 0, 10000),
            'status' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
