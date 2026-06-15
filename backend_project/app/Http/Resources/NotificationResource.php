<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'body'       => $this->body,
            'type'       => $this->type,
            'is_read'    => !is_null($this->read_at),
            'read_at'    => $this->read_at,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
