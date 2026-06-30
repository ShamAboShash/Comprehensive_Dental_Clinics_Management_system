<?php

namespace App\Http\Requests\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class AddMedicalInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

     public function rules(): array
    {
       return [
            'allergies'                       => ['array'],
            'allergies.*'                     => ['string'],

            'chronic_diseases'               => ['array'],
            'chronic_diseases.*.category_id' => ['required', 'exists:disease_categories,id'],
            'chronic_diseases.*.details'         => 'nullable|string|max:1000',
            'chronic_diseases.*.problem_name' => ['required', 'string'],
            'chronic_diseases.*.severity'     => ['nullable', 'string'],

            'medications'                     => ['array'],
            'medications.*.medication_id'     => ['required', 'exists:medications,id'],
            'medications.*.dosage'            => ['nullable', 'string'],
            'medications.*.frequency'         => ['nullable', 'string'],
            'medications.*.notes'             => 'nullable|string|max:500',

        ];
    }

    public function messages(): array
    {
        return [
            'allergies.*.required'                       => 'اسم الحساسية مطلوب.',
            'chronic_diseases.*.problem_name.required'   => 'اسم المشكلة الصحية مطلوب.',
            'chronic_diseases.*.severity.in'             => 'درجة الخطورة يجب أن تكون: mild أو moderate أو severe.',
            'medications.*.medication_id.required'       => 'معرّف الدواء مطلوب.',
            'medications.*.medication_id.exists'         => 'الدواء المحدد غير موجود في النظام.',
        ];
    }
}
