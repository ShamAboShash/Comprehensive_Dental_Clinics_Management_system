<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientChronicDisease extends Model
{
  protected $fillable = [
        'patient_id',
        'category_id',
        'problem_name',
        'details',
        'severity',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function category() // ✅ add this
    {
        return $this->belongsTo(DiseaseCategory::class, 'category_id');
    }
}
