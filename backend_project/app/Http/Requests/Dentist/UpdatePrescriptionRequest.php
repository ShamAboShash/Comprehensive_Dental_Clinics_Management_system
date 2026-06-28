<?php

namespace App\Http\Requests\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'notes'                          => 'nullable|string|max:2000',

            'medications'                    => 'sometimes|array|min:1',
            'medications.*.medication_id'    => 'required|exists:medications,id',
            'medications.*.dosage'           => 'required|string|max:255',
            'medications.*.frequency'        => 'required|string|max:255',
            'medications.*.duration'         => 'nullable|string|max:255',
            'medications.*.notes'            => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'medications.*.medication_id.required'  => 'معرّف الدواء مطلوب.',
            'medications.*.medication_id.exists'    => 'الدواء المحدد غير موجود في النظام.',
            'medications.*.dosage.required'         => 'الجرعة مطلوبة.',
            'medications.*.frequency.required'      => 'تكرار الجرعة مطلوب.',
        ];
    }
}
