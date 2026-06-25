<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        // Create m_period_lock table if it doesn't exist
        if (!Schema::connection('eudr_ts')->hasTable('m_period_lock')) {
            Schema::connection('eudr_ts')->create('m_period_lock', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->date('period')->index();
                $table->enum('lock_status', ['0', '1'])->default('0')->comment('0=unlocked, 1=locked');
                $table->string('locked_by', 50)->nullable();
                $table->timestamp('locked_at')->nullable();
                $table->string('unlocked_by', 50)->nullable();
                $table->timestamp('unlocked_at')->nullable();
                $table->text('reason')->nullable();
                $table->integer('status')->default(1)->index();
                $table->string('created_by', 50)->nullable();
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
                $table->string('updated_by', 50)->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';
            });
        }

        // Add lock_status column to t_report_pspa_head if it doesn't exist
        if (Schema::connection('eudr_ts')->hasTable('t_report_pspa_head')) {
            Schema::connection('eudr_ts')->table('t_report_pspa_head', function (Blueprint $table) {
                if (!Schema::connection('eudr_ts')->hasColumn('t_report_pspa_head', 'lock_status')) {
                    $table->enum('lock_status', ['0', '1'])->default('0')->after('status');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::connection('eudr_ts')->dropIfExists('m_period_lock');
    }
};