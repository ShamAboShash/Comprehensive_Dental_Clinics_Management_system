<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;

class LabOrderModificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'lab_order_id'         => $this->lab_order_id,
            'modification_details' => $this->modification_details,
            'fault'                => $this->fault,
            'status'               => $this->status,
            'response_notes'       => $this->response_notes,
            'created_at'           => $this->created_at->toDateTimeString(),
        ];
    }
}
