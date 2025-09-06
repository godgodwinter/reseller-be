<?php

namespace App\Http\Controllers\reseller;

use App\Http\Controllers\Controller;
use App\Models\transaksi_retur_barang;
use App\Models\transaksi_setor_barang;
use App\Models\transaksi_setor_barang_detail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ResellerService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

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

    public function setor_barang_index(Request $request)
    {
        $user = $this->getResellerUser();

        // Eager load relasi hingga ke barang dan kategori_barang
        $query = transaksi_setor_barang::with([
            'details.stok_barang.barang.kategori_barang'
        ])->where('reseller_id', $user->id);

        $status = $request->input('status_konfirmasi');
        if ($status && $status !== 'semua') {
            $query->where('status_konfirmasi', $status);
        }

        $tanggal = $request->input('tgl_transaksi_setor_barang');
        if ($tanggal) {
            $query->whereDate('tgl_transaksi_setor_barang', $tanggal);
        }

        $query->orderBy('tgl_transaksi_setor_barang', 'desc');

        $data = $query->paginate(10);

        // Tambahkan informasi nama barang & kategori di setiap detail
        $data->getCollection()->transform(function ($transaksi) {
            $transaksi->details->transform(function ($detail) {
                // Ambil nama barang dan kategori dari relasi
                $barang = $detail->stok_barang?->barang;
                $kategori = $barang?->kategori_barang;

                $detail->nama_barang = $barang?->nama ?? 'Tidak tersedia';
                $detail->kategori_barang = $kategori?->nama ?? 'Tidak tersedia';

                return $detail;
            });
            return $transaksi;
        });

        $filters = [
            'status_konfirmasi' => $status,
            'tgl_transaksi_setor_barang' => $tanggal,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data Setor Barang',
            'filters' => $filters,
            'data' => $data,
        ], 200);
    }


    public function setor_barang_per_transaksi_id(Request $request, $setor_id)
    {
        $user = $this->getResellerUser();

        // Ambil transaksi berdasarkan ID dan pastikan milik reseller ini
        $transaksi = transaksi_setor_barang::with([
            'details.stok_barang.barang.kategori_barang'
        ])
            ->where('id', $setor_id)
            ->where('reseller_id', $user->id)
            ->first();

        // Jika tidak ditemukan
        if (!$transaksi) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan atau bukan milik Anda.',
            ], 404);
        }

        // Tambahkan nama_barang dan kategori_barang ke setiap detail
        $transaksi->details->transform(function ($detail) {
            $barang = $detail->stok_barang?->barang;
            $kategori = $barang?->kategori_barang;

            $detail->nama_barang = $barang?->nama ?? 'Tidak tersedia';
            $detail->kategori_barang = $kategori?->nama ?? 'Tidak tersedia';

            return $detail;
        });

        // Hitung total qty dan total harga untuk frontend
        $totalQty = $transaksi->details->sum('qty');
        $totalHarga = $transaksi->details->sum(fn($d) => $d->harga * $d->qty);

        // Format tanggal agar lebih mudah dibaca di frontend
        $transaksi->tgl_transaksi_setor_barang = \Carbon\Carbon::parse($transaksi->tgl_transaksi_setor_barang)->format('Y-m-d H:i:s');

        return response()->json([
            'success' => true,
            'message' => 'Detail Transaksi Setor Barang',
            'data' => $transaksi,
            'summary' => [
                'total_qty' => $totalQty,
                'total_harga' => $totalHarga,
            ],
        ], 200);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['sort_by', 'sort_direction']);
        $user = $this->getResellerUser();
        // $result = $this->resellerService->fn_reseller_getStokBarang_v3($filters, $user->id);
        $result = $this->resellerService->fn_reseller_getStokBarang_v3($filters, $user->id);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function stok_barang_get(Request $request)
    {
        $barang_id = $request->barang_id ?? null;
        $filters = $request->only(['sort_by', 'sort_direction']);
        $user = $this->getResellerUser();
        $result = $this->resellerService->fn_reseller_getStokBarang_ByBarangId_v3($filters, $user->id, $barang_id);

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

    public function setor_barang_do_simpan(Request $request)
    {
        $user = $this->getResellerUser();
        $reseller_id = $user->id;

        // ✅ Perbaiki validasi: gunakan nitipdetail_id dan stok_id
        $validator = Validator::make($request->all(), [
            'metode_pembayaran' => 'required|string|max:50',
            'tgl_transaksi_setor_barang' => 'nullable|date',
            'bukti_tf' => 'nullable|string',
            'detail_barang' => 'required|array|min:1',
            'detail_barang.*.nitipdetail_id' => 'required|integer|exists:transaksi_nitip_barang_detail,id',
            'detail_barang.*.stok_id' => 'required|integer|exists:stok_barang,id',
            'detail_barang.*.qty' => 'required|integer|min:1',
            'detail_barang.*.harga' => 'required|numeric|min:0',
        ], [
            'detail_barang.*.nitipdetail_id.exists' => 'Detail transaksi titip tidak valid atau tidak ditemukan.',
            'detail_barang.*.stok_id.exists' => 'Stok barang tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }

        // 🔹 Validasi stok sebelum simpan
        $validation = $this->resellerService->fn_validateStokTransaksiSetor_v3($request->detail_barang, $reseller_id);

        if (!$validation['success']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message'],
                'errors' => $validation['errors'],
            ], 422);
        }

        // Mulai transaksi database
        DB::beginTransaction();
        try {
            $tglTransaksi = $request->filled('tgl_transaksi_setor_barang')
                ? $request->tgl_transaksi_setor_barang
                : now()->format('Y-m-d\TH:i');

            $data_id = DB::table('transaksi_setor_barang')->insertGetId([
                'reseller_id'                   => $reseller_id,
                'tgl_transaksi_setor_barang'  => $tglTransaksi,
                'bukti_tf'                      => $request->bukti_tf,
                'metode_pembayaran'             => $request->metode_pembayaran,
                'status_konfirmasi'             => "Belum",
                'status'                        => "Aktif",
                'users_id'                      => $reseller_id,
                'created_at'                    => now(),
                'updated_at'                    => now(),
            ]);

            foreach ($request->detail_barang as $detail) {
                DB::table('transaksi_setor_barang_detail')->insert([
                    'reseller_id'                   => $reseller_id,
                    'stok_barang_id'           => $detail['stok_id'],
                    'transaksi_nitip_barang_detail_id'           => $detail['nitipdetail_id'],
                    'harga'                    => $detail['harga'],
                    'qty'                      => $detail['qty'],
                    'transaksi_setor_barang_id' => $data_id,
                    'status_konfirmasi'         => "Belum",
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan.',
                'data' => [
                    'transaksi_setor_barang_id' => $data_id,
                    'detail_barang' => $request->detail_barang,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error simpan transaksi setor barang: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function retur_barang_index(Request $request)
    {
        $user = $this->getResellerUser();

        // Eager load relasi hingga ke barang dan kategori_barang
        $query = transaksi_retur_barang::with([
            'details.stok_barang.barang.kategori_barang'
        ])->where('reseller_id', $user->id);

        // $status = $request->input('status_konfirmasi');
        // if ($status && $status !== 'semua') {
        //     $query->where('status_konfirmasi', $status);
        // }

        $tanggal = $request->input('tgl_transaksi_retur_barang');
        if ($tanggal) {
            $query->whereDate('tgl_transaksi_retur_barang', $tanggal);
        }

        $query->orderBy('tgl_transaksi_retur_barang', 'desc');

        $data = $query->paginate(10);

        // Tambahkan informasi nama barang & kategori di setiap detail
        $data->getCollection()->transform(function ($transaksi) {
            $transaksi->details->transform(function ($detail) {
                // Ambil nama barang dan kategori dari relasi
                $barang = $detail->stok_barang?->barang;
                $kategori = $barang?->kategori_barang;

                $detail->nama_barang = $barang?->nama ?? 'Tidak tersedia';
                $detail->kategori_barang = $kategori?->nama ?? 'Tidak tersedia';

                return $detail;
            });
            return $transaksi;
        });

        $filters = [
            // 'status_konfirmasi' => $status,
            'tgl_transaksi_retur_barang' => $tanggal,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data Setor Barang',
            'filters' => $filters,
            'data' => $data,
        ], 200);
    }
    public function retur_barang_do_simpan(Request $request)
    {
        $user = $this->getResellerUser();
        $reseller_id = $user->id;

        // ✅ Perbaiki validasi: gunakan nitipdetail_id dan stok_id
        $validator = Validator::make($request->all(), [
            // 'metode_pembayaran' => 'required|string|max:50',
            'tgl_transaksi_retur_barang' => 'nullable|date',
            // 'bukti_tf' => 'nullable|string',
            'detail_barang' => 'required|array|min:1',
            'detail_barang.*.nitipdetail_id' => 'required|integer|exists:transaksi_nitip_barang_detail,id',
            'detail_barang.*.stok_id' => 'required|integer|exists:stok_barang,id',
            'detail_barang.*.qty' => 'required|integer|min:1',
            'detail_barang.*.harga' => 'required|numeric|min:0',
        ], [
            'detail_barang.*.nitipdetail_id.exists' => 'Detail transaksi titip tidak valid atau tidak ditemukan.',
            'detail_barang.*.stok_id.exists' => 'Stok barang tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'data' => $validator->errors(),
            ], 422);
        }

        // 🔹 Validasi stok sebelum simpan
        $validation = $this->resellerService->fn_validateStokTransaksiSetor_v3($request->detail_barang, $reseller_id);

        if (!$validation['success']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message'],
                'errors' => $validation['errors'],
            ], 422);
        }

        // Mulai transaksi database
        DB::beginTransaction();
        try {
            $tglTransaksi = $request->filled('tgl_transaksi_retur_barang')
                ? $request->tgl_transaksi_retur_barang
                : now()->format('Y-m-d\TH:i');

            $data_id = DB::table('transaksi_retur_barang')->insertGetId([
                'reseller_id'                   => $reseller_id,
                'tgl_transaksi_retur_barang'  => $tglTransaksi,
                // 'bukti_tf'                      => $request->bukti_tf,
                // 'metode_pembayaran'             => $request->metode_pembayaran,
                // 'status_konfirmasi'             => "Belum",
                'status'                        => "Aktif",
                'users_id'                      => $reseller_id,
                'created_at'                    => now(),
                'updated_at'                    => now(),
            ]);

            foreach ($request->detail_barang as $detail) {
                DB::table('transaksi_retur_barang_detail')->insert([
                    'reseller_id'                   => $reseller_id,
                    'stok_barang_id'           => $detail['stok_id'],
                    'transaksi_nitip_barang_detail_id'           => $detail['nitipdetail_id'],
                    'harga'                    => $detail['harga'],
                    'qty'                      => $detail['qty'],
                    'transaksi_retur_barang_id' => $data_id,
                    'status_konfirmasi'         => "Belum",
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan.',
                'data' => [
                    'transaksi_retur_barang_id' => $data_id,
                    'detail_barang' => $request->detail_barang,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error simpan transaksi setor barang: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
