<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $fillable = [
        'session_id',
        'dentist_id',
        'patient_id',
        'notes',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
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
        return $this->hasMany(PrescriptionMedication::class);
    }
}
