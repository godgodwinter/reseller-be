<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\barang;
use App\Models\kategori_barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class adminBarangController extends Controller
{

    public function index(Request $request)
    {
        // Ambil kategori_barang_id dari request (opsional)
        $kategori_barang_id = $request->kategori_barang_id;

        // Query utama untuk mengambil data barang
        $query = Barang::with([
            'stok' => function ($q) {
                $q->where('status', 'Aktif'); // Hanya menghitung stok dengan status "Aktif"
            },
            'kategoriBarang', // Relasi kategori barang
            'stok.transaksiNitipBarangDetail.transaksiNitipBarang' // Relasi untuk transaksi nitip
        ])
            ->orderBy('nama', 'asc')
            ->whereNull("deleted_at")
            ->where("status", "Aktif");

        // Filter berdasarkan kategori_barang_id jika ada
        if ($kategori_barang_id) {
            $query->where("kategori_barang_id", $kategori_barang_id);
        }

        // Ambil data barang
        $items = $query->get()->map(function ($barang) {
            // Hitung total stok barang
            $barang->total_stok = $barang->stok->sum('jml');

            // Tambahkan nama kategori barang
            $barang->kategori_barang_nama = $barang->kategoriBarang->nama ?? null;

            // Proses data transaksi nitip
            $barang->transaksi_nitip = $barang->stok
                ->flatMap(function ($stok) {
                    return $stok->transaksiNitipBarangDetail->map(function ($detail) {
                        return [
                            'transaksi_nitip_barang_id' => $detail->transaksiNitipBarang->id,
                            'tanggal_transaksi' => $detail->transaksiNitipBarang->created_at,
                            'reseller_id' => $detail->transaksiNitipBarang->reseller_id,
                            'reseller_nama' => optional($detail->transaksiNitipBarang->reseller)->nama ?? null,
                            'stok_barang_id' => $detail->stok_barang_id,
                            'qty' => $detail->qty,
                            'harga' => $detail->harga,
                        ];
                    });
                })
                ->values(); // Konversi ke array numerik

            // Hitung total stok nitip di reseller
            $barang->total_stok_nitip_direseller = $barang->transaksi_nitip->sum('qty');

            return $barang;
        });

        // Kembalikan respons JSON
        return response()->json([
            'success' => true,
            'data' => $items,
        ], 200);
    }
    // public function index(Request $request)
    // {
    //     $kategori_barang_id = $request->kategori_barang_id;

    //     $query = Barang::with([
    //         'stok' => function ($q) {
    //             $q->where('status', 'Aktif'); // Hanya menghitung stok dengan status "Aktif"
    //         },
    //         'kategoriBarang' // Tambahkan relasi kategori barang
    //     ])
    //         ->orderBy('nama', 'asc')
    //         ->whereNull("deleted_at")
    //         ->where("status", "Aktif");

    //     if ($kategori_barang_id) {
    //         $query->where("kategori_barang_id", $kategori_barang_id);
    //     }

    //     $items = $query->get()->map(function ($barang) {
    //         $barang->total_stok = $barang->stok->sum('jml'); // Tambahkan total stok ke hasil
    //         $barang->kategori_barang_nama = $barang->kategoriBarang->nama ?? null; // Tambahkan kategori_barang_nama
    //         return $barang;
    //     });

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $items,
    //     ], 200);
    // }

    public function store(Request $request)
    {
        //set validation
        $validator = Validator::make($request->all(), [
            'nama'   => 'required',
            'kategori_barang_id'   => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'data' => $validator->errors(),
            ], 422);
        }
        $items = 'Data berhasil di tambahkan';
        // $data = $request->except('_token');
        // apiprobk::create($data);

        $data_id = DB::table('barang')->insertGetId(
            array(
                'nama'     =>   $request->nama,
                'kategori_barang_id'     =>   $request->kategori_barang_id,
                'img'     =>   $request->img,
                'status'     =>  $request->status ? $request->status : 'Aktif',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            )
        );

        return response()->json([
            'success'    => true,
            'data'    => $items,
            'id' => $data_id
        ], 200);
    }

    public function edit(barang $item)
    {
        // Muat relasi stok
        $item->load(['stok' => function ($q) {
            $q->where('status', 'Aktif'); // Hanya stok aktif
        }]);

        // Hitung total stok
        $item->total_stok = $item->stok->sum('jml');

        return response()->json([
            'success' => true,
            'data'    => $item,
        ], 200);
    }
    public function update(barang $item, Request $request)
    {

        //set validation
        $validator = Validator::make($request->all(), [
            'nama'   => 'required',
            // 'jenis'   => 'required',
        ]);
        //response error validation
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        barang::where('id', $item->id)
            ->update([
                'nama'     =>   $request->nama,
                'kategori_barang_id'     =>   $request->kategori_barang_id,
                'img'     =>   $request->img,
                'status'     =>  $request->status ? $request->status : 'Aktif',
                'updated_at' => date("Y-m-d H:i:s")
            ]);

        return response()->json([
            'success'    => true,
            'message'    => 'Data berhasil di update!',
            'id' => $item->id
        ], 200);
    }
    public function destroy(barang $item)
    {

        // kategori::destroy($item->id);
        // delete permanent
        barang::where('id', $item->id)->forcedelete();

        return response()->json([
            'success'    => true,
            'message'    => 'Data berhasil di hapus!',
        ], 200);
    }
}
