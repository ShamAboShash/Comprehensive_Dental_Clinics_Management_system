<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dentist_id'          => ['required', 'integer', 'exists:dentists,id'],
            'appointment_type_id' => ['required', 'integer', 'exists:appointment_types,id'],
            'appointment_time'    => ['required', 'date', 'after:now'],
            'type'                => ['required', 'in:emergency,new_treatment,continue_treatment,walk_in'],
            'treatment_id'        => ['nullable', 'integer', 'exists:treatments,id', 'required_if:type,continue_treatment'],
            'notes'               => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'dentist_id.required'          => 'يرجى تحديد الطبيب.',
            'dentist_id.exists'            => 'الطبيب غير موجود.',
            'appointment_type_id.required' => 'يرجى تحديد نوع الموعد.',
            'appointment_type_id.exists'   => 'نوع الموعد غير موجود.',
            'appointment_time.required'    => 'يرجى تحديد وقت الموعد.',
            'appointment_time.after'       => 'يجب أن يكون وقت الموعد في المستقبل.',
            'type.required'                => 'يرجى تحديد نوع الحجز.',
            'type.in'                      => 'نوع الحجز غير صحيح.',
            'treatment_id.required_if'     => 'يرجى تحديد العلاج المرتبط بمتابعة العلاج.',
            'treatment_id.exists'          => 'العلاج غير موجود.',
        ];
    }
}
