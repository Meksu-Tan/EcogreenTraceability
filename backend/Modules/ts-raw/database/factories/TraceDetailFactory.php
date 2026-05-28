<?php declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TraceDetailFactory extends Factory
{
    protected $model = \Modules\TsRaw\Models\TraceDetail::class;

    public function definition(): array
    {
        return [
            'id_trace_head' => \Modules\TsRaw\Models\TraceHeader::factory(),
            'id_supplier' => \Modules\Supplier\Models\Supplier::factory(),
            'id_material' => \Modules\Material\Models\Material::factory(),
            'batch_sap' => $this->faker->bothify('SAP-####-??'),
            'in_qty' => $this->faker->randomFloat(4, 0, 10000),
            'out_qty' => $this->faker->randomFloat(4, 0, 10000),
            'status' => 1,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
