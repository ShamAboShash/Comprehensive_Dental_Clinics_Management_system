<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiseaseCategory extends Model
{
    protected $fillable = [
        'name',

    ];

    public function chronicDiseases()
    {
        return $this->hasMany(PatientChronicDisease::class, 'category_id');
    }
}
