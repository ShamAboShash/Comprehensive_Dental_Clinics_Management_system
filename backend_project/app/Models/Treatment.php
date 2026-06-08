<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    protected $fillable = [
        'patient_id',
        'dentist_id',
        'treatment_type_id',
        'total_sessions_needed',
        'sessions_completed',
        'status',
        'notes',
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

    public function treatmentType()
    {
        return $this->belongsTo(TreatmentType::class);
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function labOrders()
    {
        return $this->hasMany(LabOrder::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    // ====================== Helper Methods ======================

    public function isOngoing(): bool
    {
        return $this->status === 'ongoing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    // calculate total cost dynamically from all sessions
    public function calculateTotalCost(): float
    {
        return $this->sessions()
            ->with('toothTreatments.procedure')
            ->get()
            ->flatMap(fn($session) => $session->toothTreatments)
            ->sum(fn($tt) => $tt->procedure->price);
    }
}
