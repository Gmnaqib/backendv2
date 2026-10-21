<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'daftar_pengajuan_id',
    'nama',
    'jumlah',
    'jumlah_disetujui',
    'kategori',
    'satuan',
    'status',
    'vendor_id',
    'bukti_foto',
    'alasan'
])]

class BarangPengajuanLainnya extends Model
{
    protected $table = 'barang_pengajuan_lainnya';

    public $timestamps = false;

    public function daftarPengajuan()
    {
        return $this->belongsTo(DaftarPengajuan::class);
    }
}
