<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        if (!Schema::hasTable('m_sloc')) {
            Schema::create('m_sloc', function (Blueprint $table) {
                $table->increments('id_sloc');
                $table->string('code', 50)->nullable();
                $table->string('code_2', 50)->nullable();
                $table->string('code_3', 50)->nullable();
                $table->string('code_4', 50)->nullable();
                $table->string('id_plant', 50)->nullable();
                $table->string('plant_name', 100)->nullable();
                $table->string('id_tank', 50)->nullable();
                $table->string('description', 200);
                $table->decimal('tank_height', 10, 2)->nullable();
                $table->tinyInteger('status')->default(1);
                $table->string('created_by', 100)->nullable();
                $table->string('updated_by', 100)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('m_warehouse')) {
            Schema::create('m_warehouse', function (Blueprint $table) {
                $table->increments('id_warehouse');
                $table->string('id_batch', 50)->nullable();
                $table->string('code', 50)->nullable();
                $table->string('description', 200);
                $table->tinyInteger('status')->default(1);
                $table->string('created_by', 100)->nullable();
                $table->string('updated_by', 100)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('m_warehouse');
        Schema::dropIfExists('m_sloc');
    }
};
