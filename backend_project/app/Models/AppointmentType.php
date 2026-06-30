<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentType extends Model
{
    protected $fillable = [
        'name',
        'name_en',
    ];

    public function specializations()
{
    return $this->belongsToMany(Specialization::class, 'appointment_type_specializations');
}
}
