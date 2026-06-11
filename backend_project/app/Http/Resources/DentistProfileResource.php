<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DentistProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,

            // User
            'name'                => $this->employee->user->name,
            'email'               => $this->employee->user->email,
            'phone'               => $this->employee->user->phone,
            'gender'              => $this->employee->user->gender ,
            'date_of_birth'       => $this->employee->user->date_of_birth,
            'profile_picture'     => $this->employee->user->profile_picture_url,
            'is_active'           => (bool) $this->employee->user->is_active,

            // Employee
            'salary'              => $this->employee->salary,
            'hire_date'           => $this->employee->hire_date,
            'marital_status'      => $this->employee->marital_status,
            'secondary_phone'     => $this->employee->secondary_phone,

            // Dentist
            'specialization'      => $this->specialization->name ?? 'عام',
            'license_number'      => $this->license_number,
            'years_of_experience' => $this->years_of_experience,
            'average_rating'      => $this->average_rating,
            'bio'                 => $this->bio,

            // work scedule
            'work_schedule'       => $this->workSchedules->map(fn($schedule) => [
                'day'        => $schedule->day_of_week,
                'start_time' => $schedule->start_time,
                'end_time'   => $schedule->end_time,
            ]),

            'educations'  => $this->employee->educations->map(fn($edu) => [
                'id'              => $edu->id,
                'degree'          => $edu->degree,
                'institution'     => $edu->institution,
                'completion_date' => $edu->completion_date,
                'grade'           => $edu->grade,
            ]),

            'experiences' => $this->employee->experiences->map(fn($exp) => [
                'id'         => $exp->id,
                'title'      => $exp->title,
                'company'    => $exp->company,
                'start_date' => $exp->start_date,
                'end_date'   => $exp->end_date,
            ]),

            'trainings' => $this->employee->trainings->map(fn($t) => [
                'id'           => $t->id,
                'course_title' => $t->course_title,
                'trainer'      => $t->trainer,
                'course_date'  => $t->course_date,
            ]),

            'skills' => $this->employee->skills->pluck('skill_name'),
        ];
    }


}
