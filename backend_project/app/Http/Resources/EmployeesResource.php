<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->employee->id,
            'user_id'         =>$this->id,
            'name'            => $this->name,
            'email'           => $this->email,
            'role'            => $this->role->value,
            'profile_picture' => $this->profile_picture_url,
        ];
    }
}
