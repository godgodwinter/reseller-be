<?php

use App\Http\Controllers\admin\adminAdministratorController;
use App\Http\Controllers\admin\adminKategoriBarangController;
use App\Http\Controllers\admin\adminProsesController;
use App\Http\Controllers\admin\adminRekapController;
use App\Http\Controllers\admin\adminTransaksiController;
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
        Route::delete('/admin/kategori_barang/{item}/force', [adminKategoriBarangController::class, 'destroyForce']);


        // Route::get('/admin/barang', [adminKategoriController::class, 'index']);
        // Route::post('/admin/barang', [adminKategoriController::class, 'store']);
        // Route::get('/admin/barang/{item}', [adminKategoriController::class, 'edit']);
        // Route::put('/admin/barang/{item}', [adminKategoriController::class, 'update']);
        // Route::delete('/admin/barang/{item}', [adminKategoriController::class, 'destroy']);
        // Route::delete('/admin/barang/{item}/force', [adminKategoriController::class, 'destroyForce']);


        // Route::get('/admin/stok_barang', [adminKategoriController::class, 'index']);
        // Route::post('/admin/stok_barang', [adminKategoriController::class, 'store']);
        // Route::get('/admin/stok_barang/{item}', [adminKategoriController::class, 'edit']);
        // Route::put('/admin/stok_barang/{item}', [adminKategoriController::class, 'update']);
        // Route::delete('/admin/stok_barang/{item}', [adminKategoriController::class, 'destroy']);
        // Route::delete('/admin/stok_barang/{item}/force', [adminKategoriController::class, 'destroyForce']);



        Route::get('/admin/users', [adminAdministratorController::class, 'index']);
        Route::post('/admin/users', [adminAdministratorController::class, 'store']);
        Route::get('/admin/users/{item}', [adminAdministratorController::class, 'edit']);
        Route::put('/admin/users/{item}', [adminAdministratorController::class, 'update']);
        Route::delete('/admin/users/{item}', [adminAdministratorController::class, 'destroy']);
        Route::delete('/admin/users/{item}/force', [adminAdministratorController::class, 'destroyForce']);



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
