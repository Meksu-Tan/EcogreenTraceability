<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Material\Models\Material;
use Modules\Plant\Models\Plant;
use Modules\Supplier\Models\Supplier;
use Modules\TsRaw\Models\BalanceTemporary;

class BalanceTemporaryFactory extends Factory
{
    protected $model = BalanceTemporary::class;

    public function definition(): array
    {
        return [
            'entry_no' => $this->faker->unique()->bothify('ENT-####'),
            'id_supplier' => Supplier::factory(),
            'id_material' => Material::factory(),
            'id_plant' => Plant::factory(),
            'qty' => $this->faker->randomFloat(4, 0, 10000),
            'batch_sap' => $this->faker->bothify('SAP-####-??'),
            'status' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
