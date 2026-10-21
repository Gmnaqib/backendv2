<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'nama',
    'kontak',
    'alamat',
])]

class Vendor extends Model
{
    use HasFactory;

    protected $table = 'vendors';

    public function barang()
    {
        return $this->hasMany(Barang::class);
    }
}
