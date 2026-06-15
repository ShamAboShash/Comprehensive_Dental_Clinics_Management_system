<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeWorkSchedule;
use Illuminate\Database\Seeder;

class EmployeeWorkScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch all existing employees
        $employees = Employee::all();

        if ($employees->isEmpty()) {
            $this->command->warn('No employees found! Please seed the Employee table first.');
            return;
        }

        // Define standard Monday - Friday work days
        $workDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        foreach ($employees as $employee) {
            foreach ($workDays as $day) {
                EmployeeWorkSchedule::firstOrCreate([
                    'employee_id' => $employee->id,
                    'day_of_week' => $day,
                ], [
                    'start_time' => '09:00:00',
                    'end_time'   => '17:00:00',
                ]);
            }
        }

    }
}
