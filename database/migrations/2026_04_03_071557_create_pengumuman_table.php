<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('teks');
            $table->string('gambar')->nullable();
            $table->timestamp('create_at');
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};
