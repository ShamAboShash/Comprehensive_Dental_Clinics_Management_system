<?php

namespace App\Http\Requests\Secretary;

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
            'patient_id'   => 'required|exists:patients,id',
            'title'        => 'required|string|max:255',
            'description'  => 'required|string|max:2000',
            'phone_number' => 'nullable|string|max:20',
            'priority'     => 'nullable|in:low,medium,high',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required'  => 'معرّف المريض مطلوب.',
            'patient_id.exists'    => 'المريض غير موجود.',
            'title.required'       => 'عنوان الشكوى مطلوب.',
            'description.required' => 'تفاصيل الشكوى مطلوبة.',
            'priority.in'          => 'الأولوية يجب أن تكون low أو medium أو high.',
        ];
    }
}
