<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientChronicDisease extends Model
{
  protected $fillable = [
        'patient_id',
        'category',
        'problem_name',
        'details',
        'severity',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
