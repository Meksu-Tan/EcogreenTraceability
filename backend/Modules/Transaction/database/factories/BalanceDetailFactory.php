<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BalanceDetailFactory extends Factory
{
    protected $model = \Modules\Transaction\Models\BalanceDetail::class;

    public function definition(): array
    {
        return [
            'id_balance_head' => \Modules\Transaction\Models\BalanceHeader::factory(),
            'id_supplier' => \Modules\Supplier\Models\Supplier::factory(),
            'id_material' => \Modules\Material\Models\Material::factory(),
            'batch_sap' => $this->faker->bothify('SAP-####-??'),
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
