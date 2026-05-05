<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientAllergy extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'allergy_name',

    ];

    protected $casts = [
        // You can add more casts if needed
    ];

    /**
     * Get the patient that owns this allergy.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
