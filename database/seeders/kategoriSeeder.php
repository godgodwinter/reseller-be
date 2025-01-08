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
                'nama' => 'Komputer',
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


        DB::table('barang')->truncate();

        $kategoriList = [
            (object)[
                'kategori_barang_id' => 1,
                'nama' => 'Kulkas A',
            ],
            (object)[
                'kategori_barang_id' => 1,
                'nama' => 'Kulkas B',
            ],
            (object)[
                'kategori_barang_id' => 1,
                'nama' => 'Kulkas C',
            ],

            (object)[
                'kategori_barang_id' => 2,
                'nama' => 'Apel',
            ],

            (object)[
                'kategori_barang_id' => 2,
                'nama' => 'Pisang',
            ],


            (object)[
                'kategori_barang_id' => 3,
                'nama' => 'Ryzen 3',
            ],

            (object)[
                'kategori_barang_id' => 3,
                'nama' => 'Ryzen 5',
            ],

            (object)[
                'kategori_barang_id' => 3,
                'nama' => 'Ryzen 7',
            ],
        ];
        //
        foreach ($kategoriList as $data) {

            DB::table('barang')->insert([
                'nama' => $data->nama,
                'kategori_barang_id' => $data->kategori_barang_id,
                'img' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
