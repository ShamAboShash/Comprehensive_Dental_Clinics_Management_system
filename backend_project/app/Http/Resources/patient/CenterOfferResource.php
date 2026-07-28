<?php

namespace App\Http\Resources\patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CenterOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'title'               => $this->title,
            'description'         => $this->description,
            'start_date'          => $this->start_date?->toDateString(),
            'end_date'            => $this->end_date?->toDateString(),
            'conditions'          => $this->conditions,
            'discount_percentage' => $this->discount_percentage,
            'photo_path'          => $this->photo_path,

            'treatment_types' => $this->whenLoaded('treatmentTypes', function () {
                return $this->treatmentTypes->map(function ($type) {
                    return [
                        'id'      => $type->id,
                        'name'    => $type->name,
                        'name_en' => $type->name_en,
                    ];
                })->values();
            }),
        ];
    }
}
