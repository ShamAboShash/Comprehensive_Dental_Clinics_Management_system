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
            'allergies'              => 'nullable|array',
            'allergies.*'            => 'required|string|max:255',

            'chronic_diseases'                   => 'nullable|array',
            'chronic_diseases.*.category_id'     => 'nullable|exists:disease_categories,id',
            'chronic_diseases.*.problem_name'    => 'required|string|max:255',
            'chronic_diseases.*.details'         => 'nullable|string|max:1000',
            'chronic_diseases.*.severity'        => 'nullable|in:mild,moderate,severe',

            'medications'                        => 'nullable|array',
            'medications.*.medication_id'        => 'required|exists:medications,id',
            'medications.*.dosage'               => 'nullable|string|max:255',
            'medications.*.frequency'            => 'nullable|string|max:255',
            'medications.*.notes'                => 'nullable|string|max:500',
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
