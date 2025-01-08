<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class transaksi_nitip_barang extends Model
{
    public $table = "transaksi_nitip_barang";

    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];

    public static function boot()
    {
        parent::boot();
    }
}
