<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class reseller extends Authenticatable implements JWTSubject
{
    // use HasApiTokens, HasFactory, Notifiable;
    // protected $connection = 'mysql';
    public $table = "reseller";

    use SoftDeletes;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */




    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    // public function siswadetail()
    // {
    //     return $this->hasMany('App\Models\siswadetail')->with('apiprobk');
    // }
    // public function sekolah()
    // {
    //     return $this->belongsTo('App\Models\sekolah')->with('paket');
    // }

    // public function siswadetailwithsertifikat()
    // {
    //     return $this->belongsTo('App\Models\siswadetail', 'id', 'siswa_id')
    //         ->where('status', 'Aktif')
    //         ->with('apiprobkwithsertifikat');
    // }

    // public function ortu()
    // {
    //     return $this->belongsTo('App\Models\Ortu', 'id', 'siswa_id');
    // }
    public static function boot()
    {
        parent::boot();

        // static::deleting(function ($siswa) {
        //     // siswadetail::where('siswa_id', $siswa->id)->delete();

        //     // //update
        //     $getApiProBKId = siswadetail::where('siswa_id', $siswa->id)->get();

        //     foreach ($getApiProBKId as $data) {
        //         apiprobk::where('id', $data->apiprobk_id)->update([
        //             'sinkron' => 'belum',
        //             'sinkron_tgl' => date("Y-m-d H:i:s"),
        //             'updated_at' => date("Y-m-d H:i:s")
        //         ]);
        //         // apiprobk::where('id', $data->apiprobk_id)->forceDelete();
        //         // apiprobk_deteksi::where('apiprobk_id', $data->apiprobk_id)->forceDelete();
        //         // apiprobk_sertifikat::where('apiprobk_id', $data->apiprobk_id)->forceDelete();
        //     }

        //     $siswa->siswadetail()->delete();
        // });
    }
}
