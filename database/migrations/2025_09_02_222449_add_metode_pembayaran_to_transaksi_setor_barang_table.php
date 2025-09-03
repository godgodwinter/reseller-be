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
        Schema::table('transaksi_setor_barang', function (Blueprint $table) {
            $table->mediumText('bukti_tf')->nullable();
            $table->string('tgl_transaksi_setor_barang')->nullable();
            $table->string('metode_pembayaran')->nullable()->default(null);
            $table->string('status_konfirmasi')->nullable()->default('Belum');
            $table->string('keterangan_konfirmasi')->nullable()->default(null);
            $table->string('tgl_konfirmasi')->nullable()->default(null);
        });

        Schema::table('transaksi_setor_barang_detail', function (Blueprint $table) {
            $table->string('harga')->nullable();
            $table->string('status_konfirmasi')->nullable()->default('Belum');
            // !data
            $table->string('qty')->nullable();
            $table->string('transaksi_nitip_barang_detail_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaksi_setor_barang', function (Blueprint $table) {
            $table->dropColumn('bukti_tf');
            $table->dropColumn('tgl_transaksi_setor_barang');
            $table->dropColumn('metode_pembayaran');
            $table->dropColumn('status_konfirmasi');
            $table->dropColumn('keterangan_konfirmasi');
            $table->dropColumn('tgl_konfirmasi');
        });

        Schema::table('transaksi_setor_barang_detail', function (Blueprint $table) {
            $table->dropColumn('harga');
            $table->dropColumn('status_konfirmasi');
            $table->dropColumn('qty');
            $table->dropColumn('transaksi_nitip_barang_detail_id');
        });
    }
};
