<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\UpdateAppointmentRequest;
use App\Http\Resources\Patient\AppointmentResource;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\Patient\StoreAppointmentRequest;
use App\Models\Treatment;
use App\Models\DentistWorkSchedule;
use Illuminate\Support\Facades\DB;

class PatientAppointmentController extends Controller
{
    private const ACTIVE_STATUSES  = ['pending', 'pending_secretary', 'approved'];
    private const HISTORY_STATUSES = ['completed', 'cancelled', 'rejected', 'patient_no_show'];

    public function __construct()
    {
        Gate::authorize('is-patient');
    }

     private function isWithinDentistSchedule(int $dentistId, string $appointmentTime): bool
    {
        $dt = Carbon::parse($appointmentTime);

        // يجب أن يكون الوقت محاذياً لبداية شريحة 30 دقيقة (00 أو 30)
        if ($dt->minute % 30 !== 0 || $dt->second !== 0) {
            return false;
        }

        // اسم اليوم بصيغة lowercase الكاملة، مطابقة لعمود day_of_week
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
     * 1. عرض مواعيد المريض الحالية والنشطة
     */
    public function index(Request $request): JsonResponse
    {

        $patientId = $request->user()->patient->id;

        $appointments = Appointment::with(['dentist.employee.user', 'appointmentType','treatment.treatmentType'])
            ->where('patient_id', $patientId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->orderBy('appointment_time', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $appointments->count(),
            'data'    => AppointmentResource::collection($appointments),
        ]);
    }

    /**
     * 11. عرض مواعيد المريض السابقة
     */
    public function previous(Request $request): JsonResponse
    {

        $patientId = $request->user()->patient->id;

        $appointments = Appointment::with(['dentist.employee.user', 'appointmentType','treatment.treatmentType'])
            ->where('patient_id', $patientId)
            ->whereIn('status', self::HISTORY_STATUSES)
            ->orderBy('appointment_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $appointments->count(),
            'data'    => AppointmentResource::collection($appointments),
        ]);
    }

