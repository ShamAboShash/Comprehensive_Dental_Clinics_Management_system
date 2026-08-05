<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'company_name',
        'price_per_unit',
        'dosage',
        'unit',
        'category',
        'is_active',
    ];

    public function warehouseStocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function labStocks()
    {
        return $this->hasMany(LabStock::class);
    }


    public function materialRequestItems()
    {
        return $this->hasMany(MaterialRequestItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
