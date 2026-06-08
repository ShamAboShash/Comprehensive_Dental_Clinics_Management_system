<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    protected $fillable = [
        'name',
        'room_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function dentists()
    {
        return $this->belongsToMany(Dentist::class, 'dentist_clinics');
    }

    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class);
    }
}
