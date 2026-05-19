<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('permission_user', 'user_type')) {
            Schema::table('permission_user', function (Blueprint $table) {
                $table->string('user_type')->nullable()->after('model_type');
            });
        }

        // Sync user_type with model_type for existing records
        DB::table('permission_user')->update(['user_type' => DB::raw('model_type')]);
        
        // Fallback for NULL model_type
        DB::table('permission_user')->whereNull('user_type')->update(['user_type' => 'App\Models\User']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('permission_user', 'user_type')) {
            Schema::table('permission_user', function (Blueprint $table) {
                $table->dropColumn('user_type');
            });
        }
    }
};
