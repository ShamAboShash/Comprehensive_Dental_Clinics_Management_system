<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrderType extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'name_en',
        'type',
        'material',
        'price',
        'duration',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price'     => 'decimal:2',
    ];

    // ====================== Relationships ======================

    public function category()
    {
        return $this->belongsTo(LabOrderTypeCategory::class, 'category_id');
    }

    public function labOrderItems()
    {
        return $this->hasMany(LabOrderItem::class);
    }
}
