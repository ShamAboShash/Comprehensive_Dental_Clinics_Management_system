<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EmployeeProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // 1. بيانات الحساب الأساسية من موديل الـ User
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'gender' => $this->user->gender == 1 ? 'ذكر' : 'أنثى',
            'role' => $this->user->role->value, // يستخدم الـ Accessor الموجود في موديل User
            'is_active' => $this->user->is_active,
            'date_of_birth' => $this->user->date_of_birth,
            'profile_picture'   => $this->user?->profile_picture_url,
            'secondary_phone' => $this->secondary_phone,
            'marital_status' => $this->marital_status,
            'salary' => $this->salary,
            'hire_date' => $this->hire_date,


            'schedule'   => $this->whenLoaded('workSchedules', fn() =>
                $this->workSchedules->map(fn($s) => [
                    'day'        => $s->day_of_week,
                    'start_time' => $s->start_time,
                    'end_time'   => $s->end_time,
                ])
            ),


            'educations' => $this->educations->map(function ($edu) {
                return [
                    'id' => $edu->id,
                    'employee_id' => $edu->employee_id,
                    'degree' => $edu->degree,
                    'institution' => $edu->institution,
                    'completion_date' => $edu->completion_date,
                    'grade' => $edu->grade,
                ];
            }),

            // تنسيق الخبرات لإزالة حقول الوقت
            'experiences' => $this->experiences->map(function ($exp) {
                return [
                    'id' => $exp->id,
                    'employee_id' => $exp->employee_id,
                    'title' => $exp->title,
                    'company' => $exp->company,
                    'start_date' => $exp->start_date,
                    'end_date' => $exp->end_date,
                ];
            }),

            'trainings' => $this->trainings->map(fn($t) => [
                'id'           => $t->id,
                'course_title' => $t->course_title,
                'trainer'      => $t->trainer,
                'course_date'  => $t->course_date,
            ]),

            // المهارات
            'skills' => $this->skills->pluck('skill_name'),
        ];
    }
}
