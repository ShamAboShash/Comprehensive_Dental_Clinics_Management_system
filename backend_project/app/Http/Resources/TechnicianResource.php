<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $workStatus = ($this->active_orders_count > 0) ? 'يعمل' : 'لا يعمل';
        return [
            'id'         => $this->id,
            'name'       => $this->user->name,
            'email'      => $this->user->email,
            'phone'      => $this->user->phone,
            'is_active'   => $workStatus, // Displays 'يعمل' or 'لا يعمل'
            'schedule'   => $this->whenLoaded('workSchedules', fn() =>
                $this->workSchedules->map(fn($s) => [
                    'day'        => $s->day_of_week,
                    'start_time' => $s->start_time,
                    'end_time'   => $s->end_time,
                ])
            ),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
