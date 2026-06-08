<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewMaterialRequest extends Model
{
    protected $fillable = [
        'material_request_id',
        'material_name',
        'company_name',
        'unit',
        'quantity',
        'reason',
        'status',
    ];

    // ====================== Relationships ======================

    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class);
    }
}
