<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('log_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('log_module', 100)->nullable();
            $table->string('log_type', 50)->nullable();
            $table->text('log_description')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_transactions');
    }
};
