<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DentistsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->employee?->dentist?->id,
            'employee_id'            => $this->employee?->id,
            'user_id'                => $this->id,
            'name'                   => $this->name,
            'email'                  => $this->email,
            'phone'                  => $this->phone,
            'role'                   => $this->role?->value,
            'specialization_name'    => $this->employee?->dentist?->specialization?->name,
            'specialization_name_en' => $this->employee?->dentist?->specialization?->name_en,
            'profile_picture'        => $this->profile_picture_url,
            'is_active'              => (bool) $this->is_active,
        ];
    }
}
