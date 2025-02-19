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
        Schema::create('transaksi_nitip_barang_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            // !data dari stok barang tetepi tidak perlu di update ketika seller mengupdate data karena barang sudah di tangan reseller
            $table->bigInteger('stok_barang_id')->nullable();
            $table->string('harga')->nullable();
            // !data
            $table->string('qty')->nullable();
            // relasi
            $table->bigInteger('transaksi_nitip_barang_id')->nullable();
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
        Schema::dropIfExists('transaksi_nitip_barang_detail');
    }
};
