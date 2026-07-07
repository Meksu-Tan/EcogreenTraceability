<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Material\Models\Material;
use Modules\Supplier\Models\Supplier;
use Modules\TsRaw\Models\BalanceDetail;
use Modules\TsRaw\Models\BalanceHeader;

class BalanceDetailFactory extends Factory
{
    protected $model = BalanceDetail::class;

    public function definition(): array
    {
        return [
            'id_balance_head' => BalanceHeader::factory(),
            'id_supplier' => Supplier::factory(),
            'id_material' => Material::factory(),
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
