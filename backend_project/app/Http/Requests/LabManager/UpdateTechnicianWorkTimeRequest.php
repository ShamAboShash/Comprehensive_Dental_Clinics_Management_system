<?php

namespace App\Http\Requests\LabManager;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTechnicianWorkTimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shift_start' => 'required|date_format:H:i',
            'shift_end'   => 'required|date_format:H:i|after:shift_start',
        ];
    }

    public function messages(): array
    {
        return [
            'shift_start.required'    => 'وقت بداية الدوام مطلوب',
            'shift_start.date_format' => 'صيغة وقت البداية غير صحيحة',
            'shift_end.required'      => 'وقت نهاية الدوام مطلوب',
            'shift_end.date_format'   => 'صيغة وقت النهاية غير صحيحة',
            'shift_end.after'         => 'وقت النهاية يجب أن يكون بعد وقت البداية',
        ];
    }
}
