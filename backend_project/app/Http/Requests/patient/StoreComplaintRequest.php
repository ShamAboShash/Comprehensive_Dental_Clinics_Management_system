<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string', 'max:2000'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'priority'     => ['nullable', 'in:low,medium,high'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'يرجى إدخال عنوان الشكوى.',
            'description.required' => 'يرجى إدخال تفاصيل الشكوى.',
            'priority.in'          => 'قيمة الأولوية غير صحيحة.',
        ];
    }
}
