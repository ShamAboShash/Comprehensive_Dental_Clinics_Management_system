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
use App\Models\EmployeeQrCode;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

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


    public function scan(Request $request): JsonResponse
{
    $request->validate([
        'qr_content' => 'required|string|max:30',
    ], [
        'qr_content.required' => 'يجب إرسال رمز QR',
    ]);

    $qrContent = trim($request->qr_content);

    // Find the QR record
    $qr = EmployeeQrCode::where('qr_content', $qrContent)
        ->where('is_active', true)
        ->first();

    if (!$qr) {
        return response()->json([
            'success' => false,
            'message' => 'رمز QR غير صالح أو غير مفعل',
        ], 404);
    }

    $employeeId = $qr->employee_id;
    $today = now()->toDateString();

    // Check if attendance already exists today
    $attendance = Attendance::where('employee_id', $employeeId)
        ->whereDate('date', $today)
        ->first();

    // First scan → Check-in
    if (!$attendance) {
        $attendance = Attendance::create([
            'employee_id'   => $employeeId,
            'registered_by' => auth()->id(),
            'date'          => $today,
            'status'        => 'present',
            'check_in'      => now()->format('H:i'),
            'check_out'     => null,
            'notes'         => 'تم التسجيل عبر QR',
        ]);

        $attendance->load('employee.user');

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الحضور بنجاح',
            'action'  => 'check_in',
            'data'    => [
                'id'            => $attendance->id,
                'employee_id'   => $attendance->employee_id,
                'employee_name' => $attendance->employee?->user?->name,
                'date'          => $attendance->date?->toDateString(),
                'status'        => $attendance->status,
                'check_in'      => $attendance->check_in,
                'check_out'     => $attendance->check_out,
            ],
        ], 201);
    }

    // Second scan → Check-out
    if ($attendance->status === 'present' && empty($attendance->check_out)) {
        $attendance->update([
            'check_out'     => now()->format('H:i'),
            'registered_by' => auth()->id(),
            'notes'         => ($attendance->notes ? $attendance->notes . ' | ' : '') . 'تم تسجيل الانصراف عبر QR',
        ]);

        $attendance->load('employee.user');

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الانصراف بنجاح',
            'action'  => 'check_out',
            'data'    => [
                'id'            => $attendance->id,
                'employee_id'   => $attendance->employee_id,
                'employee_name' => $attendance->employee?->user?->name,
                'date'          => $attendance->date?->toDateString(),
                'status'        => $attendance->status,
                'check_in'      => $attendance->check_in,
                'check_out'     => $attendance->check_out,
            ],
        ]);
    }

    // Already checked in and out
    return response()->json([
        'success' => false,
        'message' => 'تم تسجيل الحضور والانصراف مسبقاً لهذا اليوم',
        'data'    => [
            'employee_name' => $attendance->employee?->user?->name,
            'check_in'      => $attendance->check_in,
            'check_out'     => $attendance->check_out,
        ],
    ], 422);
}

// public function getQrCode(int $employeeId): Response|JsonResponse
// {
//     $qr = EmployeeQrCode::where('employee_id', $employeeId)
//         ->where('is_active', true)
//         ->first();

//     if (!$qr) {
//         return response()->json([
//             'success' => false,
//             'message' => 'لا يوجد رمز QR لهذا الموظف',
//         ], 404);
//     }

//     // Generate as SVG first (very reliable)
//     $svg = QrCode::format('svg')
//         ->size(300)
//         ->margin(2)
//         ->errorCorrection('H')
//         ->generate($qr->qr_content);

//     // Convert SVG to PNG using pure PHP (no Imagick needed)
//     // We will return base64 PNG so frontend can easily use it

//     return response()->json([
//         'success'     => true,
//         'qr_content'  => $qr->qr_content,
//         'qr_image'    => 'data:image/svg+xml;base64,' . base64_encode($svg),
//     ]);
// }




public function getQrCode(int $employeeId): JsonResponse
{
    $qr = EmployeeQrCode::where('employee_id', $employeeId)
        ->where('is_active', true)
        ->first();

    if (!$qr) {
        return response()->json([
            'success' => false,
            'message' => 'لا يوجد رمز QR لهذا الموظف',
        ], 404);
    }

    // Create folder if it doesn't exist
    $folder = 'qrcodes';
    if (!Storage::disk('public')->exists($folder)) {
        Storage::disk('public')->makeDirectory($folder);
    }

    // File name example: qr-emp-5.svg
    $fileName = 'qr-emp-' . $employeeId . '.svg';
    $filePath = $folder . '/' . $fileName;

    // Generate and save the QR as SVG
    $svg = QrCode::format('svg')
        ->size(300)
        ->margin(2)
        ->errorCorrection('H')
        ->generate($qr->qr_content);

    Storage::disk('public')->put($filePath, $svg);

    // Public URL
    $url = asset('storage/' . $filePath);

    return response()->json([
        'success'     => true,
        'qr_content'  => $qr->qr_content,
        'qr_url'      => $url,
        'file_name'   => $fileName,
    ]);
}

}
