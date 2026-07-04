<?php

namespace App\Http\Resources\patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'dentist_name' => $this->dentist->employee->user->name ?? 'طبيب غير معروف',
            'notes'        => $this->notes,
            'created_at'   => $this->created_at?->toDateTimeString(),
            'medications'  => $this->medications->map(fn ($medication) => [
                'name'      => $medication->scientific_name,
                'dosage'    => $medication->pivot->dosage,
                'frequency' => $medication->pivot->frequency,
                'duration'  => $medication->pivot->duration,
                'notes'     => $medication->pivot->notes,
            ]),
        ];
    }
}
