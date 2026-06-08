<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    protected $fillable = [
        'requested_by',
        'requester_type',
        'clinic_id',
        'status',
        'notes',
    ];

    // ====================== Relationships ======================

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function items()
    {
        return $this->hasMany(MaterialRequestItem::class);
    }

    public function newMaterialRequests()
    {
        return $this->hasMany(NewMaterialRequest::class);
    }
}
