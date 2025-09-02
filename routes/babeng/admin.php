<?php

use App\Http\Controllers\admin\adminAdministratorController;
use App\Http\Controllers\admin\adminBarangController;
use App\Http\Controllers\admin\adminKategoriBarangController;
use App\Http\Controllers\admin\adminProsesController;
use App\Http\Controllers\admin\adminRekapController;
use App\Http\Controllers\admin\adminResellerController;
use App\Http\Controllers\admin\adminStokBarangController;
use App\Http\Controllers\admin\adminTransaksiController;
use App\Http\Controllers\admin\adminTransaksiNitipbarangController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// php get .env

// $prefix = getenv('API_VERSION') ? getenv('API_VERSION') : 'v1';
// Route::post('/admin/auth/register', [AuthController::class, 'register'])->name('admin.auth.register');
// Route::middleware('api')->group(function () {
Route::post("admin/auth/login", [AuthController::class, 'login']);
Route::get("admin/index", function () {
    return response()->json([
        'success' => true,
        'message' => 'Ini index admin tanpa login',
        'data' => [
            'id' => 1,
            'name' => 'FAKE DATA Admin Name',
            'role' => 'Administrator'
        ],
    ]);
});
// Route::middleware(['auth:api'])->group(
// !testing
// Route::get('/gues/barang', [adminBarangController::class, 'index']);
// !admin
Route::middleware('babeng:adminOwner')->group(
    function () {
        Route::get("admin/auth/me", [AuthController::class, 'me']);
        Route::post("admin/auth/profile", [AuthController::class, 'refresh']);
        // update
        Route::put("admin/auth/profile", [AuthController::class, 'update']);

        Route::get('/admin/kategori_barang', [adminKategoriBarangController::class, 'index']);
        Route::post('/admin/kategori_barang', [adminKategoriBarangController::class, 'store']);
        Route::get('/admin/kategori_barang/{item}', [adminKategoriBarangController::class, 'edit']);
        Route::put('/admin/kategori_barang/{item}', [adminKategoriBarangController::class, 'update']);
        Route::delete('/admin/kategori_barang/{item}', [adminKategoriBarangController::class, 'destroy']);


        Route::get('/admin/barang', [adminBarangController::class, 'index']);
        Route::post('/admin/barang', [adminBarangController::class, 'store']);
        Route::get('/admin/barang/{item}', [adminBarangController::class, 'edit']);
        Route::put('/admin/barang/{item}', [adminBarangController::class, 'update']);
        Route::delete('/admin/barang/{item}', [adminBarangController::class, 'destroy']);


        Route::get('/admin/stok_barang', [adminStokBarangController::class, 'index']);
        Route::post('/admin/stok_barang/get', [adminStokBarangController::class, 'index']);
        Route::post('/admin/stok_barang', [adminStokBarangController::class, 'store']);
        Route::get('/admin/stok_barang/{item}', [adminStokBarangController::class, 'edit']);
        Route::put('/admin/stok_barang/{item}', [adminStokBarangController::class, 'update']);
        Route::delete('/admin/stok_barang/{item}', [adminStokBarangController::class, 'destroy']);



        Route::get('/admin/users', [adminAdministratorController::class, 'index']);
        Route::post('/admin/users', [adminAdministratorController::class, 'store']);
        Route::get('/admin/users/{item}', [adminAdministratorController::class, 'edit']);
        Route::put('/admin/users/{item}', [adminAdministratorController::class, 'update']);
        Route::put('/admin/users/{item}/pass', [adminAdministratorController::class, 'updatePassword']);
        Route::delete('/admin/users/{item}', [adminAdministratorController::class, 'destroy']);

        Route::get('/admin/reseller', [adminResellerController::class, 'index']);
        Route::post('/admin/reseller', [adminResellerController::class, 'store']);
        Route::get('/admin/reseller/{item}', [adminResellerController::class, 'edit']);
        Route::put('/admin/reseller/{item}', [adminResellerController::class, 'update']);
        Route::put('/admin/reseller/{item}/pass', [adminResellerController::class, 'updatePassword']);
        Route::delete('/admin/reseller/{item}', [adminResellerController::class, 'destroy']);

        // !transaksi
        Route::get('/admin/transaksi/nitip_barang', [adminTransaksiNitipbarangController::class, 'index']);
        Route::post('/admin/transaksi/nitip_barang/get', [adminTransaksiNitipbarangController::class, 'index']);
        Route::post('/admin/transaksi/nitip_barang/get/reseller/{reseller_id}', [adminTransaksiNitipbarangController::class, 'get_where_reseller_id']);
        Route::post('/admin/transaksi/nitip_barang/get/reseller/{reseller_id}/per_barang', [adminTransaksiNitipbarangController::class, 'get_where_reseller_id_per_barang']);
        Route::post('/admin/transaksi/nitip_barang/get/reseller/{reseller_id}/per_stok_barang', [adminTransaksiNitipbarangController::class, 'get_where_reseller_id_per_stok_barang']);
        Route::post('/admin/transaksi/nitip_barang', [adminTransaksiNitipbarangController::class, 'store']);

        // Route::get('/admin/reseller', [adminKategoriController::class, 'index']);
        // Route::post('/admin/reseller', [adminKategoriController::class, 'store']);
        // Route::get('/admin/reseller/{item}', [adminKategoriController::class, 'edit']);
        // Route::put('/admin/reseller/{item}', [adminKategoriController::class, 'update']);
        // Route::delete('/admin/reseller/{item}', [adminKategoriController::class, 'destroy']);
        // Route::delete('/admin/reseller/{item}/force', [adminAdministratorController::class, 'destroyForce']);


        // !baru


        Route::get('/admin/rekap', [adminTransaksiController::class, 'rekap']); //inputan:month + year


        Route::post('/admin/proses/cleartemp ', [adminProsesController::class, 'clearTemp']);
    }
);
Route::get('/admin/rekap/kategori/{kategori}', [adminTransaksiController::class, 'rekap_perkategori']); //inputan:month + year
