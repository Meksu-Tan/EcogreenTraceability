<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        Schema::create('t_material_document', function (Blueprint $table) {
            $table->bigIncrements('id_matdoc');
            $table->unsignedBigInteger('id_trace_head')->index();
            $table->string('material_document', 50)->nullable()->index();
            $table->string('po_so', 50)->nullable();
            $table->integer('status')->default('1')->index();
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('id_trace_head')->references('id_trace_head')->on('t_trace_header')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_material_document');
    }
};
