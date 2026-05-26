<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource; // This is the User model

        return [
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'phone'         => $user->phone,
            'date_of_birth' => $user->date_of_birth,
            'address'       => $user->address,
            'profile_picture' => $user->profile_picture_url ?? null,
            'gender'        => $user->gender,
            'role'          => $user->role->value,
            'is_active'     => $user->is_active,

            'employee' => $user->employee ? [
                'id'               => $user->employee->id,
                'secondary_phone'  => $user->employee->secondary_phone,
                'marital_status'   => $user->employee->marital_status,
                'salary'           => $user->employee->salary,
                'hire_date'        => $user->employee->hire_date,
            ] : null,

            // Dentist specific data (if the employee is a dentist)
            'dentist' => $user->employee?->dentist ? [
                'id'                  => $user->employee->dentist->id,
                'specialization_id'   => $user->employee->dentist->specialization_id,
                'specialization_name' => $user->employee->dentist->specialization?->name,
                'license_number'      => $user->employee->dentist->license_number,
                'years_of_experience' => $user->employee->dentist->years_of_experience,
                'bio'                 => $user->employee->dentist->bio,
                'average_rating'      => $user->employee->dentist->average_rating,
            ] : null,

            // Related Data
            'educations' => $user->employee?->educations->map(fn($edu) => [
                'id'              => $edu->id,
                'degree'          => $edu->degree,
                'institution'     => $edu->institution,
                'completion_date' => $edu->completion_date,
                'grade'           => $edu->grade,
            ]),

            'experiences' => $user->employee?->experiences->map(fn($exp) => [
                'id'         => $exp->id,
                'title'      => $exp->title,
                'company'    => $exp->company,
                'start_date' => $exp->start_date,
                'end_date'   => $exp->end_date,
            ]),

            'trainings' => $user->employee?->trainings->map(fn($training) => [
                'id'           => $training->id,
                'course_title' => $training->course_title,
                'trainer'      => $training->trainer,
                'course_date'  => $training->course_date,
            ]),

            'skills' => $user->employee?->skills->map(fn($skill) => [
                'id'         => $skill->id,
                'skill_name' => $skill->skill_name,
            ]),

            'created_at' => $user->created_at->format('Y-m-d H:i'),
            'updated_at' => $user->updated_at->format('Y-m-d H:i'),
        ];
    }
}
