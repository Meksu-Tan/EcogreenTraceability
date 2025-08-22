<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('m_material')->insert([
            'code' => '5000010006010',
            'description' => 'CPKO',
            'type' => 'RM',
            'yield' => '100',
            'qtf_rundown' => '-',
            'qtf_feed' => '101 FT0113',
            'id_rundown' => '00',
            'id_feed' => '01',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'DA OIL',
            'type' => 'WIP',
            'yield' => '93',
            'qtf_rundown' => '102 FT0109',
            'qtf_feed' => '103 FT0101',
            'id_rundown' => '11',
            'id_feed' => '02',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'TREATED GLY',
            'type' => 'WIP',
            'yield' => '18.7',
            'qtf_rundown' => '103 FT0266',
            'qtf_feed' => '110 F0107',
            'id_rundown' => '22',
            'id_feed' => '04',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'CRUDE ME',
            'type' => 'WIP',
            'yield' => '94.4',
            'qtf_rundown' => '103 FT0329',
            'qtf_feed' => '104 F0118',
            'id_rundown' => '12',
            'id_feed' => '03',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'CRUDE GLY',
            'type' => 'WIP',
            'yield' => '61',
            'qtf_rundown' => '110 F0108',
            'qtf_feed' => '111 F0118',
            'id_rundown' => '14',
            'id_feed' => '07',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'ME 28',
            'type' => 'WIP',
            'yield' => '72',
            'qtf_rundown' => '104 FT0332',
            'qtf_feed' => '105 FQ104',
            'id_rundown' => '43',
            'id_feed' => '06',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'CFA 28',
            'type' => 'WIP',
            'yield' => '87',
            'qtf_rundown' => '105 FQ808',
            'qtf_feed' => '106 F0115',
            'id_rundown' => '16',
            'id_feed' => '08',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'PKFAD',
            'type' => 'PRD',
            'yield' => '100',
            'qtf_rundown' => '101 FT0113',
            'qtf_feed' => '-',
            'id_rundown' => '21',
            'id_feed' => '-',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'GLYCERINE',
            'type' => 'PRD',
            'yield' => '100',
            'qtf_rundown' => '111 FT0314',
            'qtf_feed' => '-',
            'id_rundown' => '17',
            'id_feed' => '-',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'UME',
            'type' => 'WIP',
            'yield' => '100',
            'qtf_rundown' => '104 FT0110',
            'qtf_feed' => '302FT102',
            'id_rundown' => '33',
            'id_feed' => '05',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'WME',
            'type' => 'PRD',
            'yield' => '100',
            'qtf_rundown' => '302FT102',
            'qtf_feed' => '-',
            'id_rundown' => '15',
            'id_feed' => '-',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'Ecorol 24',
            'type' => 'PRD',
            'yield' => '62.6',
            'qtf_rundown' => '106 F0134',
            'qtf_feed' => '112 F0109',
            'id_rundown' => '38',
            'id_feed' => '09',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'Ecorol 16',
            'type' => 'PRD',
            'yield' => '9',
            'qtf_rundown' => '106 F0231',
            'qtf_feed' => '112 F0109',
            'id_rundown' => '48',
            'id_feed' => '09',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'Ecorol Wax',
            'type' => 'PRD',
            'yield' => '100',
            'qtf_rundown' => '106 F0245',
            'qtf_feed' => '112 F0109',
            'id_rundown' => '18',
            'id_feed' => '09',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'ME60',
            'type' => 'PRD',
            'yield' => '8.9',
            'qtf_rundown' => '104 F0157',
            'qtf_feed' => '-',
            'id_rundown' => '13',
            'id_feed' => '-',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'BDME',
            'type' => 'PRD',
            'yield' => '2.6',
            'qtf_rundown' => '104 F0215',
            'qtf_feed' => '-',
            'id_rundown' => '23',
            'id_feed' => '-',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'LEFA',
            'type' => 'PRD',
            'yield' => '0.9',
            'qtf_rundown' => '106 F0134',
            'qtf_feed' => '-',
            'id_rundown' => '28',
            'id_feed' => '-',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'ECOROL 18lrr',
            'type' => 'WIP',
            'yield' => '20.4',
            'qtf_rundown' => '106 F0112',
            'qtf_feed' => '112 F0109',
            'id_rundown' => '58',
            'id_feed' => '09',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'Bottom Wax',
            'type' => 'WIP',
            'yield' => '100',
            'qtf_rundown' => '112 F0224',
            'qtf_feed' => '112 F0109',
            'id_rundown' => '19',
            'id_feed' => '09',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'Ecorol 18',
            'type' => 'WIP',
            'yield' => '100',
            'qtf_rundown' => '112 F0235',
            'qtf_feed' => '-',
            'id_rundown' => '29',
            'id_feed' => '-',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'Ecorol 12',
            'type' => 'WIP',
            'yield' => '100',
            'qtf_rundown' => '112 F0235',
            'qtf_feed' => '-',
            'id_rundown' => '39',
            'id_feed' => '-',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'ECOROL 18lrr',
            'type' => 'WIP',
            'yield' => '100',
            'qtf_rundown' => '112 F0235',
            'qtf_feed' => '112 F0109',
            'id_rundown' => '49',
            'id_feed' => '09',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'Ecorol 14',
            'type' => 'WIP',
            'yield' => '100',
            'qtf_rundown' => '112 F0235',
            'qtf_feed' => '-',
            'id_rundown' => '59',
            'id_feed' => '-',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'CFA 28',
            'type' => 'WIP',
            'yield' => '87',
            'qtf_rundown' => '112 F0224',
            'qtf_feed' => '112 F0109',
            'id_rundown' => '69',
            'id_feed' => '09',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material')->insert([
            'code' => '-',
            'description' => 'Ecorol 14lrr',
            'type' => 'WIP',
            'yield' => '100',
            'qtf_rundown' => '112 F0224',
            'qtf_feed' => '112 F0109',
            'id_rundown' => '79',
            'id_feed' => '09',
            'created_by' => 'santo'
        ]);
    }
}
