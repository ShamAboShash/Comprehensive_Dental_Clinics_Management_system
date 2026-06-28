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
            'status'            => 'sometimes|in:completed,missed',
            'notes'             => 'nullable|string|max:2000',
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
