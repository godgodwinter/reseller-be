<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class transaksi_setor_barang extends Model
{
    public $table = "transaksi_setor_barang";

    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];

    public function reseller()
    {
        return $this->belongsTo(reseller::class);
    }

    public function transaksi_setor_barang_detail()
    {
        return $this->hasMany(transaksi_setor_barang_detail::class);
    }

    public function details()
    {
        return $this->hasMany(transaksi_setor_barang_detail::class, 'transaksi_setor_barang_id');
    }

    public static function boot()
    {
        parent::boot();
    }
}
