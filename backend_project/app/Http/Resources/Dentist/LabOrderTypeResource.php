<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;

class LabOrderTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'       => $this->id,
            'category' => $this->whenLoaded('category', fn() => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
            'name'     => $this->name,
            'name_en'  => $this->name_en,
            'type'     => $this->type,
            'material' => $this->material,
            'price'    => $this->price,
            'duration' => $this->duration,
        ];
    }
}
