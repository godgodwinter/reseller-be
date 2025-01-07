<?php

namespace Database\Seeders;

use App\Models\Gurubk;
use App\Models\masterdeteksi;
use App\Models\Ortu;
use App\Models\Owner;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Yayasan;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class kategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('kategori_barang')->truncate();

        $kategoriList = [
            (object)[
                'nama' => 'Elektronik Dapur',
            ],
            (object)[
                'nama' => 'Makanan',
            ],
            (object)[
                'nama' => 'Elektronik Rumah Tangga',
            ],
        ];
        //
        foreach ($kategoriList as $data) {

            DB::table('kategori_barang')->insert([
                'nama' => $data->nama,
                'img' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
