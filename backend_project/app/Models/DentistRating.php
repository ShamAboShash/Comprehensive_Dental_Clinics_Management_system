<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DentistRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'dentist_id',
        'patient_id',
        'rating',    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function dentist()
    {
        return $this->belongsTo(Dentist::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
