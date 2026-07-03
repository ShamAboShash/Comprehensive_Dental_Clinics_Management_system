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
            'phone'            => $this->phone,
            'email'            => $this->email,
            'logo_url'         => $this->logo_path ? Storage::url($this->logo_path) : null,
            'facebook_url'     => $this->facebook_url,
            'instagram_url'    => $this->instagram_url,
            'whatsapp_number'  => $this->whatsapp_number,
        ];
    }
}
