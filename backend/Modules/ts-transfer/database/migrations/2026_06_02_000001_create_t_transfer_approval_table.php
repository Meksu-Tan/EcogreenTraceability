<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create t_transfer_approval table for approval workflow
        if (!Schema::connection('eudr_ts')->hasTable('t_transfer_approval')) {
            Schema::connection('eudr_ts')->create('t_transfer_approval', function (Blueprint $table) {
                $table->bigIncrements('id_approval');
                $table->string('id_balance_head', 50)->index();
                $table->string('id_trace_head', 50)->index();
                $table->string('entry_no', 30)->index();
                $table->date('entry_date')->index();
                $table->string('id_material', 20)->nullable();
                $table->string('material_name', 100)->nullable();
                $table->decimal('qty', 18, 4)->default(0);
                $table->string('source_sloc', 50)->nullable();
                $table->string('dest_sloc', 50)->nullable();
                $table->integer('id_plant')->default(0);

                // Approval status: DRAFT, PENDING, APPROVED, REJECTED, CANCELLED
                $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'])->default('DRAFT')->index();

                // Approval workflow fields
                $table->string('submitted_by', 50)->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->string('approved_by', 50)->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->string('rejected_by', 50)->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();

                // Additional fields
                $table->text('notes')->nullable();
                $table->string('created_by', 50)->nullable();
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
                $table->string('updated_by', 50)->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';
            });
        }

        // Add approval_status column to t_balance_header if not exists
        if (Schema::connection('eudr_ts')->hasTable('t_balance_header')) {
            Schema::connection('eudr_ts')->table('t_balance_header', function (Blueprint $table) {
                if (!Schema::connection('eudr_ts')->hasColumn('t_balance_header', 'approval_status')) {
                    $table->enum('approval_status', ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'])
                          ->default('APPROVED')
                          ->after('status');
                }
                if (!Schema::connection('eudr_ts')->hasColumn('t_balance_header', 'approved_by')) {
                    $table->string('approved_by', 50)->nullable()->after('approval_status');
                }
                if (!Schema::connection('eudr_ts')->hasColumn('t_balance_header', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::connection('eudr_ts')->dropIfExists('t_transfer_approval');
    }
};