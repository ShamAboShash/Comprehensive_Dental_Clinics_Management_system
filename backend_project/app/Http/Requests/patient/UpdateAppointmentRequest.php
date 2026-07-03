<?php
namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'appointment_time' => ['required', 'date', 'after:now'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_time.required' => 'يرجى تحديد وقت الموعد الجديد.',
            'appointment_time.date'     => 'صيغة الوقت غير صحيحة.',
            'appointment_time.after'    => 'يجب أن يكون وقت الموعد الجديد في المستقبل.',
        ];
    }
}
