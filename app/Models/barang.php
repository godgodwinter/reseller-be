<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class barang extends Model
{
    public $table = "barang";

    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];


    public function kategori_barang()
    {
        return $this->belongsTo('App\Models\kategori_barang');
    }

    // public function users()
    // {
    //     return $this->belongsTo('App\Models\User', 'users_id', 'id');
    // }
    public static function boot()
    {
        parent::boot();
    }
}
