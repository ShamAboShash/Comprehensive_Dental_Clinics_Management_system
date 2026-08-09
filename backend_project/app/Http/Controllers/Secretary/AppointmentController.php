<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\StoreAppointmentRequest;
use App\Http\Requests\Secretary\UpdateAppointmentRequest;
use App\Http\Requests\Secretary\UpdateAppointmentStatusRequest;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\DentistWorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AppointmentController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-secretary');
    }

    /**
     * عرض كل المواعيد (مع فلاتر اختيارية)
     * GET /secretary/showAllAppointments?status=&dentist_id=&patient_id=&from=&to=
     */
    public function index(Request $request): JsonResponse
    {
        $query = Appointment::with([
            'patient.user',
            'dentist.employee.user',
            'appointmentType',
            'treatment.treatmentType',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('dentist_id')) {
            $query->where('dentist_id', $request->dentist_id);
        }
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('appointment_time', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('appointment_time', '<=', $request->to);
        }

        $appointments = $query->orderBy('appointment_time', 'desc')->get();

        return response()->json([
            'success' => true,
            'count'   => $appointments->count(),
            'data'    => $appointments->map(fn ($a) => $this->format($a)),
        ]);
    }

    /**
     * 4. عرض المواعيد اليومية
     * GET /secretary/dailyAppointments?date=2026-08-09
     */
    public function daily(Request $request): JsonResponse
    {
        $date = $request->query('date', Carbon::today()->toDateString());

        $appointments = Appointment::with([
            'patient.user',
            'dentist.employee.user',
            'appointmentType',
            'treatment.treatmentType',
        ])
            ->whereDate('appointment_time', $date)
            ->orderBy('appointment_time')
            ->get();

        return response()->json([
            'success' => true,
            'date'    => $date,
            'count'   => $appointments->count(),
            'data'    => $appointments->map(fn ($a) => $this->format($a)),
        ]);
    }

    /**
     * 5. عرض المواعيد الشهرية
     * GET /secretary/monthlyAppointments?year=2026&month=8
     */
    public function monthly(Request $request): JsonResponse
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $appointments = Appointment::with([
            'patient.user',
            'dentist.employee.user',
            'appointmentType',
            'treatment.treatmentType',
        ])
            ->whereBetween('appointment_time', [$start, $end])
            ->orderBy('appointment_time')
            ->get();

        return response()->json([
            'success' => true,
            'year'    => $year,
            'month'   => $month,
            'count'   => $appointments->count(),
            'data'    => $appointments->map(fn ($a) => $this->format($a)),
        ]);
    }

    /**
     * 8. عرض موعد حسب id
     * GET /secretary/showAppointment/{id}
     */
    public function show(int $id): JsonResponse
    {
        $appointment = Appointment::with([
            'patient.user',
            'dentist.employee.user',
            'appointmentType',
            'treatment.treatmentType',
        ])->find($id);

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'الموعد غير موجود.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->format($appointment),
        ]);
    }

    /**
     * 6. إضافة موعد
     * POST /secretary/addAppointment
     * Secretary books → status defaults to approved
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointmentTime = Carbon::parse($request->appointment_time);

        // Optional: must fall inside dentist work schedule
        if (!$this->isWithinDentistSchedule($request->dentist_id, $request->appointment_time)) {
            return response()->json([
                'success' => false,
                'message' => 'الوقت المحدد لا يقع ضمن ساعات دوام الطبيب.',
            ], 422);
        }

        $slotLock = $request->dentist_id . '_' . $appointmentTime->format('Y-m-d H:i:s');

        try {
            $appointment = DB::transaction(function () use ($request, $appointmentTime, $slotLock) {
                $conflict = Appointment::where('slot_lock', $slotLock)
                    ->whereNotIn('status', ['cancelled', 'rejected', 'patient_no_show'])
                    ->lockForUpdate()
                    ->exists();

                if ($conflict) {
                    return null;
                }

                return Appointment::create([
                    'patient_id'          => $request->patient_id,
                    'dentist_id'          => $request->dentist_id,
                    'appointment_type_id' => $request->appointment_type_id,
                    'treatment_id'        => $request->treatment_id,
                    'type'                => $request->type,
                    'status'              => $request->status ?? 'approved',
                    'appointment_time'    => $appointmentTime,
                    'slot_lock'           => $slotLock,
                    'notes'               => $request->notes,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الموعد.',
            ], 500);
        }

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الوقت محجوز مسبقاً عند نفس الطبيب. اختر وقتاً آخر.',
            ], 409);
        }

        $appointment->load(['patient.user', 'dentist.employee.user', 'appointmentType', 'treatment.treatmentType']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الموعد بنجاح.',
            'data'    => $this->format($appointment),
        ], 201);
    }

    /**
     * 7. تعديل موعد
     * POST /secretary/updateAppointment/{id}
     */
    public function update(UpdateAppointmentRequest $request, int $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'الموعد غير موجود.',
            ], 404);
        }

        // Secretary can edit only non-final appointments
        if (in_array($appointment->status, ['completed', 'cancelled', 'rejected'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل موعد مكتمل أو ملغى أو مرفوض.',
            ], 422);
        }

        $dentistId = $request->dentist_id ?? $appointment->dentist_id;
        $newTime   = $request->appointment_time
            ? Carbon::parse($request->appointment_time)
            : $appointment->appointment_time;

        if ($request->filled('appointment_time') || $request->filled('dentist_id')) {
            if (!$this->isWithinDentistSchedule($dentistId, $newTime->toDateTimeString())) {
                return response()->json([
                    'success' => false,
                    'message' => 'الوقت المحدد لا يقع ضمن ساعات دوام الطبيب.',
                ], 422);
            }
        }

        $slotLock = $dentistId . '_' . $newTime->format('Y-m-d H:i:s');

        try {
            $ok = DB::transaction(function () use ($appointment, $request, $dentistId, $newTime, $slotLock) {
                if ($slotLock !== $appointment->slot_lock) {
                    $conflict = Appointment::where('slot_lock', $slotLock)
                        ->where('id', '!=', $appointment->id)
                        ->whereNotIn('status', ['cancelled', 'rejected', 'patient_no_show'])
                        ->lockForUpdate()
                        ->exists();

                    if ($conflict) {
                        return false;
                    }
                }

                $appointment->update([
                    'patient_id'          => $request->patient_id ?? $appointment->patient_id,
                    'dentist_id'          => $dentistId,
                    'appointment_type_id' => $request->has('appointment_type_id')
                        ? $request->appointment_type_id
                        : $appointment->appointment_type_id,
                    'treatment_id'        => $request->has('treatment_id')
                        ? $request->treatment_id
                        : $appointment->treatment_id,
                    'type'                => $request->type ?? $appointment->type,
                    'appointment_time'    => $newTime,
                    'slot_lock'           => $slotLock,
                    'notes'               => $request->has('notes') ? $request->notes : $appointment->notes,
                    'reschedule_count'    => $appointment->reschedule_count + (
                        $request->filled('appointment_time') ? 1 : 0
                    ),
                ]);

                return true;
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تعديل الموعد.',
            ], 500);
        }

        if (!$ok) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الوقت محجوز مسبقاً عند نفس الطبيب.',
            ], 409);
        }

        $appointment->load(['patient.user', 'dentist.employee.user', 'appointmentType', 'treatment.treatmentType']);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل الموعد بنجاح.',
            'data'    => $this->format($appointment),
        ]);
    }

    /**
     * 9. تغيير حالة موعد
     * POST /secretary/updateAppointmentStatus/{id}
     *
     * Secretary can: approved, rejected, cancelled, completed, patient_no_show
     */
    public function updateStatus(UpdateAppointmentStatusRequest $request, int $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'الموعد غير موجود.',
            ], 404);
        }

        $current = $appointment->status;
        $new     = $request->status;

        $allowed = match ($current) {
            'pending', 'pending_secretary' => ['approved', 'rejected', 'cancelled'],
            'approved'                     => ['cancelled', 'completed', 'patient_no_show'],
            default                        => [],
        };

        if (!in_array($new, $allowed, true)) {
            return response()->json([
                'success' => false,
                'message' => "لا يمكن تغيير حالة الموعد من '{$current}' إلى '{$new}'.",
            ], 422);
        }

        if ($new === 'rejected' && empty($request->rejection_reason)) {
            return response()->json([
                'success' => false,
                'message' => 'سبب الرفض مطلوب.',
            ], 422);
        }

        $appointment->update([
            'status'           => $new,
            'rejection_reason' => $request->rejection_reason ?? $appointment->rejection_reason,
            'notes'            => $request->has('notes') ? $request->notes : $appointment->notes,
        ]);

        // Free slot when cancelled / rejected / no-show
        if (in_array($new, ['cancelled', 'rejected', 'patient_no_show'], true)) {
            $appointment->update(['slot_lock' => null]);
        }

        $appointment->load(['patient.user', 'dentist.employee.user', 'appointmentType']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الموعد بنجاح.',
            'data'    => $this->format($appointment),
        ]);
    }

    /**
     * 10. عرض مواعيد طبيب حسب id + جدول دوامه
     * GET /secretary/dentistAppointments/{dentistId}?from=&to=
     */
    public function dentistAppointments(Request $request, int $dentistId): JsonResponse
    {
        $dentist = Dentist::with('employee.user')->find($dentistId);

        if (!$dentist) {
            return response()->json([
                'success' => false,
                'message' => 'الطبيب غير موجود.',
            ], 404);
        }

        $query = Appointment::with(['patient.user', 'appointmentType', 'treatment.treatmentType'])
            ->where('dentist_id', $dentistId);

        if ($request->filled('from')) {
            $query->whereDate('appointment_time', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('appointment_time', '<=', $request->to);
        }

        $appointments = $query->orderBy('appointment_time')->get();

        $workSchedule = DentistWorkSchedule::where('dentist_id', $dentistId)
            ->orderByRaw("FIELD(day_of_week,
                'saturday','sunday','monday','tuesday',
                'wednesday','thursday','friday'
            )")
            ->get()
            ->map(fn ($s) => [
                'id'          => $s->id,
                'day_of_week' => $s->day_of_week,
                'start_time'  => $s->start_time,
                'end_time'    => $s->end_time,
            ]);

        return response()->json([
            'success' => true,
            'dentist' => [
                'id'   => $dentist->id,
                'name' => $dentist->employee?->user?->name,
            ],
            'work_schedule' => $workSchedule,
            'count'         => $appointments->count(),
            'appointments'  => $appointments->map(fn ($a) => $this->format($a)),
        ]);
    }

    /**
     * المرضى المتأخرون عن الموعد (اختياري مفيد للسكرتيرة)
     * GET /secretary/lateAppointments
     */
    public function late(Request $request): JsonResponse
    {
        $appointments = Appointment::with(['patient.user', 'dentist.employee.user', 'appointmentType'])
            ->where('status', 'approved')
            ->where('appointment_time', '<', now())
            ->orderBy('appointment_time')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $appointments->count(),
            'data'    => $appointments->map(fn ($a) => $this->format($a)),
        ]);
    }

    // ───────────────────────── Helpers ─────────────────────────

    private function isWithinDentistSchedule(int $dentistId, string $appointmentTime): bool
    {
        $dt = Carbon::parse($appointmentTime);

        // Align to 30-min slots (same rule as patient module)
        if ($dt->minute % 30 !== 0 || $dt->second !== 0) {
            return false;
        }

        $dayEnum = strtolower($dt->format('l')); // monday, tuesday, ...

        $schedules = DentistWorkSchedule::where('dentist_id', $dentistId)
            ->where('day_of_week', $dayEnum)
            ->get();

        foreach ($schedules as $schedule) {
            $shiftStart = Carbon::parse($dt->toDateString() . ' ' . $schedule->start_time);
            $shiftEnd   = Carbon::parse($dt->toDateString() . ' ' . $schedule->end_time);

            if ($dt->gte($shiftStart) && $dt->copy()->addMinutes(30)->lte($shiftEnd)) {
                return true;
            }
        }

        return false;
    }

    private function format(Appointment $a): array
    {
        return [
            'id'                  => $a->id,
            'patient_id'          => $a->patient_id,
            'patient'             => $a->patient?->user?->name,
            'patient_phone'       => $a->patient?->user?->phone,
            'dentist_id'          => $a->dentist_id,
            'dentist'             => $a->dentist?->employee?->user?->name,
            'appointment_type_id' => $a->appointment_type_id,
            'appointment_type'    => $a->appointmentType?->name ?? $a->appointmentType?->name_en,
            'treatment_id'        => $a->treatment_id,
            'treatment_type'      => $a->treatment?->treatmentType?->name,
            'type'                => $a->type,
            'status'              => $a->status,
            'appointment_time'    => $a->appointment_time?->toDateTimeString(),
            'rejection_reason'    => $a->rejection_reason,
            'notes'               => $a->notes,
            'reschedule_count'    => $a->reschedule_count,
        ];
    }
}
