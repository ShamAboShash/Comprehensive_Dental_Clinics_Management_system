<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\EmployeeEducation;

class EmployeeEducationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();

        foreach ($employees as $employee) {
            EmployeeEducation::create([
                'employee_id' => $employee->id,
                'degree' => 'بكالوريوس إدارة مكاتب',
                'institution' => 'جامعة دمشق',
                'completion_date' => '2020-06-15',
                'grade' => 'جيد جداً',
            ]);

            EmployeeEducation::create([
                'employee_id' => $employee->id,
                'degree' => 'دبلوم إدارة صحية',
                'institution' => 'معهد الإدارة الصحية',
                'completion_date' => '2022-01-20',
                'grade' => 'ممتاز',
            ]);
        }

        // $this->command->info('✅ Employee Educations seeded!');

    }
}
