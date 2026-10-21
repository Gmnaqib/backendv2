<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('unit_types', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('unit_types') 
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('unit_types');
    }
};
