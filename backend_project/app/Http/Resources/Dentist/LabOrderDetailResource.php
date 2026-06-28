<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;

class LabOrderDetailResource extends JsonResource
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

            'items' => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id'             => $item->id,
                    'tooth_number'   => $item->tooth_number,
                    'price'          => $item->price,        // stored snapshot
                    'notes'          => $item->notes,
                    'lab_order_type' => $item->labOrderType ? [
                        'id'       => $item->labOrderType->id,
                        'name'     => $item->labOrderType->name,
                        'name_en'  => $item->labOrderType->name_en,
                        'type'     => $item->labOrderType->type,
                        'material' => $item->labOrderType->material,
                    ] : null,
                ])
            ),

            'modifications' => $this->whenLoaded('modifications', fn() =>
                $this->modifications->map(fn($mod) => [
                    'id'               => $mod->id,
                    'fault'            => $mod->fault,
                    'description'      => $mod->description,
                    'dentist_response' => $mod->dentist_response,
                    'created_at'       => $mod->created_at->toDateTimeString(),
                ])
            ),

            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
