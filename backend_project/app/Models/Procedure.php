<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Procedure extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function toothTreatments()
    {
        return $this->hasMany(ToothTreatment::class);
    }
}
