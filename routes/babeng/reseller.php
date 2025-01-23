<?php

use App\Http\Controllers\ResellerAuthController;
use Illuminate\Support\Facades\Route;

Route::post("reseller/auth/login", [ResellerAuthController::class, 'login']);
Route::middleware('auth:api')->group(
    function () {
        Route::get("reseller/auth/me", [ResellerAuthController::class, 'me']);
        Route::post("reseller/auth/profile", [ResellerAuthController::class, 'refresh']);
        Route::put("reseller/auth/profile", [ResellerAuthController::class, 'update']);
    }
);
        // Route::get('/admin/rekap/kategori/{kategori}', [adminTransaksiController::class, 'rekap_perkategori']); //inputan:month + year -->
