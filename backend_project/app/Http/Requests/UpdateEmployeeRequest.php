<?php

// namespace App\Http\Requests;

// use Illuminate\Contracts\Validation\ValidationRule;
// use Illuminate\Foundation\Http\FormRequest;
// use Illuminate\Validation\Rule;
// use App\Enums\UserRole;
// use Illuminate\Validation\Rules\Enum;

// class UpdateEmployeeRequest extends FormRequest
// {
//     /**
//      * Determine if the user is authorized to make this request.
//      */
//     public function authorize(): bool
//     {
//         return auth()->user()?->role === UserRole::ADMIN;    }

//     /**
//      * Get the validation rules that apply to the request.
//      *
//      * @return array<string, ValidationRule|array<mixed>|string>
//      */
//     public function rules(): array
//     {
//         $employeeId = $this->route('employee'); // This will be the user_id or employee_id?

//         return [
//             'name'              => 'sometimes|string|max:255',
//             'email'             => ['sometimes', 'email', Rule::unique('users')->ignore($this->route('employee'))],
//             'phone'             => ['sometimes', 'string', Rule::unique('users')->ignore($this->route('employee'))],
//             'date_of_birth'     => 'nullable|date|before:today',
//             'address'           => 'nullable|string|max:500',
//             'gender'            => 'nullable|integer|in:1,2',

//             // Employee fields
//             'secondary_phone'   => 'nullable|string',
//             'marital_status'    => 'nullable|string|in:single,married,divorced,widowed',
//             'salary'            => 'nullable|numeric|min:0',
//             'hire_date'         => 'nullable|date',
//             'is_active'         => 'boolean',

//             // Dentist specific
//             'role'              => ['sometimes', new Enum(UserRole::class)],
//             'specialization_id' => 'required_if:role,' . UserRole::DENTIST->value . '|exists:specializations,id',
//             'work_hours'        => 'nullable|array',
//             'license_number'    => ['nullable', 'string', Rule::unique('dentists')->ignore($this->route('employee'), 'employee_id')],
//             'years_of_experience' => 'nullable|integer|min:0',
//             'commission_rate'    => 'nullable|numeric|min:0|max:100',

//             // Related data (we will handle arrays)
//             'educations'        => 'nullable|array',
//             'experiences'       => 'nullable|array',
//             'trainings'         => 'nullable|array',
//             'skills'            => 'nullable|array',
//         ];
//     }
// }



namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\UserRole;
use Illuminate\Validation\Rules\Enum;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->role === UserRole::ADMIN;
    }

    public function rules(): array
    {
        // The route parameter is a User model (from DentistController: User $user)
        $userId = $this->route('user')?->id ?? $this->route('employee')?->id;

        // Get employee id for ignoring dentist license_number unique check
        $employeeId = $this->route('user')?->employee?->id
            ?? $this->route('employee')?->employee?->id;

        return [
            // User fields
            'name'            => 'sometimes|string|max:255',
            'email'           => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone'           => ['sometimes', 'string', Rule::unique('users', 'phone')->ignore($userId)],
            'date_of_birth'   => 'nullable|date|before:today',
            'address'         => 'nullable|string|max:500',
            'gender'          => 'nullable|integer|in:1,2',
            'is_active'       => 'sometimes|boolean',

            // Employee fields
            'secondary_phone' => 'nullable|string|max:20',
            'marital_status'  => 'nullable|string|in:single,married,divorced,widowed',
            'salary'          => 'nullable|numeric|min:0',
            'hire_date'       => 'nullable|date',

            // Dentist specific
            'specialization_id'   => 'sometimes|exists:specializations,id',
            'license_number'      => [
                'nullable',
                'string',
                Rule::unique('dentists', 'license_number')->ignore($employeeId, 'employee_id'),
            ],
            'years_of_experience' => 'nullable|integer|min:0',
            'commission_rate'     => 'nullable|numeric|min:0|max:100',
            'bio'                 => 'nullable|string',

            // Related data
            'educations'               => 'nullable|array',
            'educations.*.degree'      => 'required_with:educations|string|max:255',
            'educations.*.institution' => 'required_with:educations|string|max:255',
            'educations.*.completion_date' => 'required_with:educations|date',
            'educations.*.grade'       => 'nullable|string|max:50',

            'experiences'              => 'nullable|array',
            'experiences.*.title'      => 'required_with:experiences|string|max:255',
            'experiences.*.company'    => 'required_with:experiences|string|max:255',
            'experiences.*.start_date' => 'required_with:experiences|date',
            'experiences.*.end_date'   => 'nullable|date|after:experiences.*.start_date',

            'trainings'                    => 'nullable|array',
            'trainings.*.course_title'     => 'required_with:trainings|string|max:255',
            'trainings.*.trainer'          => 'required_with:trainings|string|max:255',
            'trainings.*.course_date'      => 'required_with:trainings|date',

            'skills'                   => 'nullable|array',
            'skills.*.skill_name'      => 'required_with:skills|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'              => 'This email is already taken.',
            'phone.unique'              => 'This phone number is already taken.',
            'license_number.unique'     => 'This license number is already registered.',
            'specialization_id.exists'  => 'The selected specialization does not exist.',

            'educations.*.degree.required_with'          => 'Degree is required for each education entry.',
            'educations.*.institution.required_with'     => 'Institution is required for each education entry.',
            'educations.*.completion_date.required_with' => 'Completion date is required for each education entry.',

            'experiences.*.title.required_with'      => 'Title is required for each experience entry.',
            'experiences.*.company.required_with'    => 'Company is required for each experience entry.',
            'experiences.*.start_date.required_with' => 'Start date is required for each experience entry.',
            'experiences.*.end_date.after'           => 'End date must be after start date.',

            'trainings.*.course_title.required_with' => 'Course title is required for each training entry.',
            'trainings.*.trainer.required_with'      => 'Trainer is required for each training entry.',
            'trainings.*.course_date.required_with'  => 'Course date is required for each training entry.',

            'skills.*.skill_name.required_with' => 'Skill name is required for each skill entry.',
        ];
    }
}
