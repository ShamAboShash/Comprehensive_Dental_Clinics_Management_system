<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LabStockResource extends JsonResource
{
    const LOW_STOCK_THRESHOLD = 10;

    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'material_id' => $this->material_id,
            'material'    => $this->whenLoaded('material', fn() => $this->material->name),
            'unit'        => $this->whenLoaded('material', fn() => $this->material->unit),
            'quantity'    => $this->quantity,
            'is_low'      => $this->quantity <= self::LOW_STOCK_THRESHOLD,
            'expiration_date' => $this->expiration_date?->toDateString(),
            'created_at'      => $this->created_at->toDateTimeString(),
        ];
    }
}
