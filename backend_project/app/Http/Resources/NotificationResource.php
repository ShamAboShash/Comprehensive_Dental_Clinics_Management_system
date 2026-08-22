<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'body'           => $this->body,
            'type'           => $this->type,
            'is_read'        => (bool) $this->is_read,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
