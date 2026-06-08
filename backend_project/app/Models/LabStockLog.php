<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabStockLog extends Model
{
    protected $fillable = [
        'material_id',
        'lab_stock_id',
        'type',
        'quantity',
        'reason',
        'notes',
        'done_by',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function labStock()
    {
        return $this->belongsTo(LabStock::class);
    }

    public function doneBy()
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}
