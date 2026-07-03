<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'description'    => $this->description,
            'status'         => $this->status,
            'priority'       => $this->priority,
            'phone_number'   => $this->phone_number,
            'admin_response' => $this->admin_response,
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
