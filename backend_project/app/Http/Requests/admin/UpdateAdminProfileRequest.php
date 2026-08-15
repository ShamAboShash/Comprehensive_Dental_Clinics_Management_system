<?php

namespace App\Http\Requests\admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => ['sometimes', 'string', 'max:255'],
            'email'           => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'phone'           => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('users', 'phone')->ignore($this->user()->id),
            ],
            'date_of_birth'   => ['sometimes', 'nullable', 'date'],
            'address'         => ['sometimes', 'nullable', 'string', 'max:500'],
            'gender'          => ['sometimes', 'integer', 'in:1,2'],
            'profile_picture' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
