<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialDocumentFactory extends Factory
{
    protected $model = \Modules\Transaction\Models\MaterialDocument::class;

    public function definition(): array
    {
        return [
            'id_trace_head' => \Modules\Transaction\Models\TraceHeader::factory(),
            'material_document' => $this->faker->unique()->bothify('DOC-####-??'),
            'po_so' => $this->faker->bothify('PO-####-??'),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
