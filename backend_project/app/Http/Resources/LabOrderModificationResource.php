<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LabOrderModificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'description' => $this->description,
            'fault'       => $this->fault,
            'created_at'  => $this->created_at->toDateTimeString(),
        ];
    }
}
