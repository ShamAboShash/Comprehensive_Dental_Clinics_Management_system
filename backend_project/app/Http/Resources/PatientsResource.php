<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'user_id' => $this->user->id,

            'name'            => $this->user->name,
            'email'           => $this->user->email,
            'phone'           => $this->user->phone,
            'profile_picture' => $this->user->profile_picture_url,
        ];
    }
}
