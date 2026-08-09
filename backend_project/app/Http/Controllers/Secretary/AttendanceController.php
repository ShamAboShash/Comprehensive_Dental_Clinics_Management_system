<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\CheckInRequest;
use App\Http\Requests\Secretary\CheckOutRequest;
use App\Http\Requests\Secretary\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-secretary');
    }

    /**
     * 1. جدول حضور الموظفين اليومي
     * GET /secretary/dailyAttendance?date=2026-08-09
     */
    public function daily(Request $request): JsonResponse
    {
        $date = $request->query('date', now()->toDateString());

        // All active staff employees
        $employees = Employee::with(['user'])
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get();

        $attendances = Attendance::with(['registeredBy'])
            ->whereDate('date', $date)
            ->get()
            ->keyBy('employee_id');

        $data = $employees->map(function (Employee $employee) use ($attendances, $date) {
            $att = $attendances->get($employee->id);

            return [
                'employee_id'   => $employee->id,
                'employee_name' => $employee->user->name ?? null,
                'role'          => $employee->user->role?->value,
                'role_label'    => $employee->user->role?->label(),
                'date'          => $date,
                'attendance'    => $att ? [
                    'id'            => $att->id,
                    'check_in'      => $att->check_in,
                    'check_out'     => $att->check_out,
                    'status'        => $att->status,
                    'notes'         => $att->notes,
                    'registered_by' => $att->registeredBy?->name,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'date'    => $date,
            'count'   => $data->count(),
            'data'    => $data,
        ]);
    }

    /**
     * 2. جدول حضور الموظفين الشهري
     * GET /secretary/monthlyAttendance?year=2026&month=8
     */
    public function monthly(Request $request): JsonResponse
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $attendances = Attendance::with(['employee.user', 'registeredBy'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->orderBy('employee_id')
            ->get();

        // Group by employee
        $grouped = $attendances->groupBy('employee_id')->map(function ($rows) {
            $first = $rows->first();

            return [
                'employee_id'   => $first->employee_id,
                'employee_name' => $first->employee?->user?->name,
                'role'          => $first->employee?->user?->role?->value,
                'days'          => $rows->map(fn ($a) => [
                    'id'        => $a->id,
                    'date'      => $a->date?->toDateString(),
                    'check_in'  => $a->check_in,
                    'check_out' => $a->check_out,
                    'status'    => $a->status,
                    'notes'     => $a->notes,
                ])->values(),
                'summary' => [
                    'present' => $rows->where('status', 'present')->count(),
                    'absent'  => $rows->where('status', 'absent')->count(),
                    'total'   => $rows->count(),
                ],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'year'    => $year,
            'month'   => $month,
            'count'   => $grouped->count(),
            'data'    => $grouped,
        ]);
    }

    /**
     * تسجيل حضور (Check-in)
     * POST /secretary/checkIn
     */
    public function checkIn(CheckInRequest $request): JsonResponse
    {
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

    /**
     * 3. تغيير حالة الحضور / تعديل سجل
     * POST /secretary/updateAttendance/{id}
     */
    public function update(UpdateAttendanceRequest $request, int $id): JsonResponse
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'سجل الحضور غير موجود.',
            ], 404);
        }

        $attendance->update([
            'status'        => $request->status ?? $attendance->status,
            'check_in'      => $request->has('check_in') ? $request->check_in : $attendance->check_in,
            'check_out'     => $request->has('check_out') ? $request->check_out : $attendance->check_out,
            'notes'         => $request->has('notes') ? $request->notes : $attendance->notes,
            'registered_by' => $request->user()->id,
        ]);

        $attendance->load(['employee.user', 'registeredBy']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث سجل الحضور بنجاح.',
            'data'    => $this->formatAttendance($attendance),
        ]);
    }

    /**
     * تسجيل غياب
     * POST /secretary/markAbsent
     */
    public function markAbsent(CheckInRequest $request): JsonResponse
    {
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
