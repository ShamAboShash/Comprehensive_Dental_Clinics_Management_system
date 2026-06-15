<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'created_by',
        'supplier_name',
        'invoice_date',
        'total_amount',
        'photo_path',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    // Auto-calculate total from items
    public function calculateTotal(): void
    {
        $total = $this->items()->sum(
            DB::raw('quantity * unit_price')
        );
        $this->update(['total_amount' => $total]);
    }
}
