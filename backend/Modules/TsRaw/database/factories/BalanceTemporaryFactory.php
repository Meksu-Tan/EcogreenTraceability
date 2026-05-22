<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BalanceTemporaryFactory extends Factory
{
    protected $model = \Modules\TsRaw\Models\BalanceTemporary::class;

    public function definition(): array
    {
        return [
            'entry_no' => $this->faker->unique()->bothify('ENT-####'),
            'id_supplier' => \Modules\Supplier\Models\Supplier::factory(),
            'id_material' => \Modules\Material\Models\Material::factory(),
            'id_plant' => \Modules\Plant\Models\Plant::factory(),
            'qty' => $this->faker->randomFloat(4, 0, 10000),
            'batch_sap' => $this->faker->bothify('SAP-####-??'),
            'status' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
