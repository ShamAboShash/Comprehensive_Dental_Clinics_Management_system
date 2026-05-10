<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\EmployeeExperience;

class EmployeeExperienceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();

        foreach ($employees as $employee) {
            EmployeeExperience::create([
                'employee_id' => $employee->id,
                'title' => 'سكرتيرة / مساعد إداري',
                'company' => 'عيادة الابتسامة',
                'start_date' => '2021-09-01',
                'end_date' => '2023-05-30',
            ]);

            EmployeeExperience::create([
                'employee_id' => $employee->id,
                'title' => 'مساعد إداري',
                'company' => 'مستشفى الشام',
                'start_date' => '2020-01-01',
                'end_date' => '2021-08-31',
            ]);
        }

        // $this->command->info('✅ Employee Experiences seeded!');
    }
}
