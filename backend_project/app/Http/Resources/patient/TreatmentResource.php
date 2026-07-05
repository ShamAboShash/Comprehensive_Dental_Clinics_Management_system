<?php

namespace App\Http\Resources\patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\dentist\SessionDetailResource;

class TreatmentResource extends JsonResource
{
    public function toArray(Request $request): array
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
            'status'                => $this->status,
            'total_sessions_needed' => $this->total_sessions_needed,
            'sessions_completed'    => $this->sessions_completed,
            'notes'                 => $this->notes,
            'created_at'            => $this->created_at->toDateString(),
            'sessions'              => SessionDetailResource::collection($this->whenLoaded('sessions')),

            ];
    }
}
