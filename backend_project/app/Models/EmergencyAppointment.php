<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencyAppointment extends Model
{
    protected $fillable = [
        'appointment_id',
        'emergency_reason',
        'suggested_time',
    ];

    protected $casts = [
        'suggested_time' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
