<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeSetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email'              => 'required|email|exists:users,email',
            'verification_code'  => 'required|digits:6',
            'password'           => 'required|string|min:8'
        ];
    }
}
