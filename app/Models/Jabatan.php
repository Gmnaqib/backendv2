<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nama',
])]

class Jabatan extends Model
{
    protected $table = 'jabatans';

    public function users()
    {
        return $this->hasMany(User::class, 'jabatan_id');
    }

    public function units()
    {
        return $this->hasMany(UnitType::class, 'parent_id');
    }
}
