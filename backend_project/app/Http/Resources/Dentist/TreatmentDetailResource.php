<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,
            'treatment_type'        => $this->whenLoaded('treatmentType', fn() => [
                'id'      => $this->treatmentType->id,
                'name'    => $this->treatmentType->name,
                'name_en' => $this->treatmentType->name_en,
            ]),
            'dentist'               => $this->whenLoaded('dentist', fn() => [
                'id'   => $this->dentist->id,
                'name' => $this->dentist->employee->user->name,
            ]),
            'patient'               => $this->whenLoaded('patient', fn() => [
                'id'   => $this->patient->id,
                'name' => $this->patient->user->name,
            ]),
            'status'                => $this->status,
            'total_sessions_needed' => $this->total_sessions_needed,
            'sessions_completed'    => $this->sessions_completed,
            'notes'                 => $this->notes,
            'created_at'            => $this->created_at->toDateString(),
            'sessions'              => SessionResource::collection(
                $this->whenLoaded('sessions')
            ),
        ];
    }
}
