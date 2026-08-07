<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseStockResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'quantity'        => $this->quantity,
            'expiration_date' => $this->expiration_date
                ? $this->expiration_date->format('Y-m-d')
                : null,
            'is_expired'      => $this->expiration_date
                && $this->expiration_date->lt(now()->startOfDay()),
            'created_at'      => $this->created_at?->toDateTimeString(),
            'updated_at'      => $this->updated_at?->toDateTimeString(),

            // Material info
            'material' => $this->whenLoaded('material', function () {
                return [
                    'id'             => $this->material->id,
                    'name'           => $this->material->name,
                    'name_en'        => $this->material->name_en,
                    'company_name'   => $this->material->company_name,
                    'price_per_unit' => $this->material->price_per_unit,
                    'dosage'         => $this->material->dosage,
                    'unit'           => $this->material->unit,
                    'category'       => $this->material->category,
                    'is_active'      => $this->material->is_active ?? true,
                ];
            }),
        ];
    }
}
