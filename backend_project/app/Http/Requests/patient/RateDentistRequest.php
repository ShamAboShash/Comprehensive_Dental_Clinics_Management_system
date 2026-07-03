<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class RateDentistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'يرجى تحديد التقييم.',
            'rating.min'      => 'يجب أن يكون التقييم بين 1 و 5.',
            'rating.max'      => 'يجب أن يكون التقييم بين 1 و 5.',
        ];
    }
}
