<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        Schema::create('m_plant_detail', function (Blueprint $table) {
            $table->bigIncrements('id_plant_tail');
            $table->unsignedInteger('id_plant')->index();
            $table->string('tf_number', 100)->index();
            $table->integer('status')->default('1')->index();
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            if (DB::getDriverName() === 'sqlite') {
                $table->timestamp('updated_at')->nullable();
            } else {
                $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            }
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('id_plant')->references('id_plant')->on('m_plant')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_plant_detail');
    }
};
