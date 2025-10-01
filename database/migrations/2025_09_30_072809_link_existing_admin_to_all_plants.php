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
        // Get all Plants
        $plants = DB::table('m_plant')->pluck('id_plant');

        // Get all Admin & Super Admin users
        $admins = DB::table('users as u')
            ->join('role_user as ru', 'u.id', '=', 'ru.user_id')
            ->whereIn('ru.role_id', [1, 2]) // Super Admin and Admin
            ->pluck('u.id');

        foreach ($admins as $adminId) {
            foreach ($plants as $plantId) {
                DB::table('m_plant_user')->updateOrInsert(
                    ['user_id' => $adminId, 'id_plant' => $plantId],
                    []
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('m_plant_user')->whereIn('user_id', function ($query) {
            $query->select('user_id')
                ->from('role_user')
                ->whereIn('role_id', [1, 2]);
        })->delete();
    }
};
