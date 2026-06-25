<?php
declare(strict_types=1);
namespace Modules\Shared\Database\Migrations;

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
        if (!Schema::connection('eudr_ts')->hasTable('log_transactions')) {
            Schema::connection('eudr_ts')->create('log_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('log_module', 50);
                $table->string('log_type', 50);
                $table->text('log_description')->nullable();
                $table->string('created_by', 100)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('eudr_ts')->dropIfExists('log_transactions');
    }
};
