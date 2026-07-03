<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class AvailableSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dentist_id' => ['required', 'integer', 'exists:dentists,id'],
            'date'       => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'dentist_id.required' => 'يرجى تحديد الطبيب.',
            'dentist_id.exists'   => 'الطبيب غير موجود.',
            'date.required'       => 'يرجى تحديد التاريخ.',
            'date.after_or_equal' => 'لا يمكن اختيار تاريخ في الماضي.',
        ];
    }
}
