<?php

// namespace App\Http\Resources;

// use Illuminate\Http\Resources\Json\JsonResource;

// class LabOrderResource extends JsonResource
// {
//     public function toArray($request): array
//     {
//         return [
//             'id'            => $this->id,
//             'status'        => $this->status,
//             'notes'         => $this->notes,
//             'lab_item'      => new LabItemResource($this->whenLoaded('labOrderType')),
//             'technician'    => new TechnicianResource($this->whenLoaded('technician')),
//             'modifications' => LabOrderModificationResource::collection($this->whenLoaded('modifications')),
//             'patient'       => $this->whenLoaded('treatment', fn() => [
//                 'id'   => $this->treatment->patient->user->id ?? null,
//                 'name' => $this->treatment->patient->user->name ?? null,
//             ]),
//             'dentist'       => $this->whenLoaded('treatment', fn() => [
//                 'id'   => $this->treatment->dentist->user->id ?? null,
//                 'name' => $this->treatment->dentist->user->name ?? null,
//             ]),
//             'created_at'    => $this->created_at->toDateTimeString(),
//             'updated_at'    => $this->updated_at->toDateTimeString(),
//         ];
//     }
// }



namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'status'                 => $this->status,
            'delivery_date_requested'=> $this->delivery_date_requested?->toDateString(),
            'rejection_reason'       => $this->rejection_reason,
            'notes'                  => $this->notes,
            'total_cost'             => $this->items->sum('price'),


            'dentist'    => $this->whenLoaded('dentist', fn() => [
                'id'   => $this->dentist->id,
                'name' => $this->dentist->employee->user->name,
            ]),

            'patient'    => $this->whenLoaded('patient', fn() => [
                'id'   => $this->patient->id,
                'name' => $this->patient->user->name,
            ]),

            'technician' => $this->whenLoaded('technician', fn() => $this->technician ? [
                'id'   => $this->technician->id,
                'name' => $this->technician->user->name,
            ] : null),

            'items' => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id'           => $item->id,
                    'tooth_number' => $item->tooth_number,
                    'price'        => $item->price,
                    'notes'        => $item->notes,
                    'type'         => $item->labOrderType ? [
                        'id'       => $item->labOrderType->id,
                        'name'     => $item->labOrderType->name,
                        'type'     => $item->labOrderType->type,
                        'material' => $item->labOrderType->material,
                    ] : null,
                ])
            ),

            'modifications' => $this->whenLoaded('modifications', fn() =>
                $this->modifications->map(fn($mod) => [
                    'id'                   => $mod->id,
                    'modification_details' => $mod->modification_details,
                    'fault'                => $mod->fault,
                    'status'               => $mod->status,
                    'response_notes'       => $mod->response_notes,
                ])
            ),

            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
