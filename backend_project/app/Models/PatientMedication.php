<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientMedication extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'medication_name',
        'dosage',
        'frequency',
    ];

    /**
     * Get the patient that this medication belongs to.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
