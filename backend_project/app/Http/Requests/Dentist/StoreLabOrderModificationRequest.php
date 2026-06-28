<?php

namespace App\Http\Requests\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabOrderModificationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'dentist_response' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'dentist_response.required' => 'رد الطبيب على طلب التعديل مطلوب.',
        ];
    }
}
