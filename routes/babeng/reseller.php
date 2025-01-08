<!-- <?php

        use App\Http\Controllers\admin\adminAdministratorController;
        use App\Http\Controllers\admin\adminKategoriController;
        use App\Http\Controllers\admin\adminProsesController;
        use App\Http\Controllers\admin\adminRekapController;
        use App\Http\Controllers\admin\adminTransaksiController;
        use App\Http\Controllers\AuthController;
        use Illuminate\Support\Facades\Route;

        // php get .env

        // $prefix = getenv('API_VERSION') ? getenv('API_VERSION') : 'v1';
        Route::post("reseller/auth/login", [AuthController::class, 'login']);
        // Route::post('/admin/auth/register', [AuthController::class, 'register'])->name('admin.auth.register');
        // Route::middleware('api')->group(function () {
        Route::middleware('auth:api')->group(
            function () {
                Route::get("reseller/auth/me", [AuthController::class, 'me']);
                // get My Data and New Token
                Route::post("reseller/auth/profile", [AuthController::class, 'refresh']);
                // update
                Route::put("reseller/auth/profile", [AuthController::class, 'update']);
            }
        );
        Route::get('/admin/rekap/kategori/{kategori}', [adminTransaksiController::class, 'rekap_perkategori']); //inputan:month + year -->
