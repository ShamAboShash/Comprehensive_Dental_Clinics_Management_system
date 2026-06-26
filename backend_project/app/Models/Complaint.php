<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'patient_id',
        'submitted_by',
        'title',
        'description',
        'status',
        'admin_response',
        'priority',
        'phone_number',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
