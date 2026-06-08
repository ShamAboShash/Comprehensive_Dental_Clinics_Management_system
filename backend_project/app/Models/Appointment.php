<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'dentist_id',
        'appointment_type_id',
        'treatment_id',
        'created_by',
        'type',
        'status',
        'appointment_time',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'appointment_time' => 'datetime',
    ];

    // ====================== Relationships ======================

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist()
    {
        return $this->belongsTo(Dentist::class);
    }

    public function appointmentType()
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function emergencyDetails()
    {
        return $this->hasOne(EmergencyAppointment::class);
    }

    public function session()
    {
        return $this->hasOne(Session::class);
    }

    // ====================== Helper Methods ======================

    public function isEmergency(): bool
    {
        return $this->type === 'emergency';
    }

    public function isWalkIn(): bool
    {
        return $this->type === 'walk_in';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
