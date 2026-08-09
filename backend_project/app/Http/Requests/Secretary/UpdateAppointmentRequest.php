<?php

namespace App\Http\Requests\Secretary;

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
            'patient_id'          => 'sometimes|exists:patients,id',
            'dentist_id'          => 'sometimes|exists:dentists,id',
            'appointment_type_id' => 'nullable|exists:appointment_types,id',
            'treatment_id'        => 'nullable|exists:treatments,id',
            'type'                => 'sometimes|in:emergency,new_treatment,continue_treatment,walk_in',
            'appointment_time'    => 'sometimes|date',
            'notes'               => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.exists' => 'المريض غير موجود.',
            'dentist_id.exists' => 'الطبيب غير موجود.',
            'type.in'           => 'نوع الموعد غير صالح.',
        ];
    }
}
