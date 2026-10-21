<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'tipe',
    'semester',
    'ujian'
])]

class Pengajuan extends Model
{
    public $timestamps = false;
    
    protected $table = 'pengajuan';

    public function aktivasi()
    {
        return $this->hasMany(AktivasiPengajuan::class, 'id_pengajuan');
    }
}
