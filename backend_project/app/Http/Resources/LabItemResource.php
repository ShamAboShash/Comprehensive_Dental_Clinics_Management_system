<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'category'    => $this->whenLoaded('category', fn() => new LabOrderTypeCategoryResource($this->category)),
            'name'        => $this->name,
            'name_en'     => $this->name_en,
            'type'        => $this->type,
            'material'    => $this->material,
            'price'       => $this->price,
            'duration'    => $this->duration,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at->toDateTimeString(),
        ];
    }
}
