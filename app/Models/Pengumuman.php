<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'judul',
    'teks',
    'gambar',
])]

class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    const CREATED_AT = 'create_at';

    public $timestamps = true;

    const UPDATED_AT = null;
}
