<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrderItem extends Model
{
    protected $fillable = [
        'lab_order_id',
        'tooth_number',
        'lab_order_type_id',
        'price',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    // ====================== Relationships ======================

    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function labOrderType()
    {
        return $this->belongsTo(LabOrderType::class);
    }
}
