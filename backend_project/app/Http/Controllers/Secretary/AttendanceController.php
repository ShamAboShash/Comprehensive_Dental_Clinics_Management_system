<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\CheckInRequest;
use App\Http\Requests\Secretary\CheckOutRequest;
use App\Http\Requests\Secretary\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\DentistWorkSchedule;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    // public function __construct()
    // {
    //     Gate::authorize('is-secretary');
    // }

    /**
 * 1. جدول حضور يومي
 * GET /secretary/dailyAttendance?date=2026-08-14
 */
public function daily(Request $request): JsonResponse
{
        Gate::authorize('is-admin-secretary');


    $date = $request->query('date', now()->toDateString());
    $dayOfWeek = strtolower(Carbon::parse($date)->format('l')); // monday, tuesday, ...

    $employees = Employee::with(['user', 'dentist', 'workSchedules'])
        ->whereHas('user', fn ($q) => $q->where('is_active', true))
        ->get();

    $attendances = Attendance::with('registeredBy')
        ->whereDate('date', $date)
        ->get()
        ->keyBy('employee_id');

    // Preload dentist schedules for this weekday
    $dentistIds = $employees->pluck('dentist.id')->filter()->values();
    $dentistSchedules = DentistWorkSchedule::whereIn('dentist_id', $dentistIds)
        ->where('day_of_week', $dayOfWeek)
        ->get()
        ->groupBy('dentist_id');

    $data = $employees->map(function (Employee $employee) use (
        $attendances, $date, $dayOfWeek, $dentistSchedules
    ) {
        $worksToday = $this->employeeWorksOnDay($employee, $dayOfWeek, $dentistSchedules);
        $att = $attendances->get($employee->id);

        // No schedule for this day → holiday
        if (!$worksToday) {
            return [
                'employee_id'   => $employee->id,
                'employee_name' => $employee->user->name ?? null,
                'role'          => $employee->user->role?->value,
                'role_label'    => $employee->user->role?->label(),
                'date'          => $date,
                'attendance'    => [
                    'id'        => $att?->id,
                    'check_in'  => null,
                    'check_out' => null,
                    'status'    => 'holiday',
                    'notes'     => $att?->notes,
                ],
            ];
        }

        // Work day: real record or not_registered_yet
        return [
            'employee_id'   => $employee->id,
            'employee_name' => $employee->user->name ?? null,
            'role'          => $employee->user->role?->value,
            'role_label'    => $employee->user->role?->label(),
            'date'          => $date,
            'is_work_day'   => true,
            'attendance'    => $att ? [
                'id'            => $att->id,
                'check_in'      => $att->check_in,
                'check_out'     => $att->check_out,
                'status'        => $att->status,
                'notes'         => $att->notes,
                'registered_by' => $att->registeredBy?->name,
            ] : [
                'id'        => null,
                'check_in'  => null,
                'check_out' => null,
                'status'    => 'not_registered_yet',
                'notes'     => null,
            ],
        ];
    });

    return response()->json([
        'success' => true,
        'date'    => $date,
        'day'     => $dayOfWeek,
        'count'   => $data->count(),
        'data'    => $data,
    ]);
}

/**
 * 2. جدول حضور شهري
 * GET /secretary/monthlyAttendance?year=2026&month=8
 */
