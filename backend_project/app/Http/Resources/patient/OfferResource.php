<?php

namespace App\Http\Resources\patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'description'          => $this->description,
            'conditions'           => $this->conditions,
            'discount_percentage'  => (float) $this->discount_percentage,
            'start_date'           => $this->start_date?->toDateString(),
            'end_date'             => $this->end_date?->toDateString(),
            'photo_url'            => $this->photo_path ? asset('storage/' . $this->photo_path) : null,
        ];
    }
}
