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
            'url'         => Storage::url($this->photo_path),
            'photo_type'  => $this->photo_type,
            'description' => $this->description,
            'created_at'  => $this->created_at->toDateTimeString(),
        ];
    }
}
