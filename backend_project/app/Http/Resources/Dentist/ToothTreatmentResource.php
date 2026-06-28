<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;

class ToothTreatmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'tooth_number' => $this->tooth_number,
            'procedure'    => $this->whenLoaded('procedure', fn() => [
                'id'      => $this->procedure->id,
                'name'    => $this->procedure->name,
                'name_en' => $this->procedure->name_en,
                'price'   => $this->procedure->price,
            ]),
            'notes'        => $this->notes,
        ];
    }
}
