<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CenterInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name'             => $this->name,
            'description'      => $this->description,
            'address'          => $this->address,
            'phone'            => $this->phone,
            'email'            => $this->email,
            'facebook_url'     => $this->facebook_url,
            'instagram_url'    => $this->instagram_url,
            'whatsapp_number'  => $this->whatsapp_number,
            'work_start_time' => $this->work_start_time
                ? substr((string) $this->work_start_time, 0, 5) 
                : null,
            'work_end_time'   => $this->work_end_time
                ? substr((string) $this->work_end_time, 0, 5)
                : null,
        ];
    }
}
