<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CenterOffer extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'description',
        'start_date',
        'end_date',
        'conditions',
        'discount_percentage',
        'photo_path',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active
            && now()->between($this->start_date, $this->end_date);
    }

    public function treatmentTypes()
{
    return $this->belongsToMany(
        TreatmentType::class,
        'center_offer_treatment_type',
        'center_offer_id',
        'treatment_type_id'
    )->withTimestamps();
}

}
