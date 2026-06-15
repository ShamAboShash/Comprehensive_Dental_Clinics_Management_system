<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseMaterialResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'material_id' => $this->material_id,
            'material'    => $this->whenLoaded('material', fn() => $this->material->name),
            'unit'        => $this->whenLoaded('material', fn() => $this->material->unit),
            'quantity'    => $this->quantity,
            'description' => $this->whenLoaded('material', fn() => $this->material->description),
        ];
    }
}
