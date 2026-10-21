<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nama',
    'parent_id',
])]

class UnitType extends Model
{
    protected $table = 'unit_types';

    public function parent()
    {
        return $this->belongsTo(UnitType::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(UnitType::class, 'parent_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'unit_id');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }
}
