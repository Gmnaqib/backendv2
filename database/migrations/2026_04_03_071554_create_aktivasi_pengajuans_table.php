<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('aktivasi_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengajuan')->constrained('pengajuan')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('aktif_mulai');
            $table->date('aktif_selesai');
            $table->enum('tipe', ['rutin', 'nonrutin']);
            $table->string('tahun_akademik');
            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('aktivasi_pengajuan');
    }
};
