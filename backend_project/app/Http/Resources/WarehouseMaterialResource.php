<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseMaterialResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'material_id'     => $this->material_id,
            'material'        => $this->whenLoaded('material', fn () => $this->material->name),
            'material_en'     => $this->whenLoaded('material', fn () => $this->material->name_en),
            'unit'            => $this->whenLoaded('material', fn () => $this->material->unit),
            'category'        => $this->whenLoaded('material', fn () => $this->material->category),
            'quantity'        => $this->quantity,
            'expiration_date' => $this->expiration_date?->toDateString(),
            'description'     => $this->whenLoaded('material', fn () => $this->material->description ?? null),
        ];
    }
}
