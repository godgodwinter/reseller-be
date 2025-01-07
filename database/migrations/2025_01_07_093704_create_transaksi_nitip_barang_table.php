<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaksi_nitip_barang', function (Blueprint $table) {
            $table->bigIncrements('id');
            // !data dari stok barang tetepi tidak perlu di update ketika seller mengupdate data karena barang sudah di tangan reseller
            $table->bigInteger('stok_barang_id')->nullable();
            $table->string('jml')->nullable();
            $table->string('harga')->nullable();
            $table->string('ket')->nullable();
            $table->longText('img')->nullable();
            // !data
            // $table->string('nama_barang')->nullable();
            // $table->string('nama_barang_alter')->nullable();
            $table->string('tgl_transaksi_nitip_barang')->nullable();
            // relasi
            $table->bigInteger('users_id')->nullable(); // seller / admin
            $table->bigInteger('reseller_id')->nullable(); // penerima
            $table->string('status')->nullable()->default('Aktif');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaksi_nitip_barang');
    }
};
