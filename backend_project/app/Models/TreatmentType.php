<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentType extends Model
{
    protected $fillable = [
        'name',
        'name_en',
    ];

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }
}
