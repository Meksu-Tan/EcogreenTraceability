<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $pivotPermission = config('permission.column_names')['permission_pivot_key'] ?? 'permission_id';
        $pivotRole = config('permission.column_names')['role_pivot_key'] ?? 'role_id';

        $permissionUserTable = $tableNames['model_has_permissions'] ?? 'model_has_permissions';

        if (Schema::hasTable($permissionUserTable) && !Schema::hasColumn($permissionUserTable, 'user_type')) {
            Schema::table($permissionUserTable, function (Blueprint $table) {
                $table->string('user_type')->nullable()->after('model_type');
            });

            DB::table($permissionUserTable)->update(['user_type' => DB::raw('model_type')]);
            DB::table($permissionUserTable)->whereNull('user_type')->update(['user_type' => 'App\Models\User']);
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $permissionUserTable = $tableNames['model_has_permissions'] ?? 'model_has_permissions';

        if (Schema::hasColumn($permissionUserTable, 'user_type')) {
            Schema::table($permissionUserTable, function (Blueprint $table) {
                $table->dropColumn('user_type');
            });
        }
    }
};
