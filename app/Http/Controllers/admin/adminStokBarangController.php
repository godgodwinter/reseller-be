<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\barang;
use App\Models\kategori_barang;
use App\Models\stok_barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class adminStokBarangController extends Controller
{
    public function index(Request $request)
    {
        $barang_id = $request->barang_id ? $request->barang_id : null;
        $sort_by = $request->sort_by ? $request->sort_by : 'barang_id'; // Default sorting by 'barang_id'
        $sort_direction = $request->sort_direction ? $request->sort_direction : 'asc'; // Default sorting direction is ascending

        // Validasi kolom yang dapat digunakan untuk sorting
        $valid_sort_columns = ['jml', 'harga', 'barang_id', 'kategori_barang_id'];
        if (!in_array($sort_by, $valid_sort_columns)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid sort column. Allowed values: jml, harga, barang_id, kategori_barang_id.',
            ], 400);
        }

        // Query
        $query = stok_barang::select('stok_barang.*') // Pilih kolom dari tabel stok_barang
            ->whereNull('stok_barang.deleted_at') // Gunakan alias tabel untuk menghindari ambiguitas
            ->where('stok_barang.status', 'Aktif')
            ->whereNotNull('stok_barang.barang_id')
            ->with('barang'); // Load relasi barang

        if ($barang_id) {
            $query->where('stok_barang.barang_id', $barang_id); // Tambahkan alias tabel
        }

        // Sorting berdasarkan kategori_barang_id
        if ($sort_by === 'kategori_barang_id') {
            $query->join('barang', 'stok_barang.barang_id', '=', 'barang.id')
                ->whereNull('barang.deleted_at') // Pastikan `deleted_at` dari tabel barang dihandle
                ->orderBy('barang.kategori_barang_id', $sort_direction);
        } else {
            $query->orderBy($sort_by, $sort_direction);
        }

        // Tambahkan distinct untuk menghilangkan duplikasi
        $items = $query->distinct()->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ], 200);
    }



    public function store(Request $request)
    {
        //set validation
        $validator = Validator::make($request->all(), [
            'jml'   => 'required',
            'harga'   => 'required',
            'barang_id'   => 'required',
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

        $data_id = DB::table('stok_barang')->insertGetId(
            array(
                'jml'     =>   $request->jml,
                'harga'     =>   $request->harga,
                'barang_id'     =>   $request->barang_id,
                'ket'     =>  $request->ket ? $request->ket : null,
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

    public function edit(stok_barang $item)
    {
        return response()->json([
            'success'    => true,
            'data'    => $item,
        ], 200);
    }
    public function update(stok_barang $item, Request $request)
    {

        //set validation
        $validator = Validator::make($request->all(), [
            'jml'   => 'required',
            'harga'   => 'required',
            'barang_id'   => 'required',
        ]);
        //response error validation
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        stok_barang::where('id', $item->id)
            ->update([
                'jml'     =>   $request->jml,
                'harga'     =>   $request->harga,
                'barang_id'     =>   $request->barang_id,
                'ket'     =>  $request->ket ? $request->ket : null,
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
    public function destroy(stok_barang $item)
    {

        // kategori::destroy($item->id);
        // delete permanent
        stok_barang::where('id', $item->id)->forcedelete();

        return response()->json([
            'success'    => true,
            'message'    => 'Data berhasil di hapus!',
        ], 200);
    }
}
