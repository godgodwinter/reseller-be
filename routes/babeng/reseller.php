<?php

use App\Http\Controllers\reseller\resellerStokBarangController;
use App\Http\Controllers\ResellerAuthController;
use Illuminate\Support\Facades\Route;

Route::post("reseller/auth/login", [ResellerAuthController::class, 'login']);

// Route::middleware('auth:api')->group(
//     function () {
//         Route::get("reseller/auth/me", [ResellerAuthController::class, 'me']);
//         Route::post("reseller/auth/profile", [ResellerAuthController::class, 'refresh']);
//         Route::put("reseller/auth/profile", [ResellerAuthController::class, 'update']);
//     }
// );


Route::get("reseller/index", function () {
    return response()->json([
        'success' => true,
        'message' => 'Ini index reseller tanpa login',
        'data' => [
            'id' => 1,
            'name' => 'FAKE DATA reseller Name',
            'role' => 'reseller'
        ],
    ]);
});

// !testing
// Route::get('/reseller/stok_barang', [resellerStokBarangController::class, 'index']);
// !reseller
Route::middleware('babeng:reseller')->group(
    function () {

        Route::get("reseller/auth/me", [ResellerAuthController::class, 'me']);
        Route::post("reseller/auth/profile", [ResellerAuthController::class, 'refresh']);
        // update
        Route::put("reseller/auth/profile", [ResellerAuthController::class, 'update']);

        // !transaksi
        Route::get('/reseller/stok_barang', [resellerStokBarangController::class, 'index']);
        Route::get('/reseller/stok_barang/example', [resellerStokBarangController::class, 'example']);

        Route::get('/reseller/stok_barang/setor_barang', [resellerStokBarangController::class, 'setor_barang_index']);
        Route::get('/reseller/stok_barang/setor_barang/{setor_id}', [resellerStokBarangController::class, 'setor_barang_per_transaksi_id']);
        Route::post('/reseller/stok_barang/get', [resellerStokBarangController::class, 'stok_barang_get']); //per barang_id
        Route::post('/reseller/stok_barang/setor_barang/do_simpan', [resellerStokBarangController::class, 'setor_barang_do_simpan']);

        // Route::post('/reseller/stok_barang/get', [resellerStokBarangController::class, 'stok_barang_get']); //per barang_id
        Route::get('/reseller/stok_barang/retur_barang', [resellerStokBarangController::class, 'retur_barang_index']);
        Route::post('/reseller/stok_barang/retur_barang/do_simpan', [resellerStokBarangController::class, 'retur_barang_do_simpan']);
    }
);
