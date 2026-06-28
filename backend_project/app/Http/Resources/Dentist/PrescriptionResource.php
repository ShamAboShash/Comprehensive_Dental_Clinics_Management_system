<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'session_id'  => $this->session_id,
            'notes'       => $this->notes,
            'medications' => $this->whenLoaded('medications',
                fn() => $this->medications->map(fn($m) => [
                    'id'              => $m->id,
                    'scientific_name' => $m->scientific_name,
                    'dosage'          => $m->pivot->dosage,
                    'frequency'       => $m->pivot->frequency,
                    'duration'        => $m->pivot->duration,
                    'notes'           => $m->pivot->notes,
                ])
            ),
        ];
    }
}
