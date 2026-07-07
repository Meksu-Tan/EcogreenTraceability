<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuantifierSeeder extends Seeder
{
    protected string $connection = 'eudr_ts';

    public function run(): void
    {
        $data = [
            ['reset_date' => '2026-06-01', 'flowmeter' => '101_FT0113', 'value' => 1250.500, 'remark' => 'Monthly reset', 'created_by' => 'system'],
            ['reset_date' => '2026-06-01', 'flowmeter' => '102_FT0129', 'value' => 890.000, 'remark' => 'Monthly reset', 'created_by' => 'system'],
            ['reset_date' => '2026-06-01', 'flowmeter' => '103_FT0101', 'value' => 2100.750, 'remark' => 'Monthly reset', 'created_by' => 'system'],
            ['reset_date' => '2026-06-01', 'flowmeter' => '104_FT0101', 'value' => 3400.000, 'remark' => 'Monthly reset', 'created_by' => 'system'],
            ['reset_date' => '2026-06-01', 'flowmeter' => '105_FT0101', 'value' => 678.250, 'remark' => 'Monthly reset', 'created_by' => 'system'],
            ['reset_date' => '2026-06-15', 'flowmeter' => '101_FT0113', 'value' => 450.000, 'remark' => 'Mid-month adjustment', 'created_by' => 'system', 'status' => 0],
            ['reset_date' => '2026-06-15', 'flowmeter' => '111_FT0113', 'value' => 1200.000, 'remark' => 'Bulk reset', 'created_by' => 'system'],
            ['reset_date' => '2026-06-20', 'flowmeter' => '112_FT0113', 'value' => 980.000, 'remark' => 'Week reset', 'created_by' => 'system'],
            ['reset_date' => '2026-06-25', 'flowmeter' => '114_FT0113', 'value' => 1560.300, 'remark' => 'End month reset', 'created_by' => 'system'],
        ];

        foreach ($data as $row) {
            $row['value'] = (float) $row['value'];
            $row['status'] ??= 1;

            $id = DB::connection($this->connection)->table('t_reset_quantifier')->insertGetId([
                'reset_date' => $row['reset_date'],
                'flowmeter' => $row['flowmeter'],
                'value' => $row['value'],
                'remark' => $row['remark'],
                'status' => $row['status'],
                'created_by' => $row['created_by'],
            ], 'id_reset');

            DB::connection($this->connection)->table('log_transactions')->insert([
                'log_module' => 'T_RESET_QTY',
                'log_type' => 'ADD',
                'log_description' => 'ID: '.$id.' | DATE: '.$row['reset_date']
                    .' / FLOWMETER: '.$row['flowmeter']
                    .' / VALUE: '.$row['value']
                    .' / REMARK: '.$row['remark'].' | Status: '.$row['status'],
                'created_by' => $row['created_by'],
            ]);
        }
    }
}
