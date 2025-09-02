<?php

namespace App\Http\Controllers\reseller;

use App\Http\Controllers\Controller;
use App\Models\transaksi_nitip_barang;
use App\Models\transaksi_nitip_barang_detail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ResellerService;

class resellerStokBarangController extends Controller
{

    protected $resellerService;

    public function __construct(ResellerService $resellerService)
    {
        $this->resellerService = $resellerService;
    }

    // Helper method untuk mendapatkan user reseller
    protected function getResellerUser()
    {
        return Auth::guard('reseller')->user();
    }



    public function setor_barang(Request $request)
    {
        $user = $this->getResellerUser();
        return response()->json([
            'success'    => true,
            'data'    => "Proses setor barang Reseller ke seller dengan bukti tf",
            'my_id' => $user->id, // Ambil ID user
            'user'  => $user,     // Opsional: kirim seluruh data user
        ], 200);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['sort_by', 'sort_direction']);
        $user = $this->getResellerUser();
        $result = $this->resellerService->fn_reseller_getStokBarang($filters, $user->id);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function example(Request $request)
    {
        $user = $this->getResellerUser();
        return response()->json([
            'success'    => true,
            'data'    => "Stok Barang di Reseller",
            'my_id' => $user->id, // Ambil ID user
            'user'  => $user,     // Opsional: kirim seluruh data user
        ], 200);
    }
}
