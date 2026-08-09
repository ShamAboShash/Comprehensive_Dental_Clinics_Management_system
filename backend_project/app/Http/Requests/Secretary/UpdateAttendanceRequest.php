<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'    => 'nullable|in:present,absent',
            'check_in'  => 'nullable|date_format:H:i,H:i:s',
            'check_out' => 'nullable|date_format:H:i,H:i:s',
            'notes'     => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in'             => 'الحالة يجب أن تكون present أو absent.',
            'check_in.date_format'  => 'صيغة وقت الحضور غير صحيحة.',
            'check_out.date_format' => 'صيغة وقت الانصراف غير صحيحة.',
        ];
    }
}
