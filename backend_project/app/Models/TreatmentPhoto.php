<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentPhoto extends Model
{
    protected $fillable = [
        'session_id',
        'photo_path',
        'photo_type',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ====================== Relationships ======================

    public function session()
    {
        return $this->belongsTo(TreatmentSession::class);
    }

    // ====================== Accessors ======================

    public function getPhotoUrlAttribute()
    {
        return asset('storage/' . $this->photo_path);
    }
}
