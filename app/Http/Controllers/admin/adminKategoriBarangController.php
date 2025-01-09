<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\kategori_barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class adminKategoriBarangController extends Controller
{
    // public function get_jenis(Request $request)
    // {
    //     $jenis = $request->jenis ? $request->jenis : "Pengeluaran";
    //     // dd($jenis);
    //     $items = kategori_barang::where("jenis", $jenis)->whereNull("deleted_at")->orderBy('nama', 'asc')->get();
    //     // $items = kategori_barang::where("jenis", $jenis)->whereNull("deleted_at")->orderBy('nama', 'asc')->get();
    //     return response()->json([
    //         'success'    => true,
    //         'data'    => $items,
    //     ], 200);
    // }

    public function index(Request $request)
    {
        $jenis = $request->jenis ? $request->jenis : null;
        if ($jenis) {
            $items = kategori_barang::orderBy('nama', 'asc')
                ->whereNull("deleted_at")
                // ->where("jenis", $jenis)
                ->get();
        } else {
            $items = kategori_barang::orderBy('nama', 'asc')
                ->whereNull("deleted_at")
                // ->whereIn("jenis", ["Pengeluaran", "Pemasukan"])
                ->get();
        }
        return response()->json([
            'success'    => true,
            'data'    => $items,
        ], 200);
    }

    public function store(Request $request)
    {
        //set validation
        $validator = Validator::make($request->all(), [
            'nama'   => 'required',
            // 'jenis'   => 'required',
        ]);

        $items = 'Data berhasil di tambahkan';
        // $data = $request->except('_token');
        // apiprobk::create($data);

        $data_id = DB::table('kategori_barang')->insertGetId(
            array(
                'nama'     =>   $request->nama,
                'img'     =>   $request->img,
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

    public function edit(kategori_barang $item)
    {
        return response()->json([
            'success'    => true,
            'data'    => $item,
        ], 200);
    }
    public function update(kategori_barang $item, Request $request)
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

        kategori_barang::where('id', $item->id)
            ->update([
                'nama'     =>   $request->nama,
                'img'     =>   $request->img,
                'updated_at' => date("Y-m-d H:i:s")
            ]);

        return response()->json([
            'success'    => true,
            'message'    => 'Data berhasil di update!',
            'id' => $item->id
        ], 200);
    }
    public function destroy(kategori_barang $item)
    {

        // kategori::destroy($item->id);
        // delete permanent
        kategori_barang::where('id', $item->id)->forcedelete();

        return response()->json([
            'success'    => true,
            'message'    => 'Data berhasil di hapus!',
        ], 200);
    }
}