public function monthly(Request $request): JsonResponse
{
            Gate::authorize('is-secretary');

    $year  = (int) $request->query('year', now()->year);
    $month = (int) $request->query('month', now()->month);

    $start = Carbon::create($year, $month, 1)->startOfMonth();
    $end   = $start->copy()->endOfMonth();

    $employees = Employee::with(['user', 'dentist', 'workSchedules'])
        ->whereHas('user', fn ($q) => $q->where('is_active', true))
        ->get();

    $attendances = Attendance::whereBetween('date', [$start->toDateString(), $end->toDateString()])
        ->get()
        ->groupBy(fn ($a) => $a->employee_id . '_' . $a->date->toDateString());

    // All dentist schedules (all weekdays)
    $dentistIds = $employees->pluck('dentist.id')->filter()->values();
    $allDentistSchedules = DentistWorkSchedule::whereIn('dentist_id', $dentistIds)
        ->get()
        ->groupBy('dentist_id');

    $data = $employees->map(function (Employee $employee) use (
        $start, $end, $attendances, $allDentistSchedules
    ) {
        $days = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dateStr   = $cursor->toDateString();
            $dayOfWeek = strtolower($cursor->format('l'));
            $key       = $employee->id . '_' . $dateStr;

            $dentistSchedulesForEmp = $employee->dentist
                ? ($allDentistSchedules->get($employee->dentist->id) ?? collect())
                : collect();

            $works = $this->employeeWorksOnDay(
                $employee,
                $dayOfWeek,
                $employee->dentist
                    ? collect([$employee->dentist->id => $dentistSchedulesForEmp])
                    : collect()
            );

            $att = $attendances->get($key)?->first();

            if (!$works) {
                $days[] = [
                    'date'      => $dateStr,
                    'day'       => $dayOfWeek,
                    'is_work_day' => false,
                    'status'    => 'holiday',
                    'check_in'  => null,
                    'check_out' => null,
                    'notes'     => $att?->notes,
                    'id'        => $att?->id,
                ];
            } else {
                $days[] = [
                    'date'        => $dateStr,
                    'day'         => $dayOfWeek,
                    'is_work_day' => true,
                    'status'      => $att?->status ?? 'not_registered_yet',
                    'check_in'    => $att?->check_in,
                    'check_out'   => $att?->check_out,
                    'notes'       => $att?->notes,
                    'id'          => $att?->id,
                ];
            }

            $cursor->addDay();
        }

        $statuses = collect($days)->pluck('status');

        return [
            'employee_id'   => $employee->id,
            'employee_name' => $employee->user->name ?? null,
            'role'          => $employee->user->role?->value,
            'days'          => $days,
            'summary'       => [
                'present'             => $statuses->filter(fn ($s) => $s === 'present')->count(),
                'absent'              => $statuses->filter(fn ($s) => $s === 'absent')->count(),
                'late'                => $statuses->filter(fn ($s) => $s === 'late')->count(),
                'on_leave'            => $statuses->filter(fn ($s) => $s === 'on_leave')->count(),
                'holiday'             => $statuses->filter(fn ($s) => $s === 'holiday')->count(),
                'not_registered_yet'  => $statuses->filter(fn ($s) => $s === 'not_registered_yet')->count(),
                'work_days'           => collect($days)->where('is_work_day', true)->count(),
            ],
        ];
    });

    return response()->json([
        'success' => true,
        'year'    => $year,
        'month'   => $month,
        'count'   => $data->count(),
        'data'    => $data,
    ]);
}



