<?php

namespace Database\Seeders;

use App\Models\Dentist;
use App\Models\Employee;
use App\Models\EmployeeWorkSchedule;
use App\Models\DentistWorkSchedule;
use App\Models\User;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Registered by: prefer a secretary, fallback to admin
        $registrar = User::whereIn('role', [UserRole::SECRETARY->value, UserRole::ADMIN->value])
            ->where('is_active', true)
            ->first();

        if (!$registrar) {
            $this->command->warn('No secretary/admin user found for registered_by. Skipping AttendanceSeeder.');
            return;
        }

        // Pick >1 dentists (their employees) and >1 non-dentist employees
        $dentistEmployees = Dentist::with('employee')->take(3)->get()->pluck('employee')->filter();
        $staffEmployees   = Employee::whereDoesntHave('dentist')->take(3)->get();

        $employees = $dentistEmployees->merge($staffEmployees)->unique('id');

        if ($employees->isEmpty()) {
            $this->command->warn('No employees found for attendance. Skipping.');
            return;
        }

        $start = Carbon::create(2026, 8, 1)->startOfDay();
        $end   = Carbon::create(2026, 8, 31)->endOfDay();

        $created = 0;

        foreach ($employees as $employee) {
            $employee->loadMissing('dentist');
            $isDentist = $employee->dentist !== null;
            $dentist   = $isDentist ? $employee->dentist : null;

            // Build map of working days → list of shifts (start, end)
            $scheduleMap = [];

            if ($isDentist && $dentist) {
                $schedules = DentistWorkSchedule::where('dentist_id', $dentist->id)->get();
                foreach ($schedules as $sch) {
                    $day = strtolower($sch->day_of_week);
                    $scheduleMap[$day][] = [
                        'start' => $sch->start_time,
                        'end'   => $sch->end_time,
                    ];
                }
            } else {
                // Ensure employee has work schedules (Mon-Fri 09-17) if none exist
                $workDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                foreach ($workDays as $day) {
                    EmployeeWorkSchedule::firstOrCreate(
                        ['employee_id' => $employee->id, 'day_of_week' => $day],
                        ['start_time' => '09:00:00', 'end_time' => '17:00:00']
                    );
                }
                $schedules = EmployeeWorkSchedule::where('employee_id', $employee->id)->get();
                foreach ($schedules as $sch) {
                    $day = strtolower($sch->day_of_week);
                    $scheduleMap[$day][] = [
                        'start' => $sch->start_time,
                        'end'   => $sch->end_time,
                    ];
                }
            }

            if (empty($scheduleMap)) {
                $this->command->warn("Employee #{$employee->id} has no work schedule — skipped.");
                continue;
            }

            // Walk every day in August 2026 — ONLY create records on scheduled working days
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dayName = strtolower($date->format('l')); // monday, tuesday, ...

                if (!isset($scheduleMap[$dayName]) || empty($scheduleMap[$dayName])) {
                    // Not a working day according to schedule → skip (no attendance row)
                    continue;
                }

                $shifts = $scheduleMap[$dayName];
                // Use earliest start and latest end for the day
                $dayStart = collect($shifts)->min('start');
                $dayEnd   = collect($shifts)->max('end');

                // ~85% present, ~15% absent (stable across re-runs via hash)
                $seed = crc32((string) $employee->id . $date->toDateString());
                $isPresent = ($seed % 100) < 85;

                $payload = [
                    'registered_by' => $registrar->id,
                    'updated_at'    => now(),
                ];

                if ($isPresent) {
                    $checkIn  = Carbon::parse($date->format('Y-m-d') . ' ' . $dayStart)
                        ->addMinutes(($seed % 31) - 10);
                    $checkOut = Carbon::parse($date->format('Y-m-d') . ' ' . $dayEnd)
                        ->addMinutes(($seed % 31) - 15);

                    if ($checkOut->lte($checkIn)) {
                        $checkOut = $checkIn->copy()->addHours(4);
                    }

                    $payload['check_in']  = $checkIn->format('H:i:s');
                    $payload['check_out'] = $checkOut->format('H:i:s');
                    $payload['status']    = 'present';
                    $payload['notes']     = null;
                } else {
                    $notesOptions = ['إجازة مرضية', 'ظرف طارئ', 'غياب بدون عذر', 'إجازة سنوية'];
                    $payload['check_in']  = null;
                    $payload['check_out'] = null;
                    $payload['status']    = 'absent';
                    $payload['notes']     = $notesOptions[$seed % count($notesOptions)];
                }

                // Use DB because the model table name may resolve to "attendances"
                // while the migration creates the table "attendance"
                $exists = DB::table('attendance')
                    ->where('employee_id', $employee->id)
                    ->where('date', $date->toDateString())
                    ->exists();

                if ($exists) {
                    DB::table('attendance')
                        ->where('employee_id', $employee->id)
                        ->where('date', $date->toDateString())
                        ->update($payload);
                } else {
                    $payload['employee_id'] = $employee->id;
                    $payload['date']        = $date->toDateString();
                    $payload['created_at']  = now();
                    DB::table('attendance')->insert($payload);
                }

                $created++;
            }
        }

    }
}
