<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $fillable = [
        'session_id',
        'dentist_id',
        'patient_id',
        'interaction_override',
        'notes',
    ];

    public function session()
    {
        return $this->belongsTo(TreatmentSession::class);
    }

    public function dentist()
    {
        return $this->belongsTo(Dentist::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function medications()
    {
        return $this->belongsToMany(Medication::class, 'prescription_medications')
            ->withPivot('dosage', 'frequency', 'duration', 'notes')
            ->withTimestamps();
    }
}
