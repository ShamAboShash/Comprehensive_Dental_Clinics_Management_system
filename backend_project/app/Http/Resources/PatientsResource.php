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
            // 'user_id' => $this->user_id,

            // بيانات الـ User
            'name'            => $this->user->name,
            'email'           => $this->user->email,
            'phone'           => $this->user->phone,
            'gender'          => $this->user->gender == 1 ? 'ذكر' : 'أنثى',
            // 'date_of_birth'   => $this->user->date_of_birth,
            // 'address'         => $this->user->address,
            'profile_picture' => $this->user->profile_picture_url,
            // 'is_active'       => (bool) $this->user->is_active,

            // بيانات الـ Patient
            // 'emergency_contact_name'     => $this->emergency_contact_name,
            // 'emergency_contact_relation' => $this->emergency_contact_relation,
            // 'emergency_contact_phone'    => $this->emergency_contact_phone,
            // 'is_pregnant'                => (bool) $this->is_pregnant,
            // 'is_breastfeeding'           => (bool) $this->is_breastfeeding,
            // 'is_smoker'                  => (bool) $this->is_smoker,
            // 'drinks_alcohol_frequently'  => (bool) $this->drinks_alcohol_frequently,
            // 'teeth_cleaning_frequency'   => $this->teeth_cleaning_frequency,
        ];
    }
}
