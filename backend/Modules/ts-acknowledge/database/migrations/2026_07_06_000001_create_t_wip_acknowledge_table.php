<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('eudr_ts')->create('t_ts_acknowledge', function (Blueprint $table) {
            $table->id();
            $table->string('plant_code', 10);
            $table->date('entry_date');
            $table->string('type', 20)->default('WIP'); // WIP, TRANSFER, BLENDING
            $table->string('transaction_id', 100)->nullable(); // For Transfer/Blending
            $table->unsignedBigInteger('section_id')->nullable(); // For WIP
            $table->string('mode_value', 50)->nullable();
            $table->string('step_type', 50)->nullable(); // feed, rundown, mode
            $table->decimal('eo_dls_qty', 18, 4)->nullable();
            $table->decimal('dcs_qty', 18, 4)->nullable();
            $table->decimal('manual_qty', 18, 4)->nullable();
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();

            // Index for efficient fetching
            $table->index(['plant_code', 'entry_date', 'type']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('eudr_ts')->dropIfExists('t_ts_acknowledge');
    }
};
