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
        Schema::create('m_sloc_detail', function (Blueprint $table) {
            $table->id('id_sloc_tail');
            $table->unsignedBigInteger('id_sloc')->nullable();
            $table->string('tf_number')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_sloc_detail');
    }
};
