<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'description'    => $this->description,
            'status'         => $this->status,
            'priority'       => $this->priority,
            'admin_response' => $this->admin_response,
            'phone_number'   => $this->phone_number,
            'patient'        => $this->whenLoaded('patient', fn () => [
                'id'    => $this->patient->id,
                'name'  => $this->patient->user?->name,
                'email' => $this->patient->user?->email,
                'phone' => $this->patient->user?->phone,
            ]),
            'submitted_by'   => $this->whenLoaded('submittedBy', fn () => [
                'id'    => $this->submittedBy->id,
                'name'  => $this->submittedBy->name,
                'email' => $this->submittedBy->email,
                'role'  => $this->submittedBy->role?->value,
            ]),
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
