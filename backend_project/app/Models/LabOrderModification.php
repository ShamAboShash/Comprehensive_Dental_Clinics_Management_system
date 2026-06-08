<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrderModification extends Model
{
    protected $fillable = [
        'lab_order_id',
        'modification_details',
        'fault',
        'status',
        'response_notes',
    ];

    // ====================== Relationships ======================

    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class);
    }
}
