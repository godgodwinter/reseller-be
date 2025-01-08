<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class kategori_barang extends Model
{
    public $table = "kategori_barang";

    use SoftDeletes;
    use HasFactory;

    // protected $fillable = [
    //     'nama',
    //     'sekolah_id',
    //     'walikelas_id',
    // ];


    protected $guarded = [];


    // public function transaksi()
    // {
    //     return $this->hasMany('App\Models\transaksi');
    // }

    public static function boot()
    {
        parent::boot();
    }
}
