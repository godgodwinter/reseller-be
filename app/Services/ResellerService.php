<?php

namespace App\Services;

use App\Models\barang;
use App\Models\stok_barang;
use App\Models\transaksi_nitip_barang;
use App\Models\transaksi_nitip_barang_detail;
use App\Models\transaksi_retur_barang;
use App\Models\transaksi_setor_barang;
use App\Models\transaksi_setor_barang_detail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResellerService
{

    public function fn_reseller_getStokBarang_ByBarangId_v2(array $filters = [], int $reseller_id, ?int $barang_id = null)
    {
        if (!$barang_id) {
            return [
                'success' => false,
                'message' => 'barang_id is required.',
                'data' => null
            ];
        }

        // Filter sorting
        $sort_by = $filters['sort_by'] ?? $filters['sortBy'] ?? 'id'; // sekarang default ke 'id' (dulu detail_id)
        $sort_direction = $filters['sort_direction'] ?? $filters['sortDirection'] ?? 'asc';

        // Kolom yang diizinkan untuk sorting (sesuaikan dengan nama field baru)
        $valid_sort_columns = [
            'id', // baru: dari detail_id
            'nitipdetail_id', // tambahkan jika ingin bisa sort by ID asli detail
            'stok_id', // baru: dari id sebelumnya (stok_barang.id)
            'jml',
            'harga',
            'harga_asli',
            'stok_created_at',
            'stok_updated_at',
            'transaksi_nitip_barang_id',
            'nama'
        ];

        // Jika sort_by tidak valid, gunakan default
        if (!in_array($sort_by, $valid_sort_columns)) {
            $sort_by = 'id'; // fallback aman
        }

        // Ambil ID transaksi aktif milik reseller
        $transaksiIds = transaksi_nitip_barang::where('reseller_id', $reseller_id)
            ->where('status', 'Aktif')
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($transaksiIds->isEmpty()) {
            return [
                'success' => true,
                'data' => null
            ];
        }

        // Ambil detail stok hanya untuk barang_id tertentu
        $items = transaksi_nitip_barang_detail::select([
            'transaksi_nitip_barang_detail.id as nitipdetail_id', // simpan asli detail.id
            'transaksi_nitip_barang_detail.id as id', // akan jadi 'id' baru
            'transaksi_nitip_barang_detail.stok_barang_id as stok_id', // ganti nama kolom
            'transaksi_nitip_barang_detail.qty as jml',
            'transaksi_nitip_barang_detail.harga',
            'stok_barang.barang_id',
            'barang.nama',
            'barang.kategori_barang_id',
            'stok_barang.harga as harga_asli',
            'stok_barang.created_at as stok_created_at',
            'stok_barang.updated_at as stok_updated_at',
            'transaksi_nitip_barang_detail.transaksi_nitip_barang_id',
        ])
            ->join('stok_barang', 'transaksi_nitip_barang_detail.stok_barang_id', '=', 'stok_barang.id')
            ->join('barang', 'stok_barang.barang_id', '=', 'barang.id')
            ->whereIn('transaksi_nitip_barang_detail.transaksi_nitip_barang_id', $transaksiIds)
            ->where('stok_barang.barang_id', $barang_id)
            ->whereNull('transaksi_nitip_barang_detail.deleted_at')
            ->whereNull('stok_barang.deleted_at')
            ->whereNull('barang.deleted_at')
            ->where('stok_barang.status', 'Aktif')
            ->get();

        if ($items->isEmpty()) {
            return [
                'success' => true,
                'data' => null
            ];
        }

        $first = $items->first();

        $result = [
            'barang_id' => $first->barang_id,
            'nama' => $first->nama,
            'kategori_barang_id' => $first->kategori_barang_id,
            'total_jml' => $items->sum('jml'),
            'total_transaksi' => $items->count(),
            'details' => $items->map(function ($item) {
                return [
                    'id' => $item->id, // dari transaksi_nitip_barang_detail.id
                    'nitipdetail_id' => $item->nitipdetail_id, // tetap simpan aslinya
                    'stok_id' => $item->stok_id, // dari stok_barang.id
                    'jml' => $item->jml,
                    'harga' => $item->harga,
                    'harga_asli' => $item->harga_asli,
                    'stok_created_at' => $item->stok_created_at,
                    'stok_updated_at' => $item->stok_updated_at,
                    'transaksi_nitip_barang_id' => $item->transaksi_nitip_barang_id,
                    'nama' => $item->nama,
                ];
            })->values()->all(),
        ];

        // Sorting pada details
        $sortedDetails = collect($result['details']);

        $sortedDetails = $sort_direction === 'asc'
            ? $sortedDetails->sortBy($sort_by)
            : $sortedDetails->sortByDesc($sort_by);

        $result['details'] = $sortedDetails->values()->all();

        if (isset($result['details']) && is_array($result['details'])) {
            foreach ($result['details'] as &$detail) {
                // Ambil nitipdetail_id
                $nitipDetailId = is_object($detail)
                    ? ($detail->nitipdetail_id ?? null)
                    : ($detail['nitipdetail_id'] ?? null);

                if (!$nitipDetailId) {
                    // Jika tidak ada ID, set default
                    if (is_object($detail)) {
                        $detail->jml_setor = 0;
                        $detail->jml_saat_ini = 0;
                    } else {
                        $detail['jml_setor'] = 0;
                        $detail['jml_saat_ini'] = 0;
                    }
                    continue;
                }

                // Query: hanya yang status_konfirmasi = 'Disetujui'
                $totalSetor = transaksi_setor_barang_detail::where('transaksi_nitip_barang_detail_id', $nitipDetailId)
                    ->where('status_konfirmasi', 'Disetujui')
                    ->sum('qty'); // Langsung gunakan sum() untuk efisiensi

                // Ambil nilai jml, konversi ke angka
                $jml = is_object($detail)
                    ? (float) ($detail->jml ?? 0)
                    : (float) ($detail['jml'] ?? 0);

                // Hitung sisa
                $jmlSaatIni = $jml - $totalSetor;

                // Masukkan ke detail, sesuaikan tipe
                if (is_object($detail)) {
                    $detail->jml_setor = (float) $totalSetor;
                    $detail->jml_saat_ini = (float) $jmlSaatIni;
                } else {
                    $detail['jml_setor'] = (float) $totalSetor;
                    $detail['jml_saat_ini'] = (float) $jmlSaatIni;
                }
            }
        }

        return [
            'success' => true,
            'data' => $result
        ];
    }

    public function fn_reseller_getStokBarang_v2(array $filters = [], int $reseller_id)
    {
        if (!$reseller_id) {
            return [
                'success' => false,
                'message' => 'Invalid reseller ID.',
                'data' => []
            ];
        }

        $sort_by = $filters['sort_by'] ?? $filters['sortBy'] ?? 'barang_id';
        $sort_direction = $filters['sort_direction'] ?? $filters['sortDirection'] ?? 'asc';

        $valid_sort_columns = [
            'barang_id',
            'nama',
            'kategori_barang_id',
            'total_jml',
            'total_transaksi',
            'per_nota'
        ];

        if (!in_array($sort_by, $valid_sort_columns)) {
            return [
                'success' => false,
                'message' => 'Invalid sort column. Allowed: ' . implode(', ', $valid_sort_columns),
                'data' => []
            ];
        }

        $transaksiIds = DB::table('transaksi_nitip_barang')
            ->where('reseller_id', $reseller_id)
            ->where('status', 'Aktif')
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($transaksiIds->isEmpty()) {
            return ['success' => true, 'data' => []];
        }

        $items = DB::table('transaksi_nitip_barang_detail')
            ->select([
                'transaksi_nitip_barang_detail.id as nitipdetail_id',
                'transaksi_nitip_barang_detail.id as id',
                'transaksi_nitip_barang_detail.stok_barang_id as stok_id',
                'transaksi_nitip_barang_detail.transaksi_nitip_barang_id',
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
            return ['success' => true, 'data' => []];
        }

        $grouped = $items->groupBy('barang_id')->map(function ($group) {
            $first = $group->first();

            $details = $group->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nitipdetail_id' => $item->nitipdetail_id,
                    'stok_id' => $item->stok_id,
                    'jml' => $item->jml,
                    'harga' => $item->harga,
                    'harga_asli' => $item->harga_asli,
                    'stok_created_at' => $item->stok_created_at,
                    'stok_updated_at' => $item->stok_updated_at,
                    'transaksi_nitip_barang_id' => $item->transaksi_nitip_barang_id,
                ];
            })->values()->all();

            // Tambahkan jml_setor & jml_saat_ini di setiap detail
            foreach ($details as &$detail) {
                $nitipDetailId = $detail['nitipdetail_id'] ?? null;

                if (!$nitipDetailId) {
                    $detail['jml_setor'] = 0;
                    $detail['jml_saat_ini'] = 0;
                    continue;
                }

                $jmlSetor = DB::table('transaksi_setor_barang_detail')
                    ->where('transaksi_nitip_barang_detail_id', $nitipDetailId)
                    ->where('status_konfirmasi', 'Disetujui')
                    ->sum('qty');

                $jml = (float)($detail['jml'] ?? 0);
                $jmlSaatIni = $jml - $jmlSetor;

                $detail['jml_setor'] = (float) $jmlSetor;
                $detail['jml_saat_ini'] = (float) $jmlSaatIni;
            }

            return [
                'barang_id' => $first->barang_id,
                'nama' => $first->nama,
                'kategori_barang_id' => $first->kategori_barang_id,
                'total_jml' => collect($details)->sum('jml_saat_ini'), // opsional: total sisa stok
                'total_transaksi' => count($details),
                'details' => $details,
            ];
        })->values();

        // Sorting
        switch ($sort_by) {
            case 'barang_id':
                $grouped = $sort_direction === 'asc' ? $grouped->sortBy('barang_id') : $grouped->sortByDesc('barang_id');
                break;
            case 'nama':
                $grouped = $sort_direction === 'asc' ? $grouped->sortBy('nama') : $grouped->sortByDesc('nama');
                break;
            case 'kategori_barang_id':
                $grouped = $sort_direction === 'asc' ? $grouped->sortBy('kategori_barang_id') : $grouped->sortByDesc('kategori_barang_id');
                break;
            case 'total_jml':
                $grouped = $sort_direction === 'asc' ? $grouped->sortBy('total_jml') : $grouped->sortByDesc('total_jml');
                break;
            case 'total_transaksi':
                $grouped = $sort_direction === 'asc' ? $grouped->sortBy('total_transaksi') : $grouped->sortByDesc('total_transaksi');
                break;
            case 'per_nota':
                $grouped = $grouped->map(function ($barang) {
                    $barang['latest_transaksi_id'] = collect($barang['details'])->max('transaksi_nitip_barang_id');
                    return $barang;
                });

                $grouped = $sort_direction === 'asc'
                    ? $grouped->sortBy('latest_transaksi_id')
                    : $grouped->sortByDesc('latest_transaksi_id');

                $grouped = $grouped->map(function ($barang) {
                    unset($barang['latest_transaksi_id']);
                    return $barang;
                });
                break;
        }

        return [
            'success' => true,
            'data' => $grouped->values()->all()
        ];
    }


    public function fn_validateStokTransaksiSetor_v2(array $details, int $reseller_id): array
    {
        if (empty($details)) {
            return [
                'success' => false,
                'message' => 'Detail barang tidak boleh kosong.',
                'errors' => []
            ];
        }

        $errors = [];
        $qtyPerNitipDetailId = []; // qty yang diminta dari input

        // 1. Validasi input & kelompokkan qty per nitipdetail_id
        foreach ($details as $index => $detail) {
            $nitipdetail_id = $detail['nitipdetail_id'] ?? null;
            $qty = $detail['qty'] ?? 0;

            if (!$nitipdetail_id) {
                $errors[] = "Baris #$index: nitipdetail_id tidak valid.";
                continue;
            }

            if (!is_numeric($qty) || $qty <= 0) {
                $errors[] = "Baris #$index: Qty harus angka positif.";
                continue;
            }

            $qtyPerNitipDetailId[$nitipdetail_id] = ($qtyPerNitipDetailId[$nitipdetail_id] ?? 0) + $qty;
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Validasi input gagal.',
                'errors' => $errors
            ];
        }

        // 2. Ambil transaksi titip aktif milik reseller
        $activeTransaksiIds = DB::table('transaksi_nitip_barang')
            ->where('reseller_id', $reseller_id)
            ->where('status', 'Aktif')
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($activeTransaksiIds->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Tidak ada transaksi titip aktif.',
                'errors' => ['Anda tidak memiliki transaksi titip aktif.']
            ];
        }

        // 3. Ambil stok awal (jml) dari transaksi_nitip_barang_detail
        $availableDetails = DB::table('transaksi_nitip_barang_detail as tnd')
            ->select([
                'tnd.id as nitipdetail_id',
                'tnd.qty as jml', // stok awal
                'tnd.stok_barang_id',
                'tnd.transaksi_nitip_barang_id',
                'sb.barang_id',
                'b.nama as barang_nama',
            ])
            ->join('stok_barang as sb', 'tnd.stok_barang_id', '=', 'sb.id')
            ->join('barang as b', 'sb.barang_id', '=', 'b.id')
            ->whereIn('tnd.transaksi_nitip_barang_id', $activeTransaksiIds)
            ->whereIn('tnd.id', array_keys($qtyPerNitipDetailId))
            ->where('sb.status', 'Aktif')
            ->whereNull('tnd.deleted_at')
            ->whereNull('sb.deleted_at')
            ->whereNull('b.deleted_at')
            ->get();

        if ($availableDetails->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Tidak ada detail transaksi yang valid.',
                'errors' => ['Detail transaksi tidak ditemukan.']
            ];
        }

        // 4. Hitung jml_setor (yang sudah disetujui) per nitipdetail_id
        $setorMap = DB::table('transaksi_setor_barang_detail as tsd')
            ->select('tsd.transaksi_nitip_barang_detail_id', DB::raw('SUM(tsd.qty) as total_qty'))
            ->whereIn('tsd.transaksi_nitip_barang_detail_id', $availableDetails->pluck('nitipdetail_id'))
            ->where('tsd.status_konfirmasi', 'Disetujui')
            ->groupBy('tsd.transaksi_nitip_barang_detail_id')
            ->pluck('total_qty', 'transaksi_nitip_barang_detail_id'); // [nitipdetail_id => total_qty]

        // 5. Buat map detail + hitung jml_saat_ini
        $detailMap = [];
        foreach ($availableDetails as $detail) {
            $jmlAwal = (float)$detail->jml;
            $jmlSetor = (float)($setorMap->get($detail->nitipdetail_id) ?? 0);
            $jmlSaatIni = $jmlAwal - $jmlSetor;

            $detailMap[$detail->nitipdetail_id] = (object) [
                'jml_saat_ini' => $jmlSaatIni,
                'jml_awal' => $jmlAwal,
                'barang_nama' => $detail->barang_nama,
                'transaksi_nitip_barang_id' => $detail->transaksi_nitip_barang_id,
            ];
        }

        // 6. Validasi: cek apakah qty diminta melebihi jml_saat_ini
        foreach ($qtyPerNitipDetailId as $nitipdetail_id => $qtyDiminta) {
            if (!isset($detailMap[$nitipdetail_id])) {
                $errors[] = "Detail transaksi titip ID $nitipdetail_id tidak ditemukan atau tidak tersedia.";
                continue;
            }

            $info = $detailMap[$nitipdetail_id];
            $qtyDiminta = (float)$qtyDiminta;

            if ($qtyDiminta > $info->jml_saat_ini) {
                $errors[] = "Stok barang '{$info->barang_nama}' dari transaksi titip #{$info->transaksi_nitip_barang_id} tidak mencukupi. " .
                    "Sisa stok yang bisa disetor: {$info->jml_saat_ini}, Diminta: $qtyDiminta.";
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Validasi stok gagal.',
                'errors' => $errors
            ];
        }

        return [
            'success' => true,
            'message' => 'Stok tersedia semua.',
            'errors' => []
        ];
    }

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
            'per_nota'
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
            'transaksi_nitip_barang_detail.id as nitipdetail_id', // simpan asli
            'transaksi_nitip_barang_detail.id as id', // jadi 'id' utama di detail
            'transaksi_nitip_barang_detail.stok_barang_id as stok_id', // ganti dari 'id' ke 'stok_id'
            'transaksi_nitip_barang_detail.transaksi_nitip_barang_id', // untuk sorting per nota
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
                        'id' => $item->id, // dari transaksi_nitip_barang_detail.id
                        'nitipdetail_id' => $item->nitipdetail_id, // tetap simpan
                        'stok_id' => $item->stok_id,
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
                // Ambil transaksi terbaru dari details
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

        return [
            'success' => true,
            'data' => $grouped->values()->all()
        ];
    }

    public function fn_reseller_getStokBarang_ByBarangId(array $filters = [], int $reseller_id, ?int $barang_id = null)
    {
        if (!$barang_id) {
            return [
                'success' => false,
                'message' => 'barang_id is required.',
                'data' => null
            ];
        }

        // Filter sorting
        $sort_by = $filters['sort_by'] ?? $filters['sortBy'] ?? 'id'; // sekarang default ke 'id' (dulu detail_id)
        $sort_direction = $filters['sort_direction'] ?? $filters['sortDirection'] ?? 'asc';

        // Kolom yang diizinkan untuk sorting (sesuaikan dengan nama field baru)
        $valid_sort_columns = [
            'id', // baru: dari detail_id
            'nitipdetail_id', // tambahkan jika ingin bisa sort by ID asli detail
            'stok_id', // baru: dari id sebelumnya (stok_barang.id)
            'jml',
            'harga',
            'harga_asli',
            'stok_created_at',
            'stok_updated_at',
            'transaksi_nitip_barang_id',
            'nama'
        ];

        // Jika sort_by tidak valid, gunakan default
        if (!in_array($sort_by, $valid_sort_columns)) {
            $sort_by = 'id'; // fallback aman
        }

        // Ambil ID transaksi aktif milik reseller
        $transaksiIds = transaksi_nitip_barang::where('reseller_id', $reseller_id)
            ->where('status', 'Aktif')
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($transaksiIds->isEmpty()) {
            return [
                'success' => true,
                'data' => null
            ];
        }

        // Ambil detail stok hanya untuk barang_id tertentu
        $items = transaksi_nitip_barang_detail::select([
            'transaksi_nitip_barang_detail.id as nitipdetail_id', // simpan asli detail.id
            'transaksi_nitip_barang_detail.id as id', // akan jadi 'id' baru
            'transaksi_nitip_barang_detail.stok_barang_id as stok_id', // ganti nama kolom
            'transaksi_nitip_barang_detail.qty as jml',
            'transaksi_nitip_barang_detail.harga',
            'stok_barang.barang_id',
            'barang.nama',
            'barang.kategori_barang_id',
            'stok_barang.harga as harga_asli',
            'stok_barang.created_at as stok_created_at',
            'stok_barang.updated_at as stok_updated_at',
            'transaksi_nitip_barang_detail.transaksi_nitip_barang_id',
        ])
            ->join('stok_barang', 'transaksi_nitip_barang_detail.stok_barang_id', '=', 'stok_barang.id')
            ->join('barang', 'stok_barang.barang_id', '=', 'barang.id')
            ->whereIn('transaksi_nitip_barang_detail.transaksi_nitip_barang_id', $transaksiIds)
            ->where('stok_barang.barang_id', $barang_id)
            ->whereNull('transaksi_nitip_barang_detail.deleted_at')
            ->whereNull('stok_barang.deleted_at')
            ->whereNull('barang.deleted_at')
            ->where('stok_barang.status', 'Aktif')
            ->get();

        if ($items->isEmpty()) {
            return [
                'success' => true,
                'data' => null
            ];
        }

        $first = $items->first();

        $result = [
            'barang_id' => $first->barang_id,
            'nama' => $first->nama,
            'kategori_barang_id' => $first->kategori_barang_id,
            'total_jml' => $items->sum('jml'),
            'total_transaksi' => $items->count(),
            'details' => $items->map(function ($item) {
                return [
                    'id' => $item->id, // dari transaksi_nitip_barang_detail.id
                    'nitipdetail_id' => $item->nitipdetail_id, // tetap simpan aslinya
                    'stok_id' => $item->stok_id, // dari stok_barang.id
                    'jml' => $item->jml,
                    'harga' => $item->harga,
                    'harga_asli' => $item->harga_asli,
                    'stok_created_at' => $item->stok_created_at,
                    'stok_updated_at' => $item->stok_updated_at,
                    'transaksi_nitip_barang_id' => $item->transaksi_nitip_barang_id,
                    'nama' => $item->nama,
                ];
            })->values()->all(),
        ];

        // Sorting pada details
        $sortedDetails = collect($result['details']);

        $sortedDetails = $sort_direction === 'asc'
            ? $sortedDetails->sortBy($sort_by)
            : $sortedDetails->sortByDesc($sort_by);

        $result['details'] = $sortedDetails->values()->all();

        return [
            'success' => true,
            'data' => $result
        ];
    }
    /**
     * Validasi stok transaksi setor barang: qty tidak melebihi stok per detail transaksi nitip
     *
     * @param array $details [ ['nitipdetail_id' => id_detail, 'qty' => jumlah] ]
     * @param int $reseller_id
     * @return array ['success' => bool, 'message' => string, 'errors' => array]
     */
    public function fn_validateStokTransaksiSetor(array $details, int $reseller_id): array
    {
        if (empty($details)) {
            return [
                'success' => false,
                'message' => 'Detail barang tidak boleh kosong.',
                'errors' => []
            ];
        }

        $errors = [];
        $qtyPerNitipDetailId = []; // qty diminta per nitipdetail_id

        // 1. Validasi input & kelompokkan qty per nitipdetail_id
        foreach ($details as $index => $detail) {
            $nitipdetail_id = $detail['nitipdetail_id'] ?? null;
            $qty = $detail['qty'] ?? 0;

            if (!$nitipdetail_id) {
                $errors[] = "Baris #$index: nitipdetail_id tidak valid.";
                continue;
            }

            if (!is_numeric($qty) || $qty <= 0) {
                $errors[] = "Baris #$index: Qty harus angka positif.";
                continue;
            }

            $qtyPerNitipDetailId[$nitipdetail_id] = ($qtyPerNitipDetailId[$nitipdetail_id] ?? 0) + $qty;
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Validasi input gagal.',
                'errors' => $errors
            ];
        }

        // 2. Ambil transaksi nitip aktif milik reseller
        $activeTransaksiIds = \App\Models\transaksi_nitip_barang::where('reseller_id', $reseller_id)
            ->where('status', 'Aktif')
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($activeTransaksiIds->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Tidak ada transaksi titip aktif.',
                'errors' => ['Anda tidak memiliki transaksi titip aktif.']
            ];
        }

        // 3. Ambil detail transaksi nitip yang valid
        $availableDetails = \App\Models\transaksi_nitip_barang_detail::select([
            'transaksi_nitip_barang_detail.id as nitipdetail_id',
            'transaksi_nitip_barang_detail.qty as jml',
            'transaksi_nitip_barang_detail.stok_barang_id',
            'transaksi_nitip_barang_detail.transaksi_nitip_barang_id',
            'stok_barang.barang_id',
            'barang.nama as barang_nama',
        ])
            ->join('stok_barang', 'transaksi_nitip_barang_detail.stok_barang_id', '=', 'stok_barang.id')
            ->join('barang', 'stok_barang.barang_id', '=', 'barang.id')
            ->whereIn('transaksi_nitip_barang_detail.transaksi_nitip_barang_id', $activeTransaksiIds)
            ->whereIn('transaksi_nitip_barang_detail.id', array_keys($qtyPerNitipDetailId))
            ->where('stok_barang.status', 'Aktif')
            ->whereNull('transaksi_nitip_barang_detail.deleted_at')
            ->whereNull('stok_barang.deleted_at')
            ->whereNull('barang.deleted_at')
            ->get();

        // Key by nitipdetail_id untuk lookup cepat
        $detailMap = $availableDetails->keyBy('nitipdetail_id');

        // 4. Validasi qty per nitipdetail_id
        foreach ($qtyPerNitipDetailId as $nitipdetail_id => $qtyDiminta) {
            $detailData = $detailMap->get($nitipdetail_id);

            if (!$detailData) {
                $errors[] = "Detail transaksi titip ID $nitipdetail_id tidak ditemukan atau tidak tersedia.";
                continue;
            }

            $tersedia = $detailData->jml;

            if ($qtyDiminta > $tersedia) {
                $errors[] = "Stok barang '{$detailData->barang_nama}' dari transaksi titip #{$detailData->transaksi_nitip_barang_id} tidak mencukupi. Tersedia: $tersedia, Diminta: $qtyDiminta.";
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Validasi stok gagal.',
                'errors' => $errors
            ];
        }

        return [
            'success' => true,
            'message' => 'Stok tersedia semua.',
            'errors' => []
        ];
    }
}
