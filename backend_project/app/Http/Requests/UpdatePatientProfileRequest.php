<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // User fields only — no email, no medical info
            'name'            => ['sometimes', 'string', 'max:255'],
            'phone'           => ['sometimes', 'string', 'unique:users,phone,' . $this->user()->id],
            'date_of_birth'   => ['sometimes', 'date'],
            'address'         => ['sometimes', 'string'],
            'gender'          => ['sometimes', 'integer', 'in:1,2'],
            'profile_picture' => ['sometimes', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],

            // Patient fields
            'emergency_contact_name'     => ['sometimes', 'string'],
            'emergency_contact_relation' => ['sometimes', 'string'],
            'emergency_contact_phone'    => ['sometimes', 'string'],
            'is_pregnant'                => ['sometimes', 'boolean'],
            'is_breastfeeding'           => ['sometimes', 'boolean'],
            'is_smoker'                  => ['sometimes', 'boolean'],
            'drinks_alcohol_frequently'  => ['sometimes', 'boolean'],
            'teeth_cleaning_frequency'   => ['sometimes', 'in:once,twice,more_than_twice'],
        ];
    }
}
