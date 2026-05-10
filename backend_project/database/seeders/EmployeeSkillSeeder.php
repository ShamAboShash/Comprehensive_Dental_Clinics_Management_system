<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\EmployeeSkill;

class EmployeeSkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();
        $commonSkills = ['Microsoft Office', 'إدارة المواعيد', 'مهارات التواصل', 'إدارة الملفات الطبية', 'لغة إنجليزية', 'استخدام برامج العيادات'];

        foreach ($employees as $employee) {
            foreach ($commonSkills as $skill) {
                EmployeeSkill::create([
                    'employee_id' => $employee->id,
                    'skill_name' => $skill,
                ]);
            }
        }

        // $this->command->info('✅ Employee Skills seeded!');
    }
}
