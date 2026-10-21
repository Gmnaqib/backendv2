<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;


#[Fillable([
    'nama',
    'kategori',
    'tipe',
    'unit',
    'harga',
    'vendor_id'
])]

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function barangPengajuan()
    {
        return $this->hasMany(BarangPengajuan::class, 'id_barang');
    }
}
