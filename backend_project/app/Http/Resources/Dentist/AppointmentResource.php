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
                'id' => $this->appointmentType->id,
                'name'    => $this->appointmentType->name,
                'name_en' => $this->appointmentType->name_en,
            ]),
            'type'             => $this->type,
            'status'           => $this->status,
            'appointment_time' => $this->appointment_time?->toDateTimeString(),
            'rejection_reason' => $this->rejection_reason,
            'notes'            => $this->notes,
        ];
    }
}
