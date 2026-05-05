<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
      //  'date_of_birth',
      //  'gender',
      //  'phone',
      //  'email',
      //  'address',
        'emergency_contact_name',
        'emergency_contact_relation',
        'emergency_contact_phone',
        'has_chronic_disease',
        'chronic_diseases',
        'takes_regular_medication',
        'regular_medications',
        'uses_blood_thinners',
        'has_allergies',
        'allergies',
        'has_anesthesia_problems',
        'anesthesia_problems_details',
        'has_gum_bleeding',
        'has_dental_allergies',
        'is_pregnant',
        'is_breastfeeding',
        'is_smoker',
        'drinks_alcohol_frequently',
        'teeth_cleaning_frequency',
    ];

    protected $casts = [
        'has_chronic_disease'       => 'boolean',
        'chronic_diseases'          => 'array',
        'takes_regular_medication'  => 'boolean',
        'regular_medications'       => 'array',
        'uses_blood_thinners'       => 'boolean',
        'has_allergies'             => 'boolean',
        'allergies'                 => 'array',
        'has_anesthesia_problems'   => 'boolean',
        'has_gum_bleeding'          => 'boolean',
        'has_dental_allergies'      => 'boolean',
        'is_pregnant'               => 'boolean',
        'is_breastfeeding'          => 'boolean',
        'is_smoker'                 => 'boolean',
        'drinks_alcohol_frequently' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