private function employeeWorksOnDay(
    Employee $employee,
    string $dayOfWeek,
    $dentistSchedulesGrouped
): bool {
    // Dentist: use dentist schedule
    if ($employee->dentist) {
        $schedules = $dentistSchedulesGrouped->get($employee->dentist->id) ?? collect();

        // groupBy may return a collection of schedules; filter by day if needed
        if ($schedules->isEmpty()) {
            return false;
        }

        // If already filtered by day in daily(), any item means works
        // If full list (monthly), filter by day
        return $schedules->contains(function ($s) use ($dayOfWeek) {
            return strtolower($s->day_of_week) === $dayOfWeek;
        });
    }

    // Non-dentist employee: employee_work_schedules
    return $employee->workSchedules
        ->contains(fn ($s) => strtolower($s->day_of_week) === $dayOfWeek);
}

    /**
     * تسجيل حضور (Check-in)
     * POST /secretary/checkIn
     */
    public function checkIn(CheckInRequest $request): JsonResponse
    {
                Gate::authorize('is-secretary');

        $date    = $request->date ?? now()->toDateString();
        $checkIn = $request->check_in ?? now()->format('H:i:s');

        $existing = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('date', $date)
            ->first();

        if ($existing && $existing->check_in) {
            return response()->json([
                'success' => false,
                'message' => 'تم تسجيل حضور هذا الموظف مسبقاً لهذا اليوم.',
            ], 422);
        }

        if ($existing) {
            $existing->update([
                'check_in'      => $checkIn,
                'status'        => 'present',
                'registered_by' => $request->user()->id,
                'notes'         => $request->notes ?? $existing->notes,
            ]);
            $attendance = $existing->fresh(['employee.user', 'registeredBy']);
        } else {
            $attendance = Attendance::create([
                'employee_id'   => $request->employee_id,
                'registered_by' => $request->user()->id,
                'date'          => $date,
                'check_in'      => $checkIn,
                'status'        => 'present',
                'notes'         => $request->notes,
            ]);
            $attendance->load(['employee.user', 'registeredBy']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الحضور بنجاح.',
            'data'    => $this->formatAttendance($attendance),
        ], 201);
    }

    /**
     * تسجيل انصراف (Check-out)
     * POST /secretary/checkOut
     */
    public function checkOut(CheckOutRequest $request): JsonResponse
    {
                Gate::authorize('is-secretary');

        $date     = $request->date ?? now()->toDateString();
        $checkOut = $request->check_out ?? now()->format('H:i:s');

        $attendance = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('date', $date)
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد سجل حضور لهذا الموظف في هذا اليوم. سجّل الحضور أولاً.',
            ], 422);
        }

        if (!$attendance->check_in) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تسجيل الانصراف قبل تسجيل الحضور.',
            ], 422);
        }

        if ($attendance->check_out) {
            return response()->json([
                'success' => false,
                'message' => 'تم تسجيل انصراف هذا الموظف مسبقاً لهذا اليوم.',
            ], 422);
        }

        $attendance->update([
            'check_out' => $checkOut,
            'notes'     => $request->notes ?? $attendance->notes,
        ]);

        $attendance->load(['employee.user', 'registeredBy']);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الانصراف بنجاح.',
            'data'    => $this->formatAttendance($attendance),
        ]);
    }


    public function markOnLeave(CheckInRequest $request): JsonResponse
    {
                Gate::authorize('is-secretary');

        $date = $request->date ?? now()->toDateString();

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $request->employee_id,
                'date'        => $date,
            ],
            [
                'status'        => 'on_leave',
                'check_in'      => null,
                'check_out'     => null,
                'registered_by' => $request->user()->id,
                'notes'         => $request->notes,
            ]
        );

        $attendance->load(['employee.user', 'registeredBy']);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الإجازة بنجاح.',
            'data'    => $this->formatAttendance($attendance),
        ]);
    }

    /**
     * POST /secretary/markLate
     * Body: employee_id, date?, check_in?, notes?
     */
    public function markLate(CheckInRequest $request): JsonResponse
    {
                Gate::authorize('is-secretary');

        $date    = $request->date ?? now()->toDateString();
        $checkIn = $request->check_in ?? now()->format('H:i:s');

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $request->employee_id,
                'date'        => $date,
            ],
            [
                'status'        => 'late',
                'check_in'      => $checkIn,
                'registered_by' => $request->user()->id,
                'notes'         => $request->notes,
            ]
        );

        $attendance->load(['employee.user', 'registeredBy']);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل التأخير بنجاح.',
            'data'    => $this->formatAttendance($attendance),
        ]);
    }

    /**
     * تسجيل غياب
     * POST /secretary/markAbsent
     */
    public function markAbsent(CheckInRequest $request): JsonResponse
    {
                Gate::authorize('is-secretary');

        $date = $request->date ?? now()->toDateString();

        $existing = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('date', $date)
            ->first();

        if ($existing) {
            $existing->update([
                'status'        => 'absent',
                'check_in'      => null,
                'check_out'     => null,
                'registered_by' => $request->user()->id,
                'notes'         => $request->notes ?? $existing->notes,
            ]);
            $attendance = $existing->fresh(['employee.user', 'registeredBy']);
        } else {
            $attendance = Attendance::create([
                'employee_id'   => $request->employee_id,
                'registered_by' => $request->user()->id,
                'date'          => $date,
                'status'        => 'absent',
                'notes'         => $request->notes,
            ]);
            $attendance->load(['employee.user', 'registeredBy']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الغياب بنجاح.',
            'data'    => $this->formatAttendance($attendance),
        ]);
    }

    private function formatAttendance(Attendance $a): array
    {
        return [
            'id'            => $a->id,
            'employee_id'   => $a->employee_id,
            'employee_name' => $a->employee?->user?->name,
            'date'          => $a->date?->toDateString(),
            'check_in'      => $a->check_in,
            'check_out'     => $a->check_out,
            'status'        => $a->status,
            'notes'         => $a->notes,
            'registered_by' => $a->registeredBy?->name,
        ];
    }
}
