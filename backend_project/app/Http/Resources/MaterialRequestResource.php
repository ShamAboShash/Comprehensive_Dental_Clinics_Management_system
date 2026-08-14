<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MaterialRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'status'         => $this->status,
            'requester_type' => $this->requester_type,
            'notes'          => $this->notes,

            'clinic_id'       => $this->clinic_id,
            'clinic'          => $this->whenLoaded('clinic', fn () => $this->clinic ? [
                'id'   => $this->clinic->id,
                'name' => $this->clinic->name,
            ] : null),
            
            'requester' => $this->whenLoaded('requestedBy', fn () => $this->requestedBy ? [
                'id'   => $this->requestedBy->id,
                'name' => $this->requestedBy->name,
            ] : null),
            'items'     => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id'                 => $item->id,
                    'material'           => $item->material->name ?? null,
                    'quantity_requested' => $item->quantity_requested,
                    'status'             => $item->status,
                    'notes'              => $item->notes,
                ])
            ),
            'new_items' => $this->whenLoaded('newItems', fn() =>
                $this->newItems->map(fn($item) => [
                    'id'            => $item->id,
                    'material_name' => $item->material_name,
                    'quantity'      => $item->quantity,
                    'unit'          => $item->unit,
                    'company_name'  => $item->company_name,
                    'reason'        => $item->reason,
                    'status'        => $item->status,
                ])
            ),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
