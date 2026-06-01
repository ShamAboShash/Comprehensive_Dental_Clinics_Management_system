<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DentistsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         return [
            'id'              => $this->employee->id,
            'user_id'         =>$this->id,
            'name'            => $this->name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'role'            => $this->role->value,
            'specialization'  => $this->employee?->dentist?->specialization?->name,
            'profile_picture' => $this->profile_picture_url,
        ];
    }
}
