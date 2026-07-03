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
            'medications'  => $this->medications->map(fn ($m) => [
                'name'      => $m->medication->name ?? null,
                'dosage'    => $m->dosage,
                'frequency' => $m->frequency,
                'duration'  => $m->duration,
                'notes'     => $m->notes,
            ]),
        ];
    }
}
