<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'daftar_pengajuan_id',
    'id_barang',
    'jumlah',
    'jumlah_disetujui',
    'status'
])]

class BarangPengajuan extends Model
{
    use HasFactory;

    protected $table = 'barang_pengajuan';

    public function daftarPengajuan()
    {
        return $this->belongsTo(DaftarPengajuan::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
