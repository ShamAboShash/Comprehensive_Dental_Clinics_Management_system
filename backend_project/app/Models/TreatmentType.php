<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentType extends Model
{
    protected $fillable = [
        'name',
        'name_en',
    ];

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }

    public function offers()
{
    return $this->belongsToMany(
        CenterOffer::class,
        'center_offer_treatment_type',
        'treatment_type_id',
        'center_offer_id'
    )->withTimestamps();
}

}
