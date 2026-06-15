<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        Schema::create('m_material', function (Blueprint $table) {
            $table->increments('id_material');
            $table->string('code', 50)->unique();
            $table->string('code_noneudr', 50)->nullable();
            $table->string('description', 200);
            $table->string('type', 20)->nullable();
            $table->decimal('yield', 6, 2)->default(100);
            $table->string('qtf_feed', 50)->nullable();
            $table->string('qtf_rundown', 50)->nullable();
            $table->unsignedInteger('id_feed')->nullable();
            $table->unsignedInteger('id_rundown')->nullable();
            $table->string('code_matl_supplier', 50)->nullable();
            $table->tinyInteger('status_packaging')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('m_material_pck', function (Blueprint $table) {
            $table->increments('id_materialpck');
            $table->unsignedInteger('id_material');
            $table->string('code', 50)->unique();
            $table->string('code_noneudr', 50)->nullable();
            $table->string('description', 200);
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('id_material')->references('id_material')->on('m_material');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_material_pck');
        Schema::dropIfExists('m_material');
    }
};