    /**
     * 2. عرض موعد محدد
     */
    public function show(Request $request, int $id): JsonResponse
    {

        $patientId = $request->user()->patient->id;

        $appointment = Appointment::with(['dentist.employee.user', 'appointmentType','treatment.treatmentType'])
            ->where('patient_id', $patientId)
            ->find($id);

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'الموعد غير موجود.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new AppointmentResource($appointment),
        ]);
    }


    /**
     * 1. حجز موعد جديد (كشف جديد / متابعة علاج / طارئ)
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $user = $request->user();
        $patientId = $user->patient->id;

        // الحد الأقصى للمواعيد قيد الانتظار
        $pendingCount = Appointment::where('patient_id', $patientId)
            ->whereIn('status', ['pending', 'pending_secretary'])
            ->count();

        $maxPending = config('appointments.max_pending_per_patient');
        if ($pendingCount >= $maxPending) {
            return response()->json([
                'success' => false,
                'message' => "لديك بالفعل {$maxPending} موعد/مواعيد قيد الانتظار. يرجى انتظار الرد عليها قبل حجز موعد جديد."
            ], 422);
        }

        // التحقق من توافق اختصاص الطبيب مع نوع الموعد
        $isQualified = DB::table('appointment_type_specializations')
            ->join('dentists', 'dentists.specialization_id', '=', 'appointment_type_specializations.specialization_id')
            ->where('appointment_type_specializations.appointment_type_id', $request->appointment_type_id)
            ->where('dentists.id', $request->dentist_id)
            ->exists();

        if (!$isQualified) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطبيب لا يعالج هذا النوع من المواعيد.'
            ], 422);
        }

        if (!$this->isWithinDentistSchedule($request->dentist_id, $request->appointment_time)) {
            return response()->json([
                'success' => false,
                'message' => 'الوقت المحدد لا يقع ضمن ساعات دوام الطبيب.'
            ], 422);
        }

        // إن كان النوع "متابعة علاج"، التأكد أن العلاج يخص هذا المريض وهذا الطبيب
        if ($request->type === 'continue_treatment') {
            $treatmentBelongsToPatient = Treatment::where('id', $request->treatment_id)
                ->where('patient_id', $patientId)
                ->where('dentist_id', $request->dentist_id)
                ->exists();

            if (!$treatmentBelongsToPatient) {
                return response()->json([
                    'success' => false,
                    'message' => 'العلاج المحدد لا يخص هذا المريض أو هذا الطبيب.'
                ], 422);
            }
        }

        try {
            $appointment = DB::transaction(function () use ($request, $patientId) {
                $slotLock = $request->dentist_id . '_' . $request->appointment_time;

                $conflict = Appointment::where('slot_lock', $slotLock)
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
                    'status'              => 'pending',
                    'appointment_time'    => $request->appointment_time,
                    'slot_lock'           => $slotLock,
                    'notes'               => $request->notes,
                ]);
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'تم حجز هذا الموعد للتو من قبل مريض آخر، يرجى اختيار وقت آخر.'
            ], 409);
         } catch (\Illuminate\Database\QueryException $e) {
            // 23000 = SQL state for unique/integrity constraint violation
            if ($e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'تم حجز هذا الموعد للتو من قبل مريض آخر، يرجى اختيار وقت آخر.'
                ], 409);
            }

            throw $e; // أي خطأ آخر يجب أن يظهر بوضوح وليس كأنه تعارض حجز
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب الحجز بنجاح، بانتظار موافقة الطبيب.',
            'data'    => new AppointmentResource($appointment->fresh(['dentist.employee.user', 'appointmentType'])),
        ], 201);
    }

    /**
     * 2. تعديل (إعادة جدولة) موعد قيد الانتظار فقط
     */
    public function update(UpdateAppointmentRequest $request, int $id): JsonResponse
    {
        $patientId = $request->user()->patient->id;

        $appointment = Appointment::where('patient_id', $patientId)->find($id);

        if (!$appointment) {
            return response()->json(['success' => false, 'message' => 'الموعد غير موجود.'], 404);
        }

        // فقط المواعيد "قيد الانتظار" يمكن تعديلها
        if (!in_array($appointment->status, ['pending', 'pending_secretary'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل هذا الموعد إلا وهو قيد الانتظار.'
            ], 422);
        }

        $minHours = config('appointments.min_hours_before_change');
        if (Carbon::now()->diffInHours($appointment->appointment_time, false) < $minHours) {
            return response()->json([
                'success' => false,
                'message' => "لا يمكن تعديل الموعد قبل أقل من {$minHours} ساعة من موعده."
            ], 422);
        }

        $limit = config('appointments.reschedule_limit');
        if ($appointment->reschedule_count >= $limit) {
            return response()->json([
                'success' => false,
                'message' => 'لقد تجاوزت الحد المسموح به لإعادة جدولة هذا الموعد.'
            ], 422);
        }

        if (!$this->isWithinDentistSchedule($appointment->dentist_id, $request->appointment_time)) {
            return response()->json([
                'success' => false,
                'message' => 'الوقت المحدد لا يقع ضمن ساعات دوام الطبيب.'
            ], 422);
        }

        $newSlotLock = $appointment->dentist_id . '_' . $request->appointment_time;

        try {
            DB::transaction(function () use ($appointment, $request, $newSlotLock) {
                $conflict = Appointment::where('slot_lock', $newSlotLock)
                    ->where('id', '!=', $appointment->id)
                    ->lockForUpdate()
                    ->exists();

                if ($conflict) {
                    throw new \RuntimeException('SLOT_TAKEN');
                }

                $appointment->update([
                    'appointment_time' => $request->appointment_time,
                    'notes'            => $request->notes ?? $appointment->notes,
                    'status'           => 'pending',
                    'reschedule_count' => $appointment->reschedule_count + 1,
                    'slot_lock'        => $newSlotLock,
                ]);
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'تم حجز هذا الموعد للتو من قبل مريض آخر، يرجى اختيار وقت آخر.'
            ], 409);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'تم حجز هذا الموعد للتو من قبل مريض آخر، يرجى اختيار وقت آخر.'
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب تعديل الموعد بنجاح، بانتظار الموافقة.',
            'data'    => new AppointmentResource($appointment->fresh(['dentist.employee.user', 'appointmentType'])),
        ]);
    }

    /**
     * 3. إلغاء موعد قبل 24 ساعة من موعده
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $patientId = $request->user()->patient->id;

        $appointment = Appointment::where('patient_id', $patientId)->find($id);

        if (!$appointment) {
            return response()->json(['success' => false, 'message' => 'الموعد غير موجود.'], 404);
        }

        if (!in_array($appointment->status, self::ACTIVE_STATUSES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء هذا الموعد في حالته الحالية.'
            ], 422);
        }

        $minHours = config('appointments.min_hours_before_change');
        if (Carbon::now()->diffInHours($appointment->appointment_time, false) < $minHours) {
            return response()->json([
                'success' => false,
                'message' => "لا يمكن إلغاء الموعد قبل أقل من {$minHours} ساعة من موعده."
            ], 422);
        }

        $appointment->update([
            'status'    => 'cancelled',
            'slot_lock' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'تم إلغاء الموعد بنجاح.']);
    }
}

//     /**
//      * 6. تعديل (إعادة جدولة) موعد
//      */
//     public function update(UpdateAppointmentRequest $request, int $id): JsonResponse
//     {

