<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()?->role === UserRole::ADMIN;    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employeeId = $this->route('employee'); // This will be the user_id or employee_id?

        return [
            'name'              => 'sometimes|string|max:255',
            'email'             => ['sometimes', 'email', Rule::unique('users')->ignore($this->route('employee'))],
            'phone'             => ['sometimes', 'string', Rule::unique('users')->ignore($this->route('employee'))],
            'date_of_birth'     => 'nullable|date|before:today',
            'address'           => 'nullable|string|max:500',
            'gender'            => 'nullable|integer|in:1,2',

            // Employee fields
            'secondary_phone'   => 'nullable|string',
            'marital_status'    => 'nullable|string|in:single,married,divorced,widowed',
            'salary'            => 'nullable|numeric|min:0',
            'hire_date'         => 'nullable|date',
            'is_active'         => 'boolean',

            // Dentist specific
            'role'              => ['sometimes', new Enum(UserRole::class)],
            'specialization_id' => 'required_if:role,' . UserRole::DENTIST->value . '|exists:specializations,id',
            'work_hours'        => 'nullable|array',
            'license_number'    => ['nullable', 'string', Rule::unique('dentists')->ignore($this->route('employee'), 'employee_id')],
            'years_of_experience' => 'nullable|integer|min:0',

            // Related data (we will handle arrays)
            'educations'        => 'nullable|array',
            'experiences'       => 'nullable|array',
            'trainings'         => 'nullable|array',
            'skills'            => 'nullable|array',
        ];
    }
}
