<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'emergency_contact_name',
        'emergency_contact_relation',
        'emergency_contact_phone',
        'is_pregnant',
        'is_breastfeeding',
        'is_smoker',
        'drinks_alcohol_frequently',
        'teeth_cleaning_frequency',
    ];

    protected $casts = [

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chronicDiseases()
    {
        return $this->hasMany(PatientChronicDisease::class);
    }

    public function medications()
    {
        return $this->hasMany(PatientMedication::class);            // return $this->belongsToMany(Medication::class, 'patient_medications')
            // ->withPivot('dosage', 'frequency', 'notes')
            // ->withTimestamps();

    }

    public function allergies()
    {
        return $this->hasMany(PatientAllergy::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }

//     public function medicalHistories()
// {
//     return $this->hasMany(PatientMedicalHistory::class);
// }

// // Optional: Helper Scopes
// public function anesthesiaProblems()
// {
//     return $this->medicalHistories()->where('category', 'anesthesia');
// }

// public function dentalProblems()
// {
//     return $this->medicalHistories()->where('category', 'dental');
// }
}
