<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToothTreatment extends Model
{
    protected $fillable = [
        'session_id',
        'procedure_id',
        'tooth_number',
        'notes',
    ];

    // ====================== Relationships ======================

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }
}
