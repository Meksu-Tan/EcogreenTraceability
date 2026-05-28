<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('m_sloc', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('id_plant', 10);
            $table->string('plant_name', 100);
            $table->string('tank_number', 50);
            $table->decimal('tank_height', 10, 2);
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_sloc');
    }
};
