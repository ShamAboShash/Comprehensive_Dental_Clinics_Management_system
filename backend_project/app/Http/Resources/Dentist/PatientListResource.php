<?php

namespace App\Http\Resources\Dentist;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PatientListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->user->name,
            'phone'           => $this->user->phone,
            'profile_picture' => $this->user->profile_picture
                ? Storage::url($this->user->profile_picture)
                : null,
            'gender'          => $this->user->gender,
            'date_of_birth'   => $this->user->date_of_birth,
        ];
    }
}
