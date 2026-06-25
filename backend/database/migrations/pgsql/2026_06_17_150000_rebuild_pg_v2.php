<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        $conn = DB::connection($this->connection);

        // PostgreSQL-only: nuke and recreate schema + tables.
        // On SQLite (tests), skip — first migration already created tables.
        if ($conn->getDriverName() !== 'pgsql') {
            return;
        }

        $conn->statement('DROP SCHEMA public CASCADE');
        $conn->statement('CREATE SCHEMA public');
        $conn->statement('GRANT ALL ON SCHEMA public TO eudr_app');
        $conn->statement('GRANT ALL ON SCHEMA public TO public');

        // m_manufacturer
        Schema::connection($this->connection)->create('m_manufacturer', function (Blueprint $table) {
            $table->increments('id_manufacturer');
            $table->string('code', 50)->unique();
            $table->string('code_noneudr', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('type', 50)->nullable();
            $table->string('batch_code', 100)->nullable();
            $table->integer('sloc')->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // m_material
        Schema::connection($this->connection)->create('m_material', function (Blueprint $table) {
            $table->bigIncrements('id_material');
            $table->string('code', 50);  // Allow duplicates (MySQL has 5 duplicate code values)
            $table->string('code_noneudr', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('code_matl_supplier', 50)->nullable();
            $table->string('type', 50)->nullable();
            $table->double('yield')->default(100);
            $table->string('qtf_feed', 50)->nullable();
            $table->string('qtf_rundown', 50)->nullable();
            $table->unsignedBigInteger('id_rundown')->nullable();
            $table->unsignedBigInteger('id_feed')->nullable();
            $table->smallInteger('status_packaging')->default(0);
            $table->smallInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // m_material_pck — added batch_prefix to match MySQL
        Schema::connection($this->connection)->create('m_material_pck', function (Blueprint $table) {
            $table->bigIncrements('id_materialpck');
            $table->unsignedBigInteger('id_material')->nullable();
            $table->string('code', 20)->nullable();
            $table->string('code_noneudr', 20)->nullable();
            $table->string('description', 500)->nullable();
            $table->string('batch_prefix', 10)->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // m_supplier — completely different from old migration
        Schema::connection($this->connection)->create('m_supplier', function (Blueprint $table) {
            $table->bigIncrements('id_supplier');
            $table->string('code', 13)->nullable();
            $table->string('batch_code', 13)->nullable();
            $table->string('description', 100)->nullable();
            $table->string('type', 20)->nullable();
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // m_sloc — NO auto_increment (MySQL id_sloc is int with no auto_increment)
        Schema::connection($this->connection)->create('m_sloc', function (Blueprint $table) {
            $table->integer('id_sloc')->primary();
            $table->string('id_plant', 10)->nullable();
            $table->string('plant_name', 100)->nullable();
            $table->string('sloc_code', 50)->nullable();
            $table->string('code', 50)->nullable();
            $table->string('code_2', 50)->nullable();
            $table->string('code_3', 50)->nullable();
            $table->string('code_4', 50)->nullable();
            $table->string('description', 50)->nullable();
            $table->decimal('tank_height', 10, 2)->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // m_warehouse — id_batch is varchar(20), no id_plant or capacity
        Schema::connection($this->connection)->create('m_warehouse', function (Blueprint $table) {
            $table->bigIncrements('id_warehouse');
            $table->string('id_batch', 20)->nullable();
            $table->string('code', 20)->nullable();
            $table->string('description', 100)->nullable();
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // t_balance_header — trace_no is bigint, id_sloc is longtext JSON, id_plant is varchar(10)
        Schema::connection($this->connection)->create('t_balance_header', function (Blueprint $table) {
            $table->bigIncrements('id_balance_head');
            $table->date('entry_date')->nullable();
            $table->string('trace_no', 20)->nullable();
            $table->unsignedBigInteger('id_material')->nullable();
            $table->text('id_sloc')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->double('qty')->default(0);
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->double('init_qty')->default(0);
            $table->integer('status')->default(1);
            $table->string('approval_status', 20)->default('APPROVED');
            $table->string('approved_by', 50)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // t_balance_detail — id_sloc is longtext JSON, has id_supplier/id_manufacturer
        Schema::connection($this->connection)->create('t_balance_detail', function (Blueprint $table) {
            $table->bigIncrements('id_balance_tail');
            $table->unsignedBigInteger('id_balance_head')->nullable();
            $table->unsignedBigInteger('id_supplier')->nullable();
            $table->unsignedBigInteger('id_material')->nullable();
            $table->unsignedInteger('id_manufacturer')->nullable();
            $table->text('id_sloc')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->string('batch_sap', 20)->nullable();
            $table->double('qty')->default(0);
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->double('init_qty')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // t_trace_header — id_sloc is longtext JSON, no id_sloc separate
        Schema::connection($this->connection)->create('t_trace_header', function (Blueprint $table) {
            $table->bigIncrements('id_trace_head');
            $table->date('entry_date')->nullable();
            $table->string('from_trace_no', 20)->nullable();
            $table->string('to_trace_no', 20)->nullable();
            $table->unsignedBigInteger('id_balance_head')->nullable();
            $table->unsignedBigInteger('id_material')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->text('id_sloc')->nullable();
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->double('last_qtf')->default(0);
            $table->double('curr_qtf')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // t_trace_detail
        Schema::connection($this->connection)->create('t_trace_detail', function (Blueprint $table) {
            $table->bigIncrements('id_trace_tail');
            $table->unsignedBigInteger('id_trace_head')->nullable();
            $table->unsignedBigInteger('id_balance_tail')->nullable();
            $table->unsignedBigInteger('id_supplier')->nullable();
            $table->unsignedBigInteger('id_material')->nullable();
            $table->unsignedInteger('id_manufacturer')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->text('id_sloc')->nullable();
            $table->string('batch_sap', 20)->nullable();
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // t_adjustment_header — adjust_no is bigint (not string), before_adjust/after_adjust
        Schema::connection($this->connection)->create('t_adjustment_header', function (Blueprint $table) {
            $table->bigIncrements('id_adjust_head');
            $table->date('entry_date')->nullable();
            $table->string('adjust_no', 20)->nullable();
            $table->unsignedBigInteger('id_balance_head')->nullable();
            $table->unsignedBigInteger('id_material')->nullable();
            $table->text('id_sloc')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->double('before_adjust')->default(0);
            $table->double('after_adjust')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // t_warehouse_header — id_material_feed/fg, no id_warehouse/id_sloc_tail
        Schema::connection($this->connection)->create('t_warehouse_header', function (Blueprint $table) {
            $table->bigIncrements('id_whx_head');
            $table->date('entry_date')->nullable();
            $table->string('from_trace_no', 20)->nullable();
            $table->string('trace_no', 20)->nullable();
            $table->unsignedBigInteger('id_material_feed')->nullable();
            $table->unsignedBigInteger('id_material_fg')->nullable();
            $table->text('id_sloc')->nullable();
            $table->unsignedBigInteger('id_section')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->string('batch_no', 20)->nullable();
            $table->string('po_no', 20)->nullable();
            $table->double('qty')->nullable()->default(0);
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->double('init_qty')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // t_warehouse_detail
        Schema::connection($this->connection)->create('t_warehouse_detail', function (Blueprint $table) {
            $table->bigIncrements('id_whx_tail');
            $table->unsignedBigInteger('id_whx_head')->nullable();
            $table->unsignedBigInteger('id_material_feed')->nullable();
            $table->unsignedBigInteger('id_material_fg')->nullable();
            $table->text('id_sloc')->nullable();
            $table->unsignedBigInteger('id_supplier')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->string('batch_sap', 20)->nullable();
            $table->double('qty')->nullable()->default(0);
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->double('init_qty')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // t_shipment_header — from_trace_no/trace_no are bigint, id_material_fg only
        Schema::connection($this->connection)->create('t_shipment_header', function (Blueprint $table) {
            $table->bigIncrements('id_ship_head');
            $table->date('entry_date')->nullable();
            $table->string('from_trace_no', 20)->nullable();
            $table->string('trace_no', 20)->nullable();
            $table->string('so_no', 20)->nullable();
            $table->unsignedBigInteger('id_material_fg')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->double('qty')->default(0);
            $table->string('doc_url', 50)->nullable();
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // t_shipment_detail
        Schema::connection($this->connection)->create('t_shipment_detail', function (Blueprint $table) {
            $table->bigIncrements('id_ship_tail');
            $table->unsignedBigInteger('id_ship_head')->nullable();
            $table->unsignedBigInteger('id_material_fg')->nullable();
            $table->unsignedBigInteger('id_supplier')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->string('batch_sap', 20)->nullable();
            $table->double('qty')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // t_material_document — completely different from old migration
        Schema::connection($this->connection)->create('t_material_document', function (Blueprint $table) {
            $table->bigIncrements('id_matdoc');
            $table->unsignedBigInteger('id_trace_head')->nullable();
            $table->string('material_document', 50)->nullable();
            $table->string('po_so', 50)->nullable();
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // t_report_pspa_head — period/batch_sap/adjust_status/lock_status
        Schema::connection($this->connection)->create('t_report_pspa_head', function (Blueprint $table) {
            $table->bigIncrements('id_report_head');
            $table->date('period')->nullable();
            $table->string('batch_sap', 50)->nullable();
            $table->integer('adjust_status')->default(0);
            $table->integer('lock_status')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        $conn = DB::connection($this->connection);

        if ($conn->getDriverName() !== 'pgsql') {
            return;
        }

        $tables = [
            't_material_document', 't_shipment_detail', 't_shipment_header',
            't_warehouse_detail', 't_warehouse_header',
            't_adjustment_detail', 't_adjustment_header',
            't_trace_detail', 't_trace_header',
            't_balance_detail', 't_balance_header',
            't_report_pspa_head',
            'm_warehouse', 'm_sloc', 'm_supplier',
            'm_material_pck', 'm_material', 'm_manufacturer',
        ];

        foreach ($tables as $table) {
            Schema::connection($this->connection)->dropIfExists($table);
        }
    }
};
