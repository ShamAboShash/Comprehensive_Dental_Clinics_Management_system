<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'nullable|date',
            'check_in'    => 'nullable|date_format:H:i,H:i:s',
            'notes'       => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'معرّف الموظف مطلوب.',
            'employee_id.exists'   => 'الموظف غير موجود.',
            'date.date'            => 'صيغة التاريخ غير صحيحة.',
            'check_in.date_format' => 'صيغة وقت الحضور غير صحيحة (مثال: 09:00).',
        ];
    }
}
