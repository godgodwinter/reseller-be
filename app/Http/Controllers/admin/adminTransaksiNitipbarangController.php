<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\kategori;
use App\Models\transaksi;
use App\Models\transaksi_nitip_barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class adminTransaksiNitipbarangController extends Controller
{
    public function index(Request $request)
    {
        // Mengambil data transaksi nitip barang berdasarkan users_id
        $items = transaksi_nitip_barang::where('users_id', Auth::guard()->user()->id)
            ->with(['transaksi_nitip_barang_detail.stok_barang.barang', 'reseller']) // Memuat relasi
            ->get();

        // Transformasi data
        $data = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'users_id' => $item->users_id,
                'reseller_id' => $item->reseller_id,
                'reseller_nama' => optional($item->reseller)->nama ?? null, // Mengambil nama reseller (gunakan null coalescing)
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'details' => $item->transaksi_nitip_barang_detail->map(function ($detail) {
                    return [
                        'stok_barang_id' => $detail->stok_barang_id,
                        'nama_barang' => optional($detail->stok_barang->barang)->nama ?? null, // Mengambil nama barang (gunakan null coalescing)
                        'qty' => $detail->qty,
                        'harga' => $detail->harga,
                    ];
                }),
            ];
        });

        // Mengembalikan respons JSON
        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function get_where_reseller_id(Request $request, $reseller_id)
    {
        // Validasi input (opsional, jika diperlukan)
        $request->validate([
            'reseller_id' => 'required|integer', // Pastikan reseller_id valid
        ]);

        // Mengambil data transaksi nitip barang berdasarkan reseller_id
        $items = transaksi_nitip_barang::where('reseller_id', $reseller_id)
            ->with(['transaksi_nitip_barang_detail.stok_barang.barang', 'reseller']) // Memuat relasi
            ->get();

        // Transformasi data
        $data = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'users_id' => $item->users_id,
                'reseller_id' => $item->reseller_id,
                'reseller_nama' => optional($item->reseller)->nama ?? null, // Mengambil nama reseller
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'details' => $item->transaksi_nitip_barang_detail->map(function ($detail) {
                    return [
                        'stok_barang_id' => $detail->stok_barang_id,
                        'nama_barang' => optional($detail->stok_barang->barang)->nama ?? null, // Mengambil nama barang
                        'qty' => $detail->qty,
                        'harga' => $detail->harga,
                    ];
                }),
            ];
        });

        // Mengembalikan respons JSON
        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function get_where_reseller_id_per_barang(Request $request, $reseller_id)
    {
        // Mengambil data transaksi nitip barang berdasarkan reseller_id
        $items = transaksi_nitip_barang::where('reseller_id', $reseller_id)
            ->with(['transaksi_nitip_barang_detail.stok_barang.barang', 'reseller']) // Memuat relasi
            ->get();

        // Proses pengelompokan dan penjumlahan qty per barang
        $groupedData = [];
        foreach ($items as $item) {
            foreach ($item->transaksi_nitip_barang_detail as $detail) {
                $barangId = $detail->stok_barang->barang->id ?? null;
                $namaBarang = $detail->stok_barang->barang->nama ?? null;

                if ($barangId && $namaBarang) {
                    if (!isset($groupedData[$barangId])) {
                        $groupedData[$barangId] = [
                            'barang_id' => $barangId,
                            'nama_barang' => $namaBarang,
                            'total_qty' => 0,
                            'harga' => $detail->harga, // Asumsi harga tetap sama untuk barang yang sama
                        ];
                    }
                    $groupedData[$barangId]['total_qty'] += $detail->qty;
                }
            }
        }

        // Konversi array ke format yang lebih bersih
        $result = array_values($groupedData);

        // Mengembalikan respons JSON
        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200);
    }

    public function get_where_reseller_id_per_stok_barang(Request $request, $reseller_id)
    {
        // Mengambil data transaksi nitip barang berdasarkan reseller_id
        $items = transaksi_nitip_barang::where('reseller_id', $reseller_id)
            ->with(['transaksi_nitip_barang_detail.stok_barang.barang', 'reseller']) // Memuat relasi
            ->get();

        // Proses pengelompokan dan penjumlahan qty per stok_barang
        $groupedData = [];
        foreach ($items as $item) {
            foreach ($item->transaksi_nitip_barang_detail as $detail) {
                $stokBarangId = $detail->stok_barang_id ?? null;
                $namaBarang = $detail->stok_barang->barang->nama ?? null;

                if ($stokBarangId && $namaBarang) {
                    if (!isset($groupedData[$stokBarangId])) {
                        $groupedData[$stokBarangId] = [
                            'stok_barang_id' => $stokBarangId,
                            'nama_barang' => $namaBarang,
                            'total_qty' => 0,
                            'harga' => $detail->harga, // Asumsi harga tetap sama untuk barang yang sama
                        ];
                    }
                    $groupedData[$stokBarangId]['total_qty'] += $detail->qty;
                }
            }
        }

        // Konversi array ke format yang lebih bersih
        $result = array_values($groupedData);

        // Urutkan hasil berdasarkan nama_barang (asc) dan stok_barang_id (asc)
        usort($result, function ($a, $b) {
            if ($a['nama_barang'] === $b['nama_barang']) {
                return $a['stok_barang_id'] <=> $b['stok_barang_id'];
            }
            return strcmp($a['nama_barang'], $b['nama_barang']);
        });

        // Mengembalikan respons JSON
        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reseller_id'   => 'required',
            // 'jenis'   => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'data' => $validator->errors(),
            ], 422);
        }

        // Mulai transaksi database
        DB::beginTransaction();

        try {
            // Periksa apakah tgl_transaksi_nitip_barang ada dalam request dan tidak kosong/null
            $tglTransaksiNitipBarang = $request->filled('tgl_transaksi_nitip_barang')
                ? $request->tgl_transaksi_nitip_barang
                : now()->format('Y-m-d\TH:i'); // Format tanggal sesuai contoh: 2025-01-28T14:04

            $data_id = DB::table('transaksi_nitip_barang')->insertGetId(
                array(
                    'reseller_id'     =>   $request->reseller_id,
                    'tgl_transaksi_nitip_barang'  => $tglTransaksiNitipBarang,
                    'status'     =>  "Aktif",
                    'users_id'     =>    Auth::guard()->user()->id,
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                )
            );

            // Loop untuk menyimpan setiap detail barang ke tabel transaksi_nitip_barang_detail
            foreach ($request->detail_barang as $detail) {
                DB::table('transaksi_nitip_barang_detail')->insert([
                    'stok_barang_id' => $detail['id'], // ID dari detail barang
                    'harga' => $detail['harga'], // Harga barang
                    'qty' => $detail['qty'], // Jumlah barang
                    'transaksi_nitip_barang_id' => $data_id, // Relasi ke transaksi_nitip_barang
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Commit transaksi jika semua operasi berhasil
            DB::commit();

            // Kembalikan respons JSON dengan data yang berhasil disimpan
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'data' => [
                    'transaksi_nitip_barang_id' => $data_id,
                    'detail_barang' => $request->detail_barang,
                ],
            ], 200);
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi kesalahan
            DB::rollBack();

            // Kembalikan respons JSON dengan pesan error
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
