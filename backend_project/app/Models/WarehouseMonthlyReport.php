<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseMonthlyReport extends Model
{
    protected $fillable = [
        'month',
        'from_date',
        'to_date',
        'data',
        'generated_by',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
        'data'      => 'array',
    ];

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