//         $patientId = $request->user()->patient->id;

//         $appointment = Appointment::where('patient_id', $patientId)->find($id);

//         if (!$appointment) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'الموعد غير موجود.'
//             ], 404);
//         }

//         if (!in_array($appointment->status, self::ACTIVE_STATUSES, true)) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'لا يمكن تعديل هذا الموعد في حالته الحالية.'
//             ], 422);
//         }

//         $minHours = config('appointments.min_hours_before_change');
//         if (Carbon::now()->diffInHours($appointment->appointment_time, false) < $minHours) {
//             return response()->json([
//                 'success' => false,
//                 'message' => "لا يمكن تعديل الموعد قبل أقل من {$minHours} ساعة من موعده."
//             ], 422);
//         }

//         $limit = config('appointments.reschedule_limit');
//         if ($appointment->reschedule_count >= $limit) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'لقد تجاوزت الحد المسموح به لإعادة جدولة هذا الموعد.'
//             ], 422);
//         }

//         $appointment->update([
//             'appointment_time' => $request->appointment_time,
//             'notes'            => $request->notes ?? $appointment->notes,
//             'status'           => 'pending', // requires re-approval after reschedule
//             'reschedule_count' => $appointment->reschedule_count + 1,
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'تم إرسال طلب تعديل الموعد بنجاح، بانتظار الموافقة.',
//             'data'    => new AppointmentResource($appointment->fresh(['dentist.employee.user', 'appointmentType'])),
//         ]);
//     }

//     /**
//      * 7. إلغاء موعد
//      */
//     public function cancel(Request $request, int $id): JsonResponse
//     {

//         $patientId = $request->user()->patient->id;

//         $appointment = Appointment::where('patient_id', $patientId)->find($id);

//         if (!$appointment) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'الموعد غير موجود.'
//             ], 404);
//         }

//         if (!in_array($appointment->status, self::ACTIVE_STATUSES, true)) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'لا يمكن إلغاء هذا الموعد في حالته الحالية.'
//             ], 422);
//         }

//         $minHours = config('appointments.min_hours_before_change');
//         if (Carbon::now()->diffInHours($appointment->appointment_time, false) < $minHours) {
//             return response()->json([
//                 'success' => false,
//                 'message' => "لا يمكن إلغاء الموعد قبل أقل من {$minHours} ساعة من موعده."
//             ], 422);
//         }

