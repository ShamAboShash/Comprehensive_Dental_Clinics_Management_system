<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PatientRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                        => 'required|string|max:255',
            'email'                       => 'required|email|unique:users,email',
            'phone'                       => 'required|string|unique:users,phone',
            'password'                    => 'required|string|min:8|confirmed',
            'date_of_birth'               => 'required|date|before:today',
            'gender'                      => 'required|integer|in:1,2',
            'address'                     => 'nullable|string',
            'profile_picture'             => 'nullable|image|max:2048', 

            // Emergency Contact
            'emergency_contact_name'      => 'required|string',
            'emergency_contact_relation'  => 'required|string',
            'emergency_contact_phone'     => 'required|string',

            // Optional
            'is_pregnant'                 => 'boolean',
            'is_breastfeeding'            => 'boolean',
            'is_smoker'                   => 'boolean',
            'drinks_alcohol_frequently'   => 'boolean',
            'teeth_cleaning_frequency'    => 'nullable|in:once,twice,more_than_twice',
        ];
    }
}
