<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class transaksi_nitip_barang_detail extends Model
{
    public $table = "transaksi_nitip_barang_detail";

    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];


    public function stok_barang()
    {
        return $this->belongsTo(stok_barang::class);
    }

    public function transaksiNitipBarang()
    {
        return $this->belongsTo(transaksi_nitip_barang::class);
    }

    public static function boot()
    {
        parent::boot();
    }
}
