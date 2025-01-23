<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class adminResellerController extends Controller
{
    public function index(Request $request)
    {
        $items = Reseller::get();
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
            'email' => 'required',
            'password' => 'required',
            // 'nomeridentitas' => 'required',
            'username' => 'required',

        ]);
        // !ambil id dari admin yang login
        $pembuat_id = auth()->id();

        $items = 'Data berhasil di tambahkan';

        $user = Reseller::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'username' => $request->username,
            // 'nomeridentitas' => $request->nomeridentitas,
            'password' => Hash::make($request->password),
            'pembuat_id' => $pembuat_id,
        ]);

        return response()->json([
            'success'    => true,
            'data'    => $items,
            'id' => $user->id
        ], 200);
    }

    public function edit(Reseller $item)
    {
        return response()->json([
            'success'    => true,
            'data'    => $item,
        ], 200);
    }
    public function update(Reseller $item, Request $request)
    {

        //set validation
        $validator = Validator::make($request->all(), [
            'nama'   => 'required',
        ]);
        //response error validation
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        Reseller::where('id', $item->id)
            ->update([
                'nama' => $request->nama,
                'email' => $request->email,
                'username' => $request->username,
                // 'nomeridentitas' => $request->nomeridentitas,
                // 'password' => Hash::make($request->password),
                'updated_at' => date("Y-m-d H:i:s")
            ]);

        // update password
        if ($request->password) {
            Reseller::where('id', $item->id)
                ->update([
                    'password' => Hash::make($request->password),
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
        }

        if ($request->status_login) {
            Reseller::where('id', $item->id)
                ->update([
                    'status_login' => $request->status_login,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
        }

        return response()->json([
            'success'    => true,
            'message'    => 'Data berhasil di update!',
            'id' => $item->id
        ], 200);
    }
    public function updatePassword(Reseller $item, Request $request)
    {
        // Validasi input hanya untuk password
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            // 'password' => 'required|min:8|confirmed',
        ]);

        // Jika validasi gagal, kembalikan respons error
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Perbarui password
        $item->update([
            'password' => Hash::make($request->password),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui!',
            'id' => $item->id
        ], 200);
    }

    // public function destroy(Reseller $item)
    // {

    //     Reseller::destroy($item->id);
    //     return response()->json([
    //         'success'    => true,
    //         'message'    => 'Data berhasil di hapus!',
    //     ], 200);
    // }
    public function destroy($item)
    {

        Reseller::where('id', $item)->forcedelete();
        return response()->json([
            'success'    => true,
            'message'    => 'Data berhasil di hapus!',
        ], 200);
    }
}
