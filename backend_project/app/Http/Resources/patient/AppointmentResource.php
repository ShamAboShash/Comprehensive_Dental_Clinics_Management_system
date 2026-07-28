<?php

namespace App\Http\Resources\patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'dentist' => $this->whenLoaded('dentist', function () {
                return [
                    // 'id'    => $this->dentist->id,
                    'name'  => $this->dentist->employee?->user?->name,
                    'photo' => $this->dentist->employee?->user?->profile_picture_url
                            ?? $this->dentist->employee?->user?->profile_picture,
                    // 'specialization' => $this->dentist->specialization?->name,
                ];
        }),
            'appointment_type' => $this->appointmentType ? [
                'ar' => $this->appointmentType->name,
                'en' => $this->appointmentType->name_en,
            ] : null,
            'type'             => $this->type,
            'status'           => $this->status,
            'appointment_time' => $this->appointment_time?->toDateTimeString(),
            'rejection_reason' => $this->rejection_reason,
            'notes'            => $this->notes,

        'treatment'        => $this->whenLoaded('treatment', fn() => $this->treatment ? [
            'id'                    => $this->treatment->id,
            'treatment_type'        => $this->treatment->treatmentType ? [
                'ar'      => $this->treatment->treatmentType->name,
                'en'      => $this->treatment->treatmentType->name_en,
            ] : null,
            'status'                => $this->treatment->status,
            'total_sessions_needed' => $this->treatment->total_sessions_needed,
            'sessions_completed'    => $this->treatment->sessions_completed,
            'notes'                 => $this->treatment->notes,
        ] : null),
        ];
    }
}
