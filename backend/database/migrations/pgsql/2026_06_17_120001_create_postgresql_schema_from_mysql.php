<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'eudr_ts_pg';

    public function up(): void
    {
        echo "Creating PostgreSQL schema to match MySQL eudr_ts...\n";

        // Master Data Tables
        Schema::create('m_plant', function (Blueprint $table) {
            $table->integer('id_plant')->primary();
            $table->string('code', 50);
            $table->string('code_2', 50)->nullable();
            $table->string('code_3', 50)->nullable();
            $table->integer('id_sloc')->nullable();
            $table->text('description')->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('m_manufacturer', function (Blueprint $table) {
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
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('m_material', function (Blueprint $table) {
            $table->bigIncrements('id_material');
            $table->string('code', 50)->unique();
            $table->string('code_noneudr', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('code_matl_supplier', 50)->nullable();
            $table->string('type', 50)->nullable();
            $table->decimal('yield', 6, 2)->default(100);
            $table->string('qtf_feed', 50)->nullable();
            $table->string('qtf_rundown', 50)->nullable();
            $table->unsignedBigInteger('id_feed')->nullable();
            $table->unsignedBigInteger('id_rundown')->nullable();
            $table->smallInteger('status_packaging')->default(0);
            $table->smallInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('m_material_pck', function (Blueprint $table) {
            $table->bigIncrements('id_materialpck');
            $table->unsignedBigInteger('id_material');
            $table->string('code', 20);
            $table->string('code_noneudr', 20);
            $table->string('description', 500);
            $table->string('batch_prefix', 10)->nullable();
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('id_material')
                ->references('id_material')
                ->on('m_material')
                ->onDelete('cascade');
        });

        Schema::create('m_supplier', function (Blueprint $table) {
            $table->bigIncrements('id_supplier');
            $table->string('code', 13);
            $table->string('batch_code', 13);
            $table->string('description', 100)->unique();
            $table->string('type', 20)->nullable();
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('m_sloc', function (Blueprint $table) {
            $table->increments('id_sloc');
            $table->string('id_plant', 10);
            $table->string('plant_name', 100);
            $table->string('id_tank', 50);
            $table->string('code', 50);
            $table->string('code_2', 50);
            $table->string('code_3', 50);
            $table->string('code_4', 50);
            $table->string('description', 50)->nullable();
            $table->decimal('tank_height', 10, 2);
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('m_warehouse', function (Blueprint $table) {
            $table->bigIncrements('id_warehouse');
            $table->string('id_batch', 20);
            $table->string('code', 20);
            $table->string('description', 100);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // Transaction Tables
        Schema::create('t_balance_header', function (Blueprint $table) {
            $table->bigIncrements('id_balance_head');
            $table->date('entry_date')->nullable();
            $table->unsignedBigInteger('trace_no');
            $table->unsignedBigInteger('id_material');
            $table->longText('id_sloc')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->double('qty')->default(0);
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->double('init_qty')->default(0);
            $table->integer('status')->default(1);
            $table->enum('approval_status', ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'])->default('APPROVED');
            $table->string('approved_by', 50)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('t_balance_detail', function (Blueprint $table) {
            $table->bigIncrements('id_balance_tail');
            $table->unsignedBigInteger('id_balance_head');
            $table->unsignedBigInteger('id_supplier');
            $table->unsignedBigInteger('id_material');
            $table->unsignedInteger('id_manufacturer')->nullable();
            $table->longText('id_sloc')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->string('batch_sap', 20)->nullable();
            $table->double('qty')->default(0);
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->double('init_qty')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('id_balance_head')
                ->references('id_balance_head')
                ->on('t_balance_header')
                ->onDelete('cascade');
            $table->foreign('id_supplier')
                ->references('id_supplier')
                ->on('m_supplier')
                ->onDelete('restrict');
            $table->foreign('id_material')
                ->references('id_material')
                ->on('m_material')
                ->onDelete('restrict');
        });

        Schema::create('t_trace_header', function (Blueprint $table) {
            $table->bigIncrements('id_trace_head');
            $table->date('entry_date')->nullable();
            $table->unsignedBigInteger('from_trace_no')->nullable();
            $table->unsignedBigInteger('to_trace_no')->nullable();
            $table->unsignedBigInteger('id_balance_head');
            $table->unsignedBigInteger('id_material');
            $table->string('id_plant', 10)->nullable();
            $table->longText('id_sloc')->nullable();
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->double('last_qtf')->default(0);
            $table->double('curr_qtf')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('id_balance_head')
                ->references('id_balance_head')
                ->on('t_balance_header')
                ->onDelete('cascade');
            $table->foreign('id_material')
                ->references('id_material')
                ->on('m_material')
                ->onDelete('restrict');
        });

        Schema::create('t_trace_detail', function (Blueprint $table) {
            $table->bigIncrements('id_trace_tail');
            $table->unsignedBigInteger('id_trace_head');
            $table->unsignedBigInteger('id_balance_tail');
            $table->unsignedBigInteger('id_supplier');
            $table->unsignedBigInteger('id_material');
            $table->unsignedInteger('id_manufacturer')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->longText('id_sloc')->nullable();
            $table->string('batch_sap', 20)->nullable();
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('id_trace_head')
                ->references('id_trace_head')
                ->on('t_trace_header')
                ->onDelete('cascade');
            $table->foreign('id_supplier')
                ->references('id_supplier')
                ->on('m_supplier')
                ->onDelete('restrict');
            $table->foreign('id_material')
                ->references('id_material')
                ->on('m_material')
                ->onDelete('restrict');
        });

        Schema::create('t_adjustment_header', function (Blueprint $table) {
            $table->bigIncrements('id_adjust_head');
            $table->date('entry_date')->nullable();
            $table->unsignedBigInteger('adjust_no');
            $table->unsignedBigInteger('id_balance_head');
            $table->unsignedBigInteger('id_material');
            $table->longText('id_sloc')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->double('before_adjust')->default(0);
            $table->double('after_adjust')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('t_warehouse_header', function (Blueprint $table) {
            $table->bigIncrements('id_whx_head');
            $table->date('entry_date')->nullable();
            $table->unsignedBigInteger('from_trace_no')->nullable();
            $table->unsignedBigInteger('trace_no');
            $table->unsignedBigInteger('id_material_feed')->nullable();
            $table->unsignedBigInteger('id_material_fg');
            $table->longText('id_sloc')->nullable();
            $table->unsignedBigInteger('id_section')->nullable();
            $table->string('id_plant', 10)->nullable();
            $table->string('batch_no', 20)->nullable();
            $table->string('po_no', 20)->nullable();
            $table->double('qty')->default(0);
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->double('init_qty')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('id_material_feed')
                ->references('id_material')
                ->on('m_material')
                ->onDelete('set null');
            $table->foreign('id_material_fg')
                ->references('id_material')
                ->on('m_material')
                ->onDelete('restrict');
        });

        Schema::create('t_warehouse_detail', function (Blueprint $table) {
            $table->bigIncrements('id_whx_tail');
            $table->unsignedBigInteger('id_whx_head');
            $table->unsignedBigInteger('id_material_feed')->nullable();
            $table->unsignedBigInteger('id_material_fg');
            $table->longText('id_sloc')->nullable();
            $table->unsignedBigInteger('id_supplier');
            $table->string('id_plant', 10)->nullable();
            $table->string('batch_sap', 20)->nullable();
            $table->double('qty')->default(0);
            $table->double('in_qty')->default(0);
            $table->double('out_qty')->default(0);
            $table->double('init_qty')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('id_whx_head')
                ->references('id_whx_head')
                ->on('t_warehouse_header')
                ->onDelete('cascade');
            $table->foreign('id_material_feed')
                ->references('id_material')
                ->on('m_material')
                ->onDelete('set null');
            $table->foreign('id_material_fg')
                ->references('id_material')
                ->on('m_material')
                ->onDelete('restrict');
            $table->foreign('id_supplier')
                ->references('id_supplier')
                ->on('m_supplier')
                ->onDelete('restrict');
        });

        Schema::create('t_shipment_header', function (Blueprint $table) {
            $table->bigIncrements('id_ship_head');
            $table->date('entry_date')->nullable();
            $table->unsignedBigInteger('from_trace_no');
            $table->unsignedBigInteger('trace_no');
            $table->string('so_no', 20)->nullable();
            $table->unsignedBigInteger('id_material_fg');
            $table->string('id_plant', 10)->nullable();
            $table->double('qty')->default(0);
            $table->string('doc_url', 50)->nullable();
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('id_material_fg')
                ->references('id_material')
                ->on('m_material')
                ->onDelete('restrict');
        });

        Schema::create('t_shipment_detail', function (Blueprint $table) {
            $table->bigIncrements('id_ship_tail');
            $table->unsignedBigInteger('id_ship_head');
            $table->unsignedBigInteger('id_material_fg');
            $table->unsignedBigInteger('id_supplier');
            $table->string('id_plant', 10)->nullable();
            $table->string('batch_sap', 20)->nullable();
            $table->double('qty')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('id_ship_head')
                ->references('id_ship_head')
                ->on('t_shipment_header')
                ->onDelete('cascade');
            $table->foreign('id_material_fg')
                ->references('id_material')
                ->on('m_material')
                ->onDelete('restrict');
            $table->foreign('id_supplier')
                ->references('id_supplier')
                ->on('m_supplier')
                ->onDelete('restrict');
        });

        Schema::create('t_material_document', function (Blueprint $table) {
            $table->bigIncrements('id_matdoc');
            $table->unsignedBigInteger('id_trace_head');
            $table->string('material_document', 50)->nullable();
            $table->string('po_so', 50)->nullable();
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('id_trace_head')
                ->references('id_trace_head')
                ->on('t_trace_header')
                ->onDelete('cascade');
        });

        Schema::create('t_report_pspa_head', function (Blueprint $table) {
            $table->bigIncrements('id_report_head');
            $table->date('period');
            $table->string('batch_sap', 50);
            $table->integer('adjust_status')->default(0);
            $table->integer('lock_status')->default(0);
            $table->integer('status')->default(1);
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        echo "PostgreSQL schema created successfully!\n";
    }

    public function down(): void
    {
        echo "Rolling back PostgreSQL schema...\n";

        $tables = [
            't_report_pspa_head', 't_material_document', 't_shipment_detail', 't_shipment_header',
            't_warehouse_detail', 't_warehouse_header', 't_adjustment_header',
            't_trace_detail', 't_trace_header', 't_balance_detail', 't_balance_header',
            'm_material_pck', 'm_supplier', 'm_material', 'm_sloc', 'm_warehouse', 'm_manufacturer', 'm_plant'
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        echo "PostgreSQL schema rollback complete!\n";
    }
};
