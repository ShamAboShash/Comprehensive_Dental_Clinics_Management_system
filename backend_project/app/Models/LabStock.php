<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabStock extends Model
{
    protected $fillable = [
        'material_id',
        'quantity',
        'expiration_date',
    ];

    protected $casts = [
        'expiration_date' => 'date',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function logs()
    {
        return $this->hasMany(LabStockLog::class);
    }
}
