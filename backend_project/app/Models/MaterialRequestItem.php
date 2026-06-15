<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequestItem extends Model
{
    protected $fillable = [
        'material_request_id',
        'material_id',
        'quantity_requested',
        'status',
        'notes',
    ];

    // ====================== Relationships ======================

    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function fulfillment()
    {
        return $this->hasMany(WarehouseFulfillment::class);
    }
}
