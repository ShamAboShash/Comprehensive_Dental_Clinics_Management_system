<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrder extends Model
{
    protected $fillable = [
        'treatment_id',
        'session_id',
        'dentist_id',
        'patient_id',
        'technician_id',
        'status',
        'delivery_date_requested',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'delivery_date_requested' => 'date',
    ];

    // ====================== Relationships ======================

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

    public function treatmentsession()
    {
        return $this->belongsTo(TreatmentSession::class);
    }

    public function dentist()
    {
        return $this->belongsTo(Dentist::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function technician()
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }

    public function items()
    {
        return $this->hasMany(LabOrderItem::class);
    }

    public function modifications()
    {
        return $this->hasMany(LabOrderModification::class);
    }

    public function materials()
    {
        return $this->hasMany(LabOrderMaterial::class);
    }

    // ====================== Helper Methods ======================

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function totalCost(): float
    {
        return $this->items()->sum('price');
    }
}
