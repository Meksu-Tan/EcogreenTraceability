<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Material\Models\Material;
use Modules\Plant\Models\Plant;
use Modules\Tank\Models\Tank;
use Modules\TsRaw\Models\BalanceHeader;

class BalanceHeaderFactory extends Factory
{
    protected $model = BalanceHeader::class;

    public function definition(): array
    {
        return [
            'entry_date' => $this->faker->date(),
            'trace_no' => $this->faker->unique()->bothify('TR-####'),
            'id_material' => Material::factory(),
            'id_sloc' => Tank::factory(),
            'id_plant' => Plant::factory(),
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
