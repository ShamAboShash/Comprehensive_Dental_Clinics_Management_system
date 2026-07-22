<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'tooth_treatment_id',
        'description',
        'unit_price',
        'discount_percentage',

    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function toothTreatment()
    {
        return $this->belongsTo(ToothTreatment::class);
    }

        public function finalPrice(): float
    {
        return round($this->unit_price - ($this->unit_price * $this->discount_percentage / 100), 2);
    }
}
