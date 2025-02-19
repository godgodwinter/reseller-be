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
        Schema::create('transaksi_setor_barang_detail', function (Blueprint $table) {
            $table->bigIncrements('id');
            // relasi
            $table->bigInteger('transaksi_setor_barang_id')->nullable();
            $table->bigInteger('stok_barang_id')->nullable();
            $table->bigInteger('users_id')->nullable(); // seller / admin
            $table->bigInteger('reseller_id')->nullable(); // penerima
            $table->string('status')->nullable()->default('Aktif');
            // $table->string('qty')->nullable();
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
        Schema::dropIfExists('transaksi_setor_barang_detail');
    }
};
