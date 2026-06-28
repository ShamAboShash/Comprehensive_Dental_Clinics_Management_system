<?php

namespace App\Http\Requests\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class StoreTreatmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // 'patient_id'            => 'required|exists:patients,id',
            'treatment_type_id'     => 'nullable|exists:treatment_types,id',
            'total_sessions_needed' => 'required|integer|min:1',
            'notes'                 => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required'            => 'معرّف المريض مطلوب.',
            'patient_id.exists'              => 'المريض غير موجود.',
            'total_sessions_needed.required' => 'عدد الجلسات المطلوبة مطلوب.',
            'total_sessions_needed.min'      => 'يجب أن يكون عدد الجلسات 1 على الأقل.',
        ];
    }
}
