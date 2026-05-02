<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialization extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'description',
    ];

    public function dentists()
    {
        return $this->hasOne(Dentist::class);
    }
}
