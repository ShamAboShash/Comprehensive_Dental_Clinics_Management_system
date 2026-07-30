<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\UserRole;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return true;
        return auth()->user()->role === UserRole::ADMIN;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
       return [
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|unique:users,email',
            'phone'             => 'required|string|unique:users,phone',
            'date_of_birth'     => 'nullable|date|before:today',
            'address'           => 'nullable|string|max:500',
            'gender'            => 'nullable|integer|in:1,2',
            'role'              => ['required', new Enum(UserRole::class)],


            // Employee fields
            'secondary_phone'   => 'nullable|string',
            'marital_status'    => 'nullable|string|in:single,married,divorced,widowed',
            'salary'            => 'nullable|numeric|min:0',
            'hire_date'         => 'nullable|date',

            // Dentist specific fields (required only if role is dentist)
            'specialization_id' => 'required_if:role,' . UserRole::DENTIST->value . '|exists:specializations,id',
            'work_hours'        => 'nullable|array',
            'license_number'    => 'required_if:role,' . UserRole::DENTIST->value . '|string|unique:dentists,license_number',
            'years_of_experience' => 'nullable|integer|min:0',

            'work_schedules'                 => 'nullable|array',
            'work_schedules.*.day_of_week'   => 'required|string|in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            'work_schedules.*.start_time'    => 'required|date_format:H:i',
            'work_schedules.*.end_time'      => 'required|date_format:H:i|after:work_schedules.*.start_time',


            // Education, Experience, etc.
            'educations'        => 'nullable|array',
            'educations.*.degree' => 'required|string',
            'educations.*.institution' => 'required|string',
            'educations.*.completion_date' => 'required|date',
            'educations.*.grade' => 'nullable|string',

            'experiences'       => 'nullable|array',
            'experiences.*.title' => 'required|string',
            'experiences.*.company' => 'required|string',
            'experiences.*.start_date' => 'required|date',
            'experiences.*.end_date' => 'nullable|date|after:experiences.*.start_date',

            'trainings'         => 'nullable|array',
            'trainings.*.course_title' => 'required|string',
            'trainings.*.trainer' => 'nullable|string',
            'trainings.*.course_date' => 'nullable|date',

            'skills'            => 'nullable|array',
            'skills.*.skill_name' => 'required|string',
        ];
    }
}
