<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PatientProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->user->name,
            'phone'          => $this->user->phone,
            'date_of_birth'  => $this->user->date_of_birth,
            'gender'         => $this->user->gender,
            'profile_picture' => $this->user->profile_picture
                ? Storage::url($this->user->profile_picture)
                : null,

            'emergency_contact' => [
                'name'     => $this->emergency_contact_name,
                'relation' => $this->emergency_contact_relation,
                'phone'    => $this->emergency_contact_phone,
            ],

            'medical_flags' => [
                'is_pregnant'             => $this->is_pregnant,
                'is_breastfeeding'        => $this->is_breastfeeding,
                'is_smoker'               => $this->is_smoker,
                'drinks_alcohol_frequently' => $this->drinks_alcohol_frequently,
                'teeth_cleaning_frequency'  => $this->teeth_cleaning_frequency,
            ],

            'allergies' => $this->whenLoaded('allergies',
                fn() => $this->allergies->map(fn($a) => [
                    'id'   => $a->id,
                    'name' => $a->allergy_name,
                ])
            ),

            'chronic_diseases' => $this->whenLoaded('chronicDiseases',
                fn() => $this->chronicDiseases->map(fn($d) => [
                    'id'           => $d->id,
                    'category'     => $d->category?->name,
                    'problem_name' => $d->problem_name,
                    'details'      => $d->details,
                    'severity'     => $d->severity,
                ])
            ),

            'medications' => $this->whenLoaded('medications',
                fn() => $this->medications->map(fn($m) => [
                    'id'              => $m->id,
                    'medication_name' => $m->pivot->medication_id
                        ? $m->scientific_name
                        : null,
                    'dosage'          => $m->pivot->dosage,
                    'frequency'       => $m->pivot->frequency,
                    'notes'           => $m->pivot->notes,
                ])
            ),

            'treatments' => $this->whenLoaded('treatments',
                fn() => $this->treatments->map(fn($t) => [
                    'id'                   => $t->id,
                    'treatment_type'       => $t->treatmentType?->name,
                    'dentist_name'         => $t->dentist->employee->user->name,
                    'status'               => $t->status,
                    'total_sessions_needed' => $t->total_sessions_needed,
                    'sessions_completed'   => $t->sessions_completed,
                    'notes'                => $t->notes,
                    'created_at'           => $t->created_at->toDateString(),
                ])
            ),
        ];
    }
}
