<?php

namespace App\Http\Requests\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'appointment_id'    => 'required|exists:appointments,id|unique:treatment_sessions,appointment_id',
            'actual_start_time' => 'nullable|date',
            'actual_end_time'   => 'nullable|date|after:actual_start_time',
            'status'            => 'sometimes|in:completed,incompleted',
            'notes'             => 'nullable|string|max:2000',

            // Tooth treatments included at session creation
            'tooth_treatments'                  => 'nullable|array',
            'tooth_treatments.*.tooth_number'   => 'required|integer|min:11|max:48',
            'tooth_treatments.*.procedure_id'   => 'required|exists:procedures,id',
            'tooth_treatments.*.notes'          => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_id.required' => 'معرّف الموعد مطلوب.',
            'appointment_id.exists'   => 'الموعد غير موجود.',
            'appointment_id.unique'   => 'هذا الموعد مرتبط بجلسة أخرى بالفعل.',
            'actual_end_time.after'   => 'وقت انتهاء الجلسة يجب أن يكون بعد وقت البداية.',
        ];
    }
}
