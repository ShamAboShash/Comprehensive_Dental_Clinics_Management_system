<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'         => 'required|in:pending,in_review,resolved,rejected',
            'priority'       => 'nullable|in:low,medium,high',
            'admin_response' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'الحالة مطلوبة.',
            'status.in'       => 'حالة الشكوى غير صالحة.',
            'priority.in'     => 'الأولوية غير صالحة.',
        ];
    }
}
