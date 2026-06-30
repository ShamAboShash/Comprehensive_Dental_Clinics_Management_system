<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
public function toArray($request): array
{
    return [
        'id'               => $this->id,
        'patient'          => [
            'id'   => $this->patient->id,
            'name' => $this->patient->user->name,
        ],
        'appointment_type' => $this->whenLoaded('appointmentType', fn() => [
            'id'      => $this->appointmentType->id,
        ]),
        'type'             => $this->type,
        'status'           => $this->status,
        'appointment_time' => $this->appointment_time?->toDateTimeString(),
        'rejection_reason' => $this->rejection_reason,
        'notes'            => $this->notes,

        // Treatment info — null if no treatment linked yet
        'treatment'        => $this->whenLoaded('treatment', fn() => $this->treatment ? [
            'id'                    => $this->treatment->id,
            'treatment_type'        => $this->treatment->treatmentType ? [
                'id'      => $this->treatment->treatmentType->id,
            ] : null,
            'status'                => $this->treatment->status,
            'total_sessions_needed' => $this->treatment->total_sessions_needed,
            'sessions_completed'    => $this->treatment->sessions_completed,
            'notes'                 => $this->treatment->notes,
        ] : null),
    ];
}
}
