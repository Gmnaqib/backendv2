<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();

            $table->string('nama');

            $table->enum('kategori', [
                'atk_tahunan',
                'atk_ujian',
                'atk_kelas',
            ]);

            $table->enum('tipe', [
                'habis_pakai',
                'tidak_habis_pakai'
            ]);

            $table->string('unit');

            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->casecadeOnUpdate()
                ->nullOnDelete();
            $table->decimal('harga', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};