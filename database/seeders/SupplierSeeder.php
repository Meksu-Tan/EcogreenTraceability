<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('m_supplier')->insert([
            'code' => '10100027',
            'description' => 'ADEI PLANTATION & INDUSTRY, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10100043',
            'description' => 'AGRO JAYA PERDANA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10100092',
            'description' => 'AMAN JAYA PERDANA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10100171',
            'description' => 'ASIANAGRO AGUNGJAYA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10100381',
            'description' => 'BUDI NABATI PERKASA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10100412',
            'description' => 'KODECO AGROJAYA MANDIRI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10100481',
            'description' => 'CIPTA JAYA CEMERLANG, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10100673',
            'description' => 'EKA DURA INDONESIA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10100856',
            'description' => 'GAWI MAKMUR KALIMANTAN, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10100947',
            'description' => 'GUNUNG SEJAHTERA DUA INDAH, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10100948',
            'description' => 'GUNUNG SEJAHTERA PUTI PESONA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10100968',
            'description' => 'HARAPAN SAWIT LESTARI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10101124',
            'description' => 'IVO MAS TUNGGAL, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10101231',
            'description' => 'KENCANA AGRO JAYA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10101300',
            'description' => 'KURNIA TUNGGAL NUGRAHA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10101547',
            'description' => 'NAGAMAS PALMOIL LESTARI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10101694',
            'description' => 'PELITA AGUNG AGRINDUSTRI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10101762',
            'description' => 'PP LONDON SUMATRA INDONESIA TBK, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10101876',
            'description' => 'REA KALTIM PLANTATIONS, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10101948',
            'description' => 'SALIM IVOMAS PRATAMA TBK, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10101965',
            'description' => 'SARI ADITYA LOKA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10101966',
            'description' => 'SARI DUMAI SEJATI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102076',
            'description' => 'SINAR ALAM PERMAI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102083',
            'description' => 'SINAR JAYA INTI MULYA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102084',
            'description' => 'SINAR LAUT, CV',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102119',
            'description' => 'SMART TBK, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102232',
            'description' => 'SYNERGY OIL NUSANTARA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102241',
            'description' => 'TALUK KUANTAN PERKASA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102341',
            'description' => 'TUNAS BARU LAMPUNG TBK, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102373',
            'description' => 'USAHA INTI PADANG, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102429',
            'description' => 'WILMAR NABATI INDONESIA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102437',
            'description' => 'WIRA INNO MAS, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102623',
            'description' => 'AGRO BUKIT, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102695',
            'description' => 'BANYU BENING UTAMA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102817',
            'description' => 'KIMIA TIRTA UTAMA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10102860',
            'description' => 'SARI LEMBAH SUBUR, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103023',
            'description' => 'CERIA PRIMA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103057',
            'description' => 'RAMAJAYA PRAMUKTI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103137',
            'description' => 'SUMBER INDAH PERKASA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103155',
            'description' => 'Binasawit Abadipratama, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103331',
            'description' => 'Karyaindah Alam Sejahtera, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103332',
            'description' => 'Adhitya Serayakorita, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103452',
            'description' => 'Sinar Jaya Inti Mulya',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103456',
            'description' => 'BATARA ELOK SEMESTA TERPADU, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103457',
            'description' => 'TH INDO PLANTATIONS',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103525',
            'description' => 'DHARMA SATYA NUSANTARA TBK, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103545',
            'description' => 'LAGUNA MANDIRI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103572',
            'description' => 'GUNUNG MAS RAYA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103754',
            'description' => 'UNILEVER OLEOCHEMICALS INDONESIA PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103773',
            'description' => 'BINAPRATAMA SAKATOJAYA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103796',
            'description' => 'STEELINDO WAHANA PERKASA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103835',
            'description' => 'MITRA MENDAWAI SEJATI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103872',
            'description' => 'TEGUH SEMPURNA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103885',
            'description' => 'KPB NUSANTARA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103890',
            'description' => 'MUTIARA BUNDA JAYA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103976',
            'description' => 'WIRATADAYA BANGUN PERSADA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103986',
            'description' => 'MITRA KARYA USAHA PERKASA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103991',
            'description' => 'CITRAKOPRASINDO TANI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10103997',
            'description' => 'PASANG KAYU, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104002',
            'description' => 'GLOBAL INTERINTI INDUSTRY, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104041',
            'description' => 'BERKAH EMAS SUMBER TERANG, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104091',
            'description' => 'WILMAR CAHAYA INDONESIA TBK, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104160',
            'description' => 'LETAWA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104161',
            'description' => 'SURYARAYA LESTARI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104166',
            'description' => 'PENITI SUNGAI PURUN, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104192',
            'description' => 'PUTRA BANGKA MANDIRI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104201',
            'description' => 'BANYUASIN NUSANTARA SEJAHTERA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104226',
            'description' => 'GUNUNG SEJAHTERA IBU PERTIWI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104242',
            'description' => 'BORNEO INDAH MARJAYA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104243',
            'description' => 'WARU KALTIM PLANTATION, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104244',
            'description' => 'KARYANUSA EKADAYA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104312',
            'description' => 'PERKEBUNAN NUSANTARA VII, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104359',
            'description' => 'ANDES AGRO INVESTAMA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104375',
            'description' => 'SINAR JAYA INTI MULYA, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104383',
            'description' => 'HARTONO PLANTATION INDONESIA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104410',
            'description' => 'SARI MAS PERMAI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104422',
            'description' => 'GUNUNG MARAS LESTARI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104451',
            'description' => 'KAWAKEN SINGAPORE PTE. LTD.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104488',
            'description' => 'MULTI NABATI SULAWESI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104490',
            'description' => "CARGILL INT'L TRADING PTE LTD",
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104530',
            'description' => 'SINAR SAWIT SENTOSA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104822',
            'description' => 'BANGUNJAYA ALAM PERMAI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104823',
            'description' => 'WANASAWIT SUBUR LESTARI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104842',
            'description' => 'PUTERA MANUNGGAL PERKASA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104852',
            'description' => 'TUNAS AGRO SUBUR KENCANA, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104936',
            'description' => 'INDUSTRI NABATI LESTARI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104960',
            'description' => 'SINAR TAYAN INTI MULYA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10104990',
            'description' => 'SWADAYA MUKTI PRAKARSA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105000',
            'description' => 'GUNUNG TUA ABADI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105001',
            'description' => 'SAMPOERNA AGRO TBK, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105002',
            'description' => 'TELAGA HIKMAH, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105051',
            'description' => 'CITRA BORNEO UTAMA TBK, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105096',
            'description' => 'ENERGI UNGGUL PERSADA, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105098',
            'description' => 'TIRTA MADU SAWIT JAYA, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105131',
            'description' => 'GOLDEN AGRI INTERNATIONAL PTE LTD',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105136',
            'description' => 'PERKEBUNAN NUSANTARA V, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105137',
            'description' => 'SURYAMAS CIPTA PERKASA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105138',
            'description' => 'PERINTIS LESTARI TALANGDUKU, CV',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105139',
            'description' => 'PERKEBUNAN NUSANTARA III, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105151',
            'description' => 'JUJUR SENTOSA, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105155',
            'description' => 'BINA KARYA PRIMA, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105170',
            'description' => 'KUTAI REFINERY NUSANTARA, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105181',
            'description' => 'JALIN VANEO, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105193',
            'description' => 'CITRA AGRO KENCANA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105194',
            'description' => 'KETAPANG AGRO LESTARI, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105196',
            'description' => 'BUANA KARYA BHAKTI, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105215',
            'description' => 'MITRA ANEKA REZEKI, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105236',
            'description' => 'HINDOLI, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105276',
            'description' => 'ENERGI UNGGUL PERSADA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105388',
            'description' => 'CARGILL TRADING INDONESIA, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105415',
            'description' => 'SUKSES KARYA MANDIRI, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105457',
            'description' => 'SINAR BENGKULU INTI MULYA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105491',
            'description' => 'GREEN GLOBAL UTAMA, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105492',
            'description' => 'PARNA AGROMAS, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105535',
            'description' => 'FIRST LAMANDAU TIMBER INTERNATIONAL',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '10105540',
            'description' => 'PERKEBUNAN NUSANTARA IV, PT.',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '16001718',
            'description' => 'SWAKARSA SINARSENTOSA, PT',
            'created_by' => 'santo'
        ]);
        \DB::table('m_supplier')->insert([
            'code' => '16002716',
            'description' => 'SAWIT SUMBERMAS SARANA Tbk, PT',
            'created_by' => 'santo'
        ]);
    }
}
