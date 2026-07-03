<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentOffer extends Model
{
    protected $fillable = ['treatment_id', 'offer_id', 'discount_percentage', 'applied_at'];

    public $timestamps = false;

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

    public function offer()
    {
        return $this->belongsTo(CenterOffer::class);
    }
}
