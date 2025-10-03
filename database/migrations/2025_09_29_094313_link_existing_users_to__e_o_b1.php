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
        //
        $users = DB::table('users')->pluck('id');

        foreach ($users as $userId) {
            DB::table('m_plant_user')->updateOrInsert(
                ['id_plant' => "1002", 'user_id' => $userId],
                []
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        DB::table('m_plant_user')->where('id_plant', "1002")->delete();
    }
};
