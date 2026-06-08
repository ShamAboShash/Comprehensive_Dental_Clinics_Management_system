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
}
