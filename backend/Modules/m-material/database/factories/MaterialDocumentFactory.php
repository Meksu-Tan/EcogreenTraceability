<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\TsRaw\Models\MaterialDocument;
use Modules\TsRaw\Models\TraceHeader;

class MaterialDocumentFactory extends Factory
{
    protected $model = MaterialDocument::class;

    public function definition(): array
    {
        return [
            'id_trace_head' => TraceHeader::factory(),
            'material_document' => $this->faker->unique()->bothify('DOC-####-??'),
            'po_so' => $this->faker->bothify('PO-####-??'),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
