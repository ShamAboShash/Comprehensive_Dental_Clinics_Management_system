<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'session_number'    => $this->session_number,
            'status'            => $this->status,
            'actual_start_time' => $this->actual_start_time?->toDateTimeString(),
            'actual_end_time'   => $this->actual_end_time?->toDateTimeString(),
            'dentist'           => $this->whenLoaded('dentist', fn() => [
                'id'   => $this->dentist->id,
                'name' => $this->dentist->employee->user->name,
            ]),
            'notes'             => $this->notes,
            'session_cost'      => $this->calculateCost(),
        ];
    }
}
