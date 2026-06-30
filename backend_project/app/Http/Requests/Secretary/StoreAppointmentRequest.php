<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'          => 'required|exists:patients,id',
            'dentist_id'          => 'required|exists:dentists,id',
            'appointment_type_id' => 'nullable|exists:appointment_types,id',
            'treatment_id'        => 'nullable|exists:treatments,id',
            'type'                => 'required|in:emergency,new_treatment,continue_treatment,walk_in',
            'status'              => 'sometimes|in:pending,pending_secretary,approved',
            'appointment_time'    => 'required|date',
            'notes'               => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required'       => 'معرّف المريض مطلوب.',
            'patient_id.exists'         => 'المريض غير موجود.',
            'dentist_id.required'       => 'معرّف الطبيب مطلوب.',
            'dentist_id.exists'         => 'الطبيب غير موجود.',
            'type.required'             => 'نوع الموعد مطلوب.',
            'type.in'                   => 'نوع الموعد غير صالح.',
            'appointment_time.required' => 'وقت الموعد مطلوب.',
        ];
    }
}
