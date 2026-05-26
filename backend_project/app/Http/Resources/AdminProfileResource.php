<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'gender' => $this->gender == 1 ? 'ذكر' : 'أنثى',
            'date_of_birth' => $this->date_of_birth,
            'profile_picture' => $this->profile_picture_url, // يستخدم الـ Accessor الموجود في موديل User

            'role' => $this->role->value,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
