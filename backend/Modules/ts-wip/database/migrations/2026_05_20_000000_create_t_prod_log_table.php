<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('eudr_ts')->create('t_prod_log', function (Blueprint $table) {
            $table->bigIncrements('id_prod_log');
            $table->unsignedBigInteger('id_trace_head')->nullable();
            $table->string('section', 50)->nullable();
            $table->date('entry_date')->nullable();
            $table->string('batch_no', 50)->nullable();
            $table->unsignedBigInteger('tank_id')->nullable();
            $table->text('tank_tail')->nullable();
            $table->unsignedBigInteger('id_material')->nullable();
            $table->decimal('in_qty', 18, 4)->default(0);
            $table->decimal('out_qty', 18, 4)->default(0);
            $table->decimal('yield', 18, 4)->default(0);
            $table->string('id_plant', 10)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            // Foreign key to t_trace_header
            $table->foreign('id_trace_head')->references('id_trace_head')->on('t_trace_header')->onDelete('set null');

            // Indexes
            $table->index('id_trace_head');
            $table->index('section');
            $table->index('entry_date');
            $table->index('batch_no');
            $table->index('tank_id');
            $table->index('id_material');
            $table->index('id_plant');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('eudr_ts')->dropIfExists('t_prod_log');
    }
};
