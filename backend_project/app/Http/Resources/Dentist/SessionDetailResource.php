<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;

class SessionDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'treatment_id'      => $this->treatment_id,
            'session_number'    => $this->session_number,
            'status'            => $this->status,
            'actual_start_time' => $this->actual_start_time?->toDateTimeString(),
            'actual_end_time'   => $this->actual_end_time?->toDateTimeString(),
            'dentist'           => $this->whenLoaded('dentist', fn() => [
                'id'   => $this->dentist->id,
                'name' => $this->dentist->employee->user->name,
            ]),
            'notes'             => $this->notes,
            'session_cost'      => $this->calculateCost(),


            'tooth_treatments'  => ToothTreatmentResource::collection(
                $this->whenLoaded('toothTreatments')
            ),
            'photos'            => TreatmentPhotoResource::collection(
                $this->whenLoaded('photos')
            ),
            'prescription'      => $this->whenLoaded('prescription',
                fn() => $this->prescription
                    ? new PrescriptionResource($this->prescription)
                    : null
            ),
        ];
    }
}
