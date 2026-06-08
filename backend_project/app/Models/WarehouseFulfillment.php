<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseFulfillment extends Model
{
    protected $fillable = [
        'material_request_item_id',
        'warehouse_stock_id',
        'quantity_given',
        'fulfilled_by',
        'status',
    ];

    // ====================== Relationships ======================

    public function materialRequestItem()
    {
        return $this->belongsTo(MaterialRequestItem::class);
    }

    public function warehouseStock()
    {
        return $this->belongsTo(WarehouseStock::class);
    }

    public function fulfilledBy()
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }
}
