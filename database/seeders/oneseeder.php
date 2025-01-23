<?php

namespace Database\Seeders;

use App\Models\Gurubk;
use App\Models\masterdeteksi;
use App\Models\Ortu;
use App\Models\Owner;
use App\Models\Reseller;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Yayasan;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class oneseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->truncate();
        //settings SEEDER
        DB::table('settings')->insert([
            'app_nama' => 'App Reseller',
            'app_namapendek' => 'ResellerApp',
            'paginationjml' => '10',
            'login' => 'Aktif',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('users')->truncate();
        // admin
        User::insert([
            'nama' => 'Admin Paijo',
            'email' => 'admin@gmail.com',
            'username' => 'admin',
            'nomeridentitas' => '123',
            'password' => Hash::make('admin'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);


        DB::table('reseller')->truncate();
        // reseller
        Reseller::insert([
            'nama' => 'Reseller 1',
            'email' => 'reseller_1@gmail.com',
            'username' => 'res1',
            'nomeridentitas' => '1',
            'password' => Hash::make('res1'),
            'pembuat_id' => '1',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        Reseller::insert([
            'nama' => 'Reseller 2',
            'email' => 'reseller_2@gmail.com',
            'username' => 'res2',
            'nomeridentitas' => '2',
            'password' => Hash::make('res2'),
            'pembuat_id' => '1',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        Reseller::insert([
            'nama' => 'Reseller 3',
            'email' => 'reseller_3@gmail.com',
            'username' => 'res3',
            'nomeridentitas' => '3',
            'password' => Hash::make('res3'),
            'pembuat_id' => '1',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}
