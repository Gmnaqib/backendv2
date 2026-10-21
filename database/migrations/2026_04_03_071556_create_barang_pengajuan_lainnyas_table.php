<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('barang_pengajuan_lainnya', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daftar_pengajuan_id')->constrained('daftar_pengajuan')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('nama');
            $table->integer('jumlah');
            $table->integer('jumlah_disetujui')->default(0);
            $table->enum('kategori', ['habis_pakai','tidak_habis_pakai']);
            $table->string('satuan');
            $table->boolean('status')->default(false);
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('barang_pengajuan_lainnya');
    }
};
