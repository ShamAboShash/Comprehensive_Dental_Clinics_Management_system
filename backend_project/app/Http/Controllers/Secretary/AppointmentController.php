<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\StoreAppointmentRequest;
use App\Http\Requests\Secretary\UpdateAppointmentRequest;
use App\Http\Requests\Secretary\UpdateAppointmentStatusRequest;
use App\Http\Resources\patient\DentistResource;
use App\Models\AppointmentType;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\DentistWorkSchedule;
use App\Models\Treatment;
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


     private const SLOT_MINUTES = 30;
     private const DAYS_AHEAD   = 14;
     private const CARBON_DOW_TO_ENUM = [
        0 => 'sunday',
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
     ];
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
 * مواعيد كل الأطباء لهذا الأسبوع
 * GET /secretary/weeklyAppointments
 * GET /secretary/weeklyAppointments?date=2026-08-15
 */
    public function weekly(Request $request): JsonResponse
    {
        $base = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::now();

        // Saturday → Friday
        $start = $base->copy()->startOfWeek(Carbon::SATURDAY)->startOfDay();
        $end   = $base->copy()->endOfWeek(Carbon::FRIDAY)->endOfDay();

        $appointments = Appointment::with([
            'patient.user',
            'dentist.employee.user',
            'appointmentType',
            'treatment.treatmentType',
        ])
            ->whereBetween('appointment_time', [$start, $end])
            ->orderBy('appointment_time')
            ->get();

        // Group by dentist
        // $byDentist = $appointments
        //     ->groupBy('dentist_id')
        //     ->map(function ($rows) {
        //         $first = $rows->first();

        //         return [
        //             'dentist_id'   => $first->dentist_id,
        //             'dentist_name' => $first->dentist?->employee?->user?->name,
        //             'count'        => $rows->count(),
        //             'appointments' => $rows->map(fn ($a) => $this->format($a))->values(),
        //         ];
        //     })
        //     ->values();

        // Also group by day (useful for calendar UI)
        $byDay = $appointments
            ->groupBy(fn ($a) => $a->appointment_time?->toDateString())
            ->map(fn ($rows, $date) => [
                'date'  => $date,
                'day'   => strtolower(Carbon::parse($date)->format('l')),
                'count' => $rows->count(),
                'appointments' => $rows->map(fn ($a) => $this->format($a))->values(),
            ])
            ->values();

        return response()->json([
            'success'    => true,
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
            'count'      => $appointments->count(),
            // 'by_dentist' => $byDentist,
            'by_day'     => $byDay,
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
 * حجز موعد (مريض مسجّل أو walk-in)
 * نفس فحوصات المريض: جدول الدوام + اختصاص الطبيب + تعارض الشريحة
 * POST /secretary/addAppointment
 */
public function store(StoreAppointmentRequest $request): JsonResponse
{
    $patientId = $request->patient_id;

    // 1) اختصاص الطبيب ↔ نوع الموعد
    $isQualified = DB::table('appointment_type_specializations')
        ->join('dentists', 'dentists.specialization_id', '=', 'appointment_type_specializations.specialization_id')
        ->where('appointment_type_specializations.appointment_type_id', $request->appointment_type_id)
        ->where('dentists.id', $request->dentist_id)
        ->exists();

    if (!$isQualified) {
        return response()->json([
            'success' => false,
            'message' => 'هذا الطبيب لا يعالج هذا النوع من المواعيد.',
        ], 422);
    }

    // 2) ضمن دوام الطبيب + شريحة 30 دقيقة
    if (!$this->isWithinDentistSchedule($request->dentist_id, $request->appointment_time)) {
        return response()->json([
            'success' => false,
            'message' => 'الوقت المحدد لا يقع ضمن ساعات دوام الطبيب.',
        ], 422);
    }

    // 3) متابعة علاج: العلاج يخص نفس المريض ونفس الطبيب
    if ($request->type === 'continue_treatment') {
        $ok = Treatment::where('id', $request->treatment_id)
            ->where('patient_id', $patientId)
            ->where('dentist_id', $request->dentist_id)
            ->exists();

        if (!$ok) {
            return response()->json([
                'success' => false,
                'message' => 'العلاج المحدد لا يخص هذا المريض أو هذا الطبيب.',
            ], 422);
        }
    }

    // 4) حجز الشريحة (slot_lock)
    try {
        $appointment = DB::transaction(function () use ($request, $patientId) {
            $slotLock = $request->dentist_id . '_' . $request->appointment_time;

            $conflict = Appointment::where('slot_lock', $slotLock)
                ->whereNotIn('status', ['cancelled', 'rejected', 'patient_no_show'])
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new \RuntimeException('SLOT_TAKEN');
            }

            return Appointment::create([
                'patient_id'          => $patientId,
                'dentist_id'          => $request->dentist_id,
                'appointment_type_id' => $request->appointment_type_id,
                'treatment_id'        => $request->treatment_id,
                'type'                => $request->type,
                'status'              => 'pending_secretary',
                'appointment_time'    => $request->appointment_time,
                'slot_lock'           => $slotLock,
                'notes'               => $request->notes,
            ]);
        });
    } catch (\RuntimeException $e) {
        return response()->json([
            'success' => false,
            'message' => 'تم حجز هذا الموعد للتو، يرجى اختيار وقت آخر.',
        ], 409);
    } catch (\Illuminate\Database\QueryException $e) {
        if ($e->getCode() === '23000') {
            return response()->json([
                'success' => false,
                'message' => 'تم حجز هذا الموعد للتو، يرجى اختيار وقت آخر.',
            ], 409);
        }
        throw $e;
    }

    $appointment->load([
        'patient.user',
        'dentist.employee.user',
        'appointmentType',
        'treatment.treatmentType',
    ]);

    return response()->json([
        'success' => true,
        'message' => 'تم حجز الموعد بنجاح.',
        'data'    => $this->format($appointment),
    ], 201);
}

private function isWithinDentistSchedule(int $dentistId, string $appointmentTime): bool
{
    $dt = Carbon::parse($appointmentTime);

    if ($dt->minute % 30 !== 0 || $dt->second !== 0) {
        return false;
    }

    $dayEnum = strtolower($dt->format('l'));

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


      
    public function availableSlots(Dentist $dentist): JsonResponse
    {
        $today = Carbon::today();
        $rangeEnd = $today->copy()->addDays(self::DAYS_AHEAD - 1);

        // كل جداول عمل الطبيب مجمعة حسب اليوم
        $schedulesByDay = DentistWorkSchedule::where('dentist_id', $dentist->id)
            ->get()
            ->groupBy('day_of_week');

        if ($schedulesByDay->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'لا يوجد دوام مسجل لهذا الطبيب.',
                'data'    => [],
            ]);
        }

        // كل المواعيد المحجوزة (غير الملغاة/المرفوضة) خلال الفترة كاملة، بجلب واحد فقط
        $bookedAppointments = Appointment::where('dentist_id', $dentist->id)
            ->whereBetween('appointment_time', [$today->copy()->startOfDay(), $rangeEnd->copy()->endOfDay()])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->pluck('appointment_time')
            ->map(fn ($time) => Carbon::parse($time)->format('Y-m-d H:i'))
            ->toArray();

        $now = Carbon::now();
        $result = [];

        for ($i = 0; $i < self::DAYS_AHEAD; $i++) {
            $date = $today->copy()->addDays($i);
            $dayEnum = self::CARBON_DOW_TO_ENUM[$date->dayOfWeek];

            $daySchedules = $schedulesByDay->get($dayEnum);
            if (!$daySchedules) {
                continue; // الطبيب لا يعمل في هذا اليوم من الأسبوع
            }

            $daySlots = [];

            foreach ($daySchedules as $schedule) {
                $slotStart = Carbon::parse($date->toDateString() . ' ' . $schedule->start_time);
                $rangeEndTime = Carbon::parse($date->toDateString() . ' ' . $schedule->end_time);

                while ($slotStart->copy()->addMinutes(self::SLOT_MINUTES)->lte($rangeEndTime)) {
                    $slotKey = $slotStart->format('Y-m-d H:i');

                    $isPast   = $slotStart->lte($now);
                    $isBooked = in_array($slotKey, $bookedAppointments, true);

                    if (!$isPast && !$isBooked) {
                        $daySlots[] = [
                            'time'     => $slotStart->format('H:i'),
                            'datetime' => $slotStart->toDateTimeString(),
                        ];
                    }

                    $slotStart->addMinutes(self::SLOT_MINUTES);
                }
            }

            if (!empty($daySlots)) {
                $result[] = [
                    'date'  => $date->toDateString(),
                    'day'   => $dayEnum,
                    'slots' => $daySlots,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'dentist_id' => $dentist->id,
            'data'       => $result,
        ]);
    }


    public function byAppointmentType(int $appointmentTypeId)
{
    // Check appointment type exists
    $appointmentType =AppointmentType::with('specializations')->find($appointmentTypeId);

    if (!$appointmentType) {
        return response()->json([
            'success' => false,
            'message' => 'نوع الموعد غير موجود',
        ], 404);
    }

    $specializationIds = $appointmentType->specializations->pluck('id');

    if ($specializationIds->isEmpty()) {
        return response()->json([
            'success' => true,
            'data'    => [],
            'message' => 'لا يوجد أطباء مرتبطين بهذا النوع من المواعيد',
        ]);
    }

    $dentists = Dentist::with(['employee.user', 'specialization', 'clinics'])
        ->whereIn('specialization_id', $specializationIds)
        ->whereHas('employee.user', function ($q) {
            $q->where('is_active', true);
        })
        ->get();

    return response()->json([
        'success' => true,
        'data'    => DentistResource::collection($dentists),
    ]);
}

    // ───────────────────────── Helpers ─────────────────────────

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
