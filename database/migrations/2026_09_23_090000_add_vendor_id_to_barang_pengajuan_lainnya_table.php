<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barang_pengajuan_lainnya', function (Blueprint $table) {
            if (! Schema::hasColumn('barang_pengajuan_lainnya', 'vendor_id')) {
                $table->foreignId('vendor_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('vendors')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('barang_pengajuan_lainnya', function (Blueprint $table) {
            if (Schema::hasColumn('barang_pengajuan_lainnya', 'vendor_id')) {
                $table->dropConstrainedForeignId('vendor_id');
            }
        });
    }
};
