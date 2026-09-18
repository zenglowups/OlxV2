<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('countries')->insertOrIgnore(['code' => 'RO', 'name_ro' => 'România', 'name_en' => 'Romania']);
        // Administrative county codes, including the municipality of Bucharest.
        $counties = [
            'AB' => 'Alba', 'AR' => 'Arad', 'AG' => 'Argeș', 'BC' => 'Bacău',
            'BH' => 'Bihor', 'BN' => 'Bistrița-Năsăud', 'BT' => 'Botoșani', 'BV' => 'Brașov',
            'BR' => 'Brăila', 'B' => 'București', 'BZ' => 'Buzău', 'CS' => 'Caraș-Severin',
            'CL' => 'Călărași', 'CJ' => 'Cluj', 'CT' => 'Constanța', 'CV' => 'Covasna',
            'DB' => 'Dâmbovița', 'DJ' => 'Dolj', 'GL' => 'Galați', 'GR' => 'Giurgiu',
            'GJ' => 'Gorj', 'HR' => 'Harghita', 'HD' => 'Hunedoara', 'IL' => 'Ialomița',
            'IS' => 'Iași', 'IF' => 'Ilfov', 'MM' => 'Maramureș', 'MH' => 'Mehedinți',
            'MS' => 'Mureș', 'NT' => 'Neamț', 'OT' => 'Olt', 'PH' => 'Prahova',
            'SM' => 'Satu Mare', 'SJ' => 'Sălaj', 'SB' => 'Sibiu', 'SV' => 'Suceava',
            'TR' => 'Teleorman', 'TM' => 'Timiș', 'TL' => 'Tulcea', 'VS' => 'Vaslui',
            'VL' => 'Vâlcea', 'VN' => 'Vrancea',
        ];
        foreach ($counties as $code => $name) {
            DB::table('counties')->insertOrIgnore(['country_code' => 'RO', 'code' => $code, 'name' => $name]);
        }
    }
}
