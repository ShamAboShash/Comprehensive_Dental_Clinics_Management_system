<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseStockLog extends Model
{
    protected $fillable = [
        'material_id',
        'warehouse_stock_id',
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

    public function warehouseStock()
    {
        return $this->belongsTo(WarehouseStock::class);
    }

    public function doneBy()
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}
