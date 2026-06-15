<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabStockLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'type'          => $this->type === 'add' ? 'إضافة' : 'خصم',
            'quantity'      => $this->quantity,
            'reason'        => $this->reason,
            'notes'         => $this->notes,
            'material_name' => $this->labStock->material->name ?? null,
            'unit'          => $this->labStock->material->unit ?? null,
            'created_at'    => $this->created_at->toDateTimeString(),
        ];
    }
}
