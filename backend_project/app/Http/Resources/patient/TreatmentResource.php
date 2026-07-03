<?php

namespace App\Http\Resources\patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'dentist_name'          => $this->dentist->employee->user->name ?? 'طبيب غير معروف',
            'treatment_type'        => $this->treatmentType ? [
                'ar' => $this->treatmentType->name,
                'en' => $this->treatmentType->name_en,
            ] : null,
            'total_sessions_needed' => $this->total_sessions_needed,
            'sessions_completed'    => $this->sessions_completed,
            'status'                => $this->status,
            'notes'                 => $this->notes,
            'created_at'            => $this->created_at?->toDateTimeString(),
        ];
    }
}
