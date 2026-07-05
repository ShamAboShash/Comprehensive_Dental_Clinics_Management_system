<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TreatmentPhotoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'url'  =>$this->getPhotoUrlAttribute(),
            'session_id'  => $this->session_id,
            'session'     => $this->whenLoaded('session'),
            'photo_type'  => $this->photo_type,
            'description' => $this->description,
            'created_at'  => $this->created_at->toDateTimeString(),
        ];
    }
}
