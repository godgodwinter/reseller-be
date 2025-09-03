<?php

namespace App\Services;

use App\Models\transaksi_nitip_barang;
use App\Models\transaksi_nitip_barang_detail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminService
{

    /**
     * Admin: Update status konfirmasi transaksi setor barang & detailnya
     *
     * @param int $transaksi_id
     * @param string $status_konfirmasi 'Belum', 'Disetujui', 'Ditolak'
     * @param string|null $keterangan_konfirmasi Keterangan umum (untuk header & semua detail)
     * @param int|null $admin_user_id ID admin (opsional)
     * @return array
     */
    public function admin_updateStatusKonfirmasiSetor(
        int $transaksi_id,
        string $status_konfirmasi,
        ?string $keterangan_konfirmasi = null,
        ?int $admin_user_id = null
    ): array {
        // Validasi status
        $validStatus = ['Belum', 'Disetujui', 'Ditolak'];
        if (!in_array($status_konfirmasi, $validStatus)) {
            return [
                'success' => false,
                'message' => 'Status konfirmasi tidak valid. Harus: ' . implode(', ', $validStatus),
            ];
        }

        // Cek transaksi utama
        $transaksi = DB::table('transaksi_setor_barang')
            ->where('id', $transaksi_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$transaksi) {
            return [
                'success' => false,
                'message' => 'Transaksi setor barang tidak ditemukan.',
            ];
        }

        try {
            DB::beginTransaction();

            // Data update untuk transaksi utama
            $updateTransaksi = [
                'status_konfirmasi' => $status_konfirmasi,
                'keterangan_konfirmasi' => $keterangan_konfirmasi,
                'tgl_konfirmasi' => now(),
                'updated_at' => now(),
            ];


            // Update transaksi utama
            DB::table('transaksi_setor_barang')
                ->where('id', $transaksi_id)
                ->update($updateTransaksi);

            // Update semua detail yang terkait
            $updateDetail = [
                'status_konfirmasi' => $status_konfirmasi,
                'keterangan_konfirmasi' => $keterangan_konfirmasi, // bisa diganti per-detail nanti jika butuh
                'updated_at' => now(),
            ];

            DB::table('transaksi_setor_barang_detail')
                ->where('transaksi_setor_barang_id', $transaksi_id)
                ->whereNull('deleted_at')
                ->update($updateDetail);
            DB::commit();
            return [
                'success' => true,
                'message' => 'Status konfirmasi berhasil diperbarui (termasuk detail barang).',
                'data' => [
                    'transaksi_setor_barang_id' => $transaksi_id,
                    'status_konfirmasi' => $status_konfirmasi,
                    'total_detail_updated' => DB::table('transaksi_setor_barang_detail')
                        ->where('transaksi_setor_barang_id', $transaksi_id)
                        ->whereNull('deleted_at')
                        ->count(),
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal update status konfirmasi setor barang & detail: ' . $e->getMessage(), [
                'transaksi_id' => $transaksi_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui status.',
                'error' => $e->getMessage(),
            ];
        }
    }
}
