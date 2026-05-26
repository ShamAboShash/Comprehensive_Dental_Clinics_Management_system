<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // User fields
            'name'            => ['sometimes', 'string', 'max:255'],
            'phone'           => ['sometimes', 'string', 'unique:users,phone,' . $this->user()->id],
            'date_of_birth'   => ['sometimes', 'date'],
            'address'         => ['sometimes', 'string'],
            'gender'          => ['sometimes', 'integer', 'in:1,2'],
            'profile_picture' => ['sometimes', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],

            // Employee fields
            'secondary_phone' => ['sometimes', 'string'],
            'marital_status'  => ['sometimes', 'string'],
        ];
    }
}
