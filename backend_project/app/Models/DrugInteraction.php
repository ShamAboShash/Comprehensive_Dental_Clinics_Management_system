<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrugInteraction extends Model
{
    protected $fillable = [
        'medication_id_1',
        'medication_id_2',
        'severity',
        'description',
    ];

    public function medication1()
    {
        return $this->belongsTo(Medication::class, 'medication_id_1');
    }

    public function medication2()
    {
        return $this->belongsTo(Medication::class, 'medication_id_2');
    }

    // ---------------------------------------------------------------
    // Helper: find all interactions between two sets of medication IDs
    // patientMeds = what the patient is already taking
    // newMeds     = what the dentist wants to prescribe
    // ---------------------------------------------------------------
    public static function findInteractions(
        \Illuminate\Support\Collection $patientMedIds,
        \Illuminate\Support\Collection $newMedIds
    ): \Illuminate\Database\Eloquent\Collection {
        if ($patientMedIds->isEmpty() || $newMedIds->isEmpty()) {
            return collect();
        }

        return static::with(['medication1', 'medication2'])
            ->where(function ($q) use ($patientMedIds, $newMedIds) {
                $q->whereIn('medication_id_1', $patientMedIds)
                  ->whereIn('medication_id_2', $newMedIds);
            })
            ->orWhere(function ($q) use ($patientMedIds, $newMedIds) {
                $q->whereIn('medication_id_1', $newMedIds)
                  ->whereIn('medication_id_2', $patientMedIds);
            })
            ->get();
    }
}