//         $appointment->update([
//             'status'    => 'cancelled',
//             'slot_lock' => null,
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'تم إلغاء الموعد بنجاح.',
//         ]);
//     }
// // حجز موعد

//     public function store(StoreAppointmentRequest $request): JsonResponse
//     {
//         $user = $request->user();
//         $patientId = $user->patient->id;

//         // 1. التحقق من عدم تجاوز الحد الأقصى للمواعيد قيد الانتظار
//         $pendingCount = Appointment::where('patient_id', $patientId)
//             ->whereIn('status', ['pending', 'pending_secretary'])
//             ->count();

//         $maxPending = config('appointments.max_pending_per_patient');
//         if ($pendingCount >= $maxPending) {
//             return response()->json([
//                 'success' => false,
//                 'message' => "لديك بالفعل {$maxPending} موعد/مواعيد قيد الانتظار. يرجى انتظار الرد عليها قبل حجز موعد جديد."
//             ], 422);
//         }

//         // 2. التحقق من أن الطبيب مؤهل لهذا النوع من المواعيد (توافق الاختصاص)
//         $isQualified = DB::table('appointment_type_specializations')
//             ->join('dentists', 'dentists.specialization_id', '=', 'appointment_type_specializations.specialization_id')
//             ->where('appointment_type_specializations.appointment_type_id', $request->appointment_type_id)
//             ->where('dentists.id', $request->dentist_id)
//             ->exists();

//         if (!$isQualified) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'هذا الطبيب لا يعالج هذا النوع من المواعيد.'
//             ], 422);
//         }

//         // 3. إذا كان النوع "متابعة علاج"، التأكد أن العلاج يخص هذا المريض وهذا الطبيب
//         if ($request->type === 'continue_treatment') {
//             $treatmentBelongsToPatient = Treatment::where('id', $request->treatment_id)
//                 ->where('patient_id', $patientId)
//                 ->where('dentist_id', $request->dentist_id)
//                 ->exists();

//             if (!$treatmentBelongsToPatient) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'العلاج المحدد لا يخص هذا المريض أو هذا الطبيب.'
//                 ], 422);
//             }
//         }

//         // 4. الحجز الفعلي مع قفل الصف لمنع تعارض الأوقات

//         try {
//             $appointment = DB::transaction(function () use ($request, $patientId) {
//                 $slotLock = $request->dentist_id . '_' . $request->appointment_time;

//                 $conflict = Appointment::where('slot_lock', $slotLock)
//                     ->lockForUpdate()
//                     ->exists();

//                 if ($conflict) {
//                     throw new \RuntimeException('SLOT_TAKEN');
//                 }

//                 return Appointment::create([
//                     'patient_id'          => $patientId,
//                     'dentist_id'          => $request->dentist_id,
//                     'appointment_type_id' => $request->appointment_type_id,
//                     'treatment_id'        => $request->treatment_id,
//                     'type'                => $request->type,
//                     'status'              => 'pending',
//                     'appointment_time'    => $request->appointment_time,
//                     'slot_lock'           => $slotLock,
//                     'notes'               => $request->notes,
//                 ]);
//             });
//         } catch (\RuntimeException $e) {
//             if ($e->getMessage() === 'SLOT_TAKEN') {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'تم حجز هذا الموعد للتو من قبل مريض آخر، يرجى اختيار وقت آخر.'
//                 ], 409);
//             }
//             throw $e;
//         } catch (\Illuminate\Database\QueryException $e) {
//             // شبكة أمان أخيرة إن مرّ الطلبان معاً قبل القفل
//             return response()->json([
//                 'success' => false,
//                 'message' => 'تم حجز هذا الموعد للتو من قبل مريض آخر، يرجى اختيار وقت آخر.'
//             ], 409);
//         }

//         return response()->json([
//             'success' => true,
//             'message' => 'تم إرسال طلب الحجز بنجاح، بانتظار موافقة الطبيب.',
//             'data'    => new AppointmentResource($appointment->fresh(['dentist.employee.user', 'appointmentType'])),
//         ], 201);
//     }

