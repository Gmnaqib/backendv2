<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'id_aktivasi',
    'user_id',
    'date',
    'surat_pengajuan'
])]

class DaftarPengajuan extends Model
{
    use HasFactory;

    protected $table = 'daftar_pengajuan';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function aktivasi()
    {
        return $this->belongsTo(AktivasiPengajuan::class, 'id_aktivasi');
    }

    public function barang()
    {
        return $this->hasMany(BarangPengajuan::class, 'daftar_pengajuan_id');
    }

    public function barangLainnya()
    {
        return $this->hasMany(BarangPengajuanLainnya::class);
    }
}
