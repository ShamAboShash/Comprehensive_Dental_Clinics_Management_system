<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PatientVerifyEmailRequest extends FormRequest
{

public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'              => 'required|email|exists:users,email',
            'verification_code'  => 'required|digits:6',
        ];
    }
}
