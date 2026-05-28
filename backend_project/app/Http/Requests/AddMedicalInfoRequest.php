<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddMedicalInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
       return [
            'allergies'                       => ['array'],
            'allergies.*'                     => ['string'],

            'chronic_diseases'                => ['array'],
            'chronic_diseases.*.category_id' => ['required', 'exists:disease_categories,id'],
            'chronic_diseases.*.problem_name' => ['required', 'string'],
            'chronic_diseases.*.severity'     => ['nullable', 'string'],

            'medications'                     => ['array'],
            'medications.*.medication_id'     => ['required', 'exists:medications,id'],
            'medications.*.dosage'            => ['nullable', 'string'],
            'medications.*.frequency'         => ['nullable', 'string'],
        ];
    }
}
