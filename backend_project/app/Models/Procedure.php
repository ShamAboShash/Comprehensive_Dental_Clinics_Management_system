<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Procedure extends Model
{
    protected $fillable = [
    'name',
    'name_en',
    'price',
    // 'treatment_session_id',
];

    protected $casts = [
        // 'price' => 'decimal:2',
    ];

    public function toothTreatments()
    {
        return $this->hasMany(ToothTreatment::class);
    }

    public function treatmentSession()
    {
        return $this->belongsTo(TreatmentSession::class, 'treatment_session_id');
    }
}
