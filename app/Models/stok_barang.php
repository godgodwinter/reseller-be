<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class stok_barang extends Model
{
    public $table = "stok_barang";

    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];

    public static function boot()
    {
        parent::boot();
    }
}
