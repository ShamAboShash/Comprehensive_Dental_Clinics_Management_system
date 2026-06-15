<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrderMaterial extends Model
{
    protected $fillable = [
        'lab_order_id',
        'material_id',
        'quantity_used',
    ];

    // ====================== Relationships ======================

    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
