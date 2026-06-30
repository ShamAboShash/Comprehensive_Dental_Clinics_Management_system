<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentResource extends JsonResource
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

            'teeth_map' => $this->whenLoaded('sessions', function () {
    $map = [];

    foreach ($this->sessions as $session) {
        foreach ($session->toothTreatments as $tt) {
            $tooth = (string) $tt->tooth_number;

            // Force it to stay as object key
            if (!array_key_exists($tooth, $map)) {
                $map[$tooth] = [];
            }

            $map[$tooth][] = [
                'tooth_treatment_id' => $tt->id,
                'procedure'          => $tt->procedure?->name,
                'procedure_en'       => $tt->procedure?->name_en,
                'price'              => $tt->procedure?->price,
                'session_id'         => $session->id,
                'session_number'     => $session->session_number,
                'notes'              => $tt->notes,
            ];
        }
    }

    // Sort treatments inside each tooth
    foreach ($map as $tooth => $treatments) {
        usort($map[$tooth], fn($a, $b) => $a['session_number'] <=> $b['session_number']);
    }

return (object) $map;}),
        ];
    }
}
