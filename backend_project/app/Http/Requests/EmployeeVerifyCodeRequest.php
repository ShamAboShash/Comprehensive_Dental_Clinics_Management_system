<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeVerifyCodeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'             => ['required', 'email'],
            'verification_code' => ['required', 'digits:6'],
        ];
    }
}
