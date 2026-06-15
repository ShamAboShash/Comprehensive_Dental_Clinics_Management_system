<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrderTypeCategory extends Model
{
    protected $fillable = [
        'name',
        'name_en',
    ];

    // ====================== Relationships ======================

    public function labOrderTypes()
    {
        return $this->hasMany(LabOrderType::class, 'category_id');
    }
}
