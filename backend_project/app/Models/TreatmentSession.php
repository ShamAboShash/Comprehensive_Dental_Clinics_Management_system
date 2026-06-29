<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentSession extends Model
{
    protected $fillable = [
        'treatment_id',
        'appointment_id',
        'dentist_id',
        'session_number',
        'actual_start_time',
        'actual_end_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'actual_start_time' => 'datetime',
        'actual_end_time'   => 'datetime',
    ];

    // ====================== Relationships ======================

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function dentist()
    {
        return $this->belongsTo(Dentist::class);
    }

   public function toothTreatments()
    {
        return $this->hasMany(ToothTreatment::class, 'session_id');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'session_id');
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class, 'session_id');
    }

    public function photos()
    {
        return $this->hasMany(TreatmentPhoto::class, 'session_id');
    }

    public function labOrders()
    {
        return $this->hasMany(LabOrder::class, 'session_id');
    }

    // ====================== Helper Methods ======================

    public function calculateCost(): float
    {
        return $this->toothTreatments()
            ->with('procedure')
            ->get()
            ->sum(fn($tt) => $tt->procedure->price);
    }
}
