<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // User fields
            'name'            => ['sometimes', 'string', 'max:255'],
            'phone'           => ['sometimes', 'string', 'unique:users,phone,' . $this->user()->id],
            'date_of_birth'   => ['sometimes', 'date'],
            'address'         => ['sometimes', 'string'],
            'gender'          => ['sometimes', 'integer', 'in:1,2'],
            'profile_picture' => ['sometimes', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],

            // Employee fields
            'secondary_phone' => ['sometimes', 'string'],
            'marital_status'  => ['sometimes', 'string'],

            // Nested relations (add/update only, do not delete)
            'educations' => ['sometimes', 'array'],
            'educations.*.id' => ['nullable', 'integer', 'min:1'],
            'educations.*.degree' => ['required', 'string', 'max:255'],
            'educations.*.institution' => ['required', 'string', 'max:255'],
            'educations.*.completion_date' => ['nullable', 'date'],
            'educations.*.grade' => ['nullable', 'string', 'max:255'],

            'experiences' => ['sometimes', 'array'],
            'experiences.*.id' => ['nullable', 'integer', 'min:1'],
            'experiences.*.title' => ['required', 'string', 'max:255'],
            'experiences.*.company' => ['required', 'string', 'max:255'],
            'experiences.*.start_date' => ['nullable', 'date'],
            'experiences.*.end_date' => ['nullable', 'date'],

            'trainings' => ['sometimes', 'array'],
            'trainings.*.id' => ['nullable', 'integer', 'min:1'],
            'trainings.*.course_title' => ['required', 'string', 'max:255'],
            'trainings.*.trainer' => ['nullable', 'string', 'max:255'],
            'trainings.*.course_date' => ['nullable', 'date'],

            'skills' => ['sometimes', 'array'],
            'skills.*.id' => ['nullable', 'integer', 'min:1'],
            'skills.*.skill_name' => ['required', 'string', 'max:255'],
        ];
    }
}

