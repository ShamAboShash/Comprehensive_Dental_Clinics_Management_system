<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\EmployeeTraining;

class EmployeeTrainingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();

        foreach ($employees as $employee) {
            EmployeeTraining::create([
                'employee_id' => $employee->id,
                'course_title' => 'دورة إدارة عيادات الأسنان الشاملة',
                'trainer' => 'مركز التدريب الطبي المتقدم',
                'course_date' => '2023-02-10',
            ]);

            EmployeeTraining::create([
                'employee_id' => $employee->id,
                'course_title' => 'برنامج إدارة المواعيد الإلكترونية',
                'trainer' => 'شركة الحلول الطبية',
                'course_date' => '2024-01-20',
            ]);
        }

        // $this->command->info('✅ Employee Trainings seeded!');
    }
}
