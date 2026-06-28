<?php

namespace App\Http\Requests\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'           => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:500',
        ];
    }
    public function messages(): array
    {
        return [
            'status.required'                => 'حقل الحالة مطلوب.',
            'status.in'                      => 'الحالة المدخلة غير صالحة.',
            'rejection_reason.required_if'   => 'سبب الرفض مطلوب عند رفض الموعد.',
        ];
    }
}
