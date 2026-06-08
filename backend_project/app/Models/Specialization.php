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
       return $this->hasMany(Dentist::class);
    }


    public function appointmentTypes()
{
    return $this->belongsToMany(
        AppointmentType::class,
        'appointment_type_specializations'
    );
}
}
