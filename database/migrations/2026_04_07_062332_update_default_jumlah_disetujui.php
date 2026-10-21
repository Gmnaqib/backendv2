<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('barang_pengajuan', function (Blueprint $table) {
            $table->integer('jumlah_disetujui')->default(0)->change();
        });

        Schema::table('barang_pengajuan_lainnya', function (Blueprint $table) {
            $table->integer('jumlah_disetujui')->default(0)->change();
        });
    }

    
    public function down(): void
    {
        
    }
};




