<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\kategori;
use App\Models\transaksi;
use App\Models\transaksi_nitip_barang;
use App\Models\transaksi_setor_barang;
use App\Models\transaksi_setor_barang_detail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\AdminService; // Sesuaikan namespace jika berbeda

class adminTransaksiSetorbarangController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    protected function getAdminUser()
    {
        return Auth::guard()->user();
    }

    /**
     * Admin: Konfirmasi transaksi setor barang (update status & keterangan)
     *
     * @param Request $request
     * @param int $id (transaksi_setor_barang_id)
     * @return \Illuminate\Http\JsonResponse
     */

    public function do_konfirmasi(Request $request)
    {
        $user = $this->getAdminUser(); // Pastikan method ini ada

        // Validasi input
        $validator = Validator::make($request->all(), [
            'transaksi_setor_barang_id' => 'required|integer|exists:transaksi_setor_barang,id',
            'status_konfirmasi' => 'required|string|in:Belum,Disetujui,Ditolak',
            'keterangan_konfirmasi' => 'nullable|string|max:500',
        ], [
            'transaksi_setor_barang_id.required' => 'ID transaksi setor barang wajib diisi.',
            'transaksi_setor_barang_id.integer' => 'ID transaksi harus angka.',
            'transaksi_setor_barang_id.exists' => 'Transaksi setor barang tidak ditemukan.',
            'status_konfirmasi.in' => 'Status konfirmasi harus: Belum, Disetujui, atau Ditolak.',
            'keterangan_konfirmasi.max' => 'Keterangan maksimal 500 karakter.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Ambil ID dari request
        $transaksiId = $data['transaksi_setor_barang_id'];

        // Panggil service
        $result = $this->adminService->admin_updateStatusKonfirmasiSetor(
            $transaksiId,
            $data['status_konfirmasi'],
            $data['keterangan_konfirmasi'],
            $user->id
        );

        // Kembalikan respons
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null,
            ], 500);
        }
    }

    public function get_all(Request $request)
    {
        $items = transaksi_setor_barang::with([
            'reseller',
            'transaksi_setor_barang_detail.stok_barang.barang.kategori_barang'
        ])->get();

        $data = $items->map(function ($item) {
            $details = $item->transaksi_setor_barang_detail;

            // 🔹 Kategori unik
            $kategori_barangs = $details
                ->pluck('stok_barang.barang.kategori_barang.nama')
                ->filter()
                ->unique()
                ->values()
                ->all();

            // 🔹 Hitung jumlah unik nama barang (misal: Apel, Kulkas A = 2)
            $jumlah_jenis_barang = $details
                ->pluck('stok_barang.barang.nama') // Ambil nama barang
                ->filter()
                ->unique()
                ->count();

            $total_barang = $details->sum('qty');

            return [
                'id' => $item->id,
                'users_id' => $item->users_id,
                'reseller_id' => $item->reseller_id,
                'reseller_nama' => optional($item->reseller)->nama ?? null,
                'metode_pembayaran' => $item->metode_pembayaran,
                'status_konfirmasi' => $item->status_konfirmasi,
                'keterangan_konfirmasi' => $item->keterangan_konfirmasi,
                'tgl_konfirmasi' => $item->tgl_konfirmasi,
                'bukti_tf' => $item->bukti_tf,
                'tgl_transaksi_setor_barang' => $item->tgl_transaksi_setor_barang,
                'kategori_barang' => $kategori_barangs,
                'jumlah_jenis_barang' => $jumlah_jenis_barang, // ✅ Jumlah unik nama barang
                'total_barang' => $total_barang,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'details' => $details->map(function ($detail) {
                    return [
                        'stok_barang_id' => $detail->stok_barang_id,
                        'nama_barang' => optional($detail->stok_barang->barang)->nama ?? null,
                        'qty' => $detail->qty,
                        'harga' => $detail->harga,
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * Get satu transaksi setor barang berdasarkan ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_setor_barang_where_id($id)
    {
        // Cari transaksi dengan relasi
        $item = transaksi_setor_barang::with([
            'reseller:id,nama', // ambil reseller (hanya id & nama)
            'transaksi_setor_barang_detail' => function ($q) {
                $q->with([
                    'stok_barang.barang:id,nama' // ambil barang dari stok_barang
                ]);
            }
        ])
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi setor barang tidak ditemukan.',
            ], 404);
        }

        // Format data
        $data = [
            'id' => $item->id,
            'users_id' => $item->users_id,
            'reseller_id' => $item->reseller_id,
            'reseller_nama' => optional($item->reseller)->nama ?? 'Tidak diketahui',
            'metode_pembayaran' => $item->metode_pembayaran,
            'status_konfirmasi' => $item->status_konfirmasi,
            'keterangan_konfirmasi' => $item->keterangan_konfirmasi,
            'tgl_konfirmasi' => $item->tgl_konfirmasi,
            'tgl_transaksi_setor_barang' => $item->tgl_transaksi_setor_barang,
            'bukti_tf' => $item->bukti_tf,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'total_qty' => $item->transaksi_setor_barang_detail->sum('qty'),
            'details' => $item->transaksi_setor_barang_detail->map(function ($detail) {
                return [
                    'stok_barang_id' => $detail->stok_barang_id,
                    'nama_barang' => optional($detail->stok_barang->barang)->nama ?? 'Tidak diketahui',
                    'qty' => $detail->qty,
                    'harga' => $detail->harga,
                    'status_konfirmasi' => $detail->status_konfirmasi,
                    'keterangan_konfirmasi' => $detail->keterangan_konfirmasi,
                ];
            })->values(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data transaksi setor barang ditemukan.',
            'data' => $data,
        ], 200);
    }
}
