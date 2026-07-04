<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;

class LabOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                      => $this->id,
            'treatment_id'            => $this->treatment_id,
            'session_id'              => $this->session_id,
            'status'                  => $this->status,
            'price'                   => $this->price,
            'delivery_date_requested' => $this->delivery_date_requested,
            'notes'                   => $this->notes,
            'rejection_reason'        => $this->rejection_reason,

            'patient'    => $this->whenLoaded('patient', fn() => [
                'id'   => $this->patient->id,
                'name' => $this->patient->user->name,
            ]),

            'technician' => $this->whenLoaded('technician', fn() => $this->technician
                ? ['id' => $this->technician->id, 'name' => $this->technician->user->name]
                : null
            ),

            'items_count' => $this->whenLoaded('items', fn() => $this->items->count()),

            'modifications' => $this->whenLoaded('modifications',
                fn() => LabOrderModificationResource::collection($this->modifications)
            ),

            'pending_modifications_count' => $this->whenLoaded('modifications',
                fn() => $this->modifications->where('status', 'pending')->count()
            ),

            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
