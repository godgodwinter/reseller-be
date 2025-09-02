<?php

namespace App\Services;

use App\Models\transaksi_nitip_barang;
use App\Models\transaksi_nitip_barang_detail;
use Illuminate\Support\Facades\Auth;

class ResellerService
{
    /**
     * Ambil stok barang yang dititipkan ke reseller.
     * Dikelompokkan per barang_id, dengan detail dan total.
     * Bisa diurutkan berdasarkan berbagai kriteria, termasuk per nota terbaru.
     *
     * @param array $filters (sort_by, sort_direction)
     * @param int $resellerId ID dari reseller
     * @return array
     */
    public function fn_reseller_getStokBarang(array $filters = [], int $reseller_id)
    {
        if (!$reseller_id) {
            return [
                'success' => false,
                'message' => 'Invalid reseller ID.',
                'data' => []
            ];
        }

        // Filter sorting
        $sort_by = $filters['sort_by'] ?? $filters['sortBy'] ?? 'barang_id';
        $sort_direction = $filters['sort_direction'] ?? $filters['sortDirection'] ?? 'asc';

        // Kolom yang diizinkan untuk sorting
        $valid_sort_columns = [
            'barang_id',
            'nama',
            'kategori_barang_id',
            'total_jml',
            'total_transaksi',
            'per_nota' // urutkan berdasarkan nota terbaru
        ];

        if (!in_array($sort_by, $valid_sort_columns)) {
            return [
                'success' => false,
                'message' => 'Invalid sort column. Allowed: ' . implode(', ', $valid_sort_columns),
                'data' => []
            ];
        }

        // Ambil ID transaksi aktif milik reseller
        $transaksiIds = transaksi_nitip_barang::where('reseller_id', $reseller_id)
            ->where('status', 'Aktif')
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($transaksiIds->isEmpty()) {
            return [
                'success' => true,
                'data' => []
            ];
        }

        // Ambil data detail + relasi barang dan stok
        $items = transaksi_nitip_barang_detail::select([
            'transaksi_nitip_barang_detail.id as detail_id',
            'transaksi_nitip_barang_detail.transaksi_nitip_barang_id', // untuk sort per nota
            'transaksi_nitip_barang_detail.stok_barang_id as id',
            'transaksi_nitip_barang_detail.qty as jml',
            'transaksi_nitip_barang_detail.harga',
            'stok_barang.barang_id',
            'barang.nama',
            'barang.kategori_barang_id',
            'stok_barang.harga as harga_asli',
            'stok_barang.created_at as stok_created_at',
            'stok_barang.updated_at as stok_updated_at',
        ])
            ->join('stok_barang', 'transaksi_nitip_barang_detail.stok_barang_id', '=', 'stok_barang.id')
            ->join('barang', 'stok_barang.barang_id', '=', 'barang.id')
            ->whereIn('transaksi_nitip_barang_detail.transaksi_nitip_barang_id', $transaksiIds)
            ->whereNull('transaksi_nitip_barang_detail.deleted_at')
            ->whereNull('stok_barang.deleted_at')
            ->whereNull('barang.deleted_at')
            ->where('stok_barang.status', 'Aktif')
            ->get();

        if ($items->isEmpty()) {
            return [
                'success' => true,
                'data' => []
            ];
        }

        // Kelompokkan per barang_id
        $grouped = $items->groupBy('barang_id')->map(function ($group) {
            $first = $group->first();

            return [
                'barang_id' => $first->barang_id,
                'nama' => $first->nama,
                'kategori_barang_id' => $first->kategori_barang_id,
                'total_jml' => $group->sum('jml'),
                'total_transaksi' => $group->count(),
                'details' => $group->map(function ($item) {
                    return [
                        'detail_id' => $item->detail_id,
                        'id' => $item->id,
                        'jml' => $item->jml,
                        'harga' => $item->harga,
                        'harga_asli' => $item->harga_asli,
                        'stok_created_at' => $item->stok_created_at,
                        'stok_updated_at' => $item->stok_updated_at,
                        'transaksi_nitip_barang_id' => $item->transaksi_nitip_barang_id,
                    ];
                })->values()->all(),
            ];
        })->values();

        // Sorting berdasarkan kolom yang diminta
        switch ($sort_by) {
            case 'barang_id':
                $grouped = $sort_direction === 'asc'
                    ? $grouped->sortBy('barang_id')
                    : $grouped->sortByDesc('barang_id');
                break;

            case 'nama':
                $grouped = $sort_direction === 'asc'
                    ? $grouped->sortBy('nama')
                    : $grouped->sortByDesc('nama');
                break;

            case 'kategori_barang_id':
                $grouped = $sort_direction === 'asc'
                    ? $grouped->sortBy('kategori_barang_id')
                    : $grouped->sortByDesc('kategori_barang_id');
                break;

            case 'total_jml':
                $grouped = $sort_direction === 'asc'
                    ? $grouped->sortBy('total_jml')
                    : $grouped->sortByDesc('total_jml');
                break;

            case 'total_transaksi':
                $grouped = $sort_direction === 'asc'
                    ? $grouped->sortBy('total_transaksi')
                    : $grouped->sortByDesc('total_transaksi');
                break;

            case 'per_nota':
                // Urutkan berdasarkan transaksi_nitip_barang_id terbesar (terbaru) dari detail
                $grouped = $grouped->map(function ($barang) {
                    $barang['latest_transaksi_id'] = collect($barang['details'])->max('transaksi_nitip_barang_id');
                    return $barang;
                });

                $grouped = $sort_direction === 'asc'
                    ? $grouped->sortBy('latest_transaksi_id')
                    : $grouped->sortByDesc('latest_transaksi_id');

                // Hapus field tambahan setelah sorting
                $grouped = $grouped->map(function ($barang) {
                    unset($barang['latest_transaksi_id']);
                    return $barang;
                });
                break;
        }

        // Reset index dan kembalikan sebagai array
        return [
            'success' => true,
            'data' => $grouped->values()->all()
        ];
    }
}
