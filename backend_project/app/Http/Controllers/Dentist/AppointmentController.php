<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dentist\UpdateAppointmentStatusRequest;
use App\Http\Resources\Dentist\AppointmentResource;
use App\Models\Appointment;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Services\NotificationService;

class AppointmentController extends Controller
{
    // ---------------------------------------------------------------
    // Helper: resolve the authenticated dentist's ID safely
    // ---------------------------------------------------------------
    private function getDentistId(Request $request): int
    {
        return $request->user()->employee->dentist->id;
    }

    // ---------------------------------------------------------------
    // GET /dentist/appointments?filter=daily|weekly|monthly|yesterday
    // API #1 — Show all appointments with period filter
    // ---------------------------------------------------------------
   public function daily(Request $request): JsonResponse
{
    Gate::authorize('is-dentist');

    $dentistId    = $this->getDentistId($request);
    $appointments = Appointment::with(['patient.user', 'appointmentType','treatment'])
        ->where('dentist_id', $dentistId)
        ->whereDate('appointment_time', Carbon::today())
        ->orderBy('appointment_time')
        ->get();

    return response()->json([
        'success' => true,
        'date'    => Carbon::today()->toDateString(),
        'count'   => $appointments->count(),
        'data'    => AppointmentResource::collection($appointments),
    ]);
}

// ---------------------------------------------------------------
// GET /dentist/patients/{patient}/appointments
// Show all appointments between this dentist and patient
// ---------------------------------------------------------------
public function dentistAppointments(Request $request, Patient $patient): JsonResponse
{
    Gate::authorize('is-dentist');

    $dentistId = $this->getDentistId($request);

    if (!Appointment::dentistHasAccessToPatient($dentistId, $patient->id)) {
        return response()->json([
            'success' => false,
            'message' => 'ليس لديك صلاحية الوصول إلى بيانات هذا المريض.',
        ], 403);
    }

    $appointments = Appointment::with(['appointmentType', 'treatment.treatmentType'])
        ->where('dentist_id', $dentistId)
        ->where('patient_id', $patient->id)
        ->orderBy('appointment_time')
        ->get();

    return response()->json([
        'success' => true,
        'count'   => $appointments->count(),
        'data'    => AppointmentResource::collection($appointments),
    ]);
}

// ---------------------------------------------------------------
// GET /dentist/appointments/yesterday
// API #1b — Yesterday's appointments
// ---------------------------------------------------------------
public function yesterday(Request $request): JsonResponse
{
    Gate::authorize('is-dentist');

    $dentistId    = $this->getDentistId($request);
    $appointments = Appointment::with(['patient.user', 'appointmentType','treatment'])
        ->where('dentist_id', $dentistId)
        ->whereDate('appointment_time', Carbon::yesterday())
        ->orderBy('appointment_time')
        ->get();

    return response()->json([
        'success' => true,
        'date'    => Carbon::yesterday()->toDateString(),
        'count'   => $appointments->count(),
        'data'    => AppointmentResource::collection($appointments),
    ]);
}

// ---------------------------------------------------------------
// GET /dentist/appointments/weekly
// API #1c — This week's appointments (Sat–Fri)
// ---------------------------------------------------------------
public function weekly(Request $request): JsonResponse
{
    Gate::authorize('is-dentist');

    $dentistId = $this->getDentistId($request);
    $start     = Carbon::now()->startOfWeek(Carbon::SUNDAY);
    $end       = Carbon::now()->endOfWeek(Carbon::SATURDAY);

    $appointments = Appointment::with(['patient.user', 'appointmentType','treatment'])
        ->where('dentist_id', $dentistId)
        ->whereBetween('appointment_time', [$start, $end])
        ->orderBy('appointment_time')
        ->get();

    return response()->json([
        'success'    => true,
        'start_date' => $start->toDateString(),
        'end_date'   => $end->toDateString(),
        'count'      => $appointments->count(),
        'data'       => AppointmentResource::collection($appointments),
    ]);
}

// ---------------------------------------------------------------
// GET /dentist/appointments/monthly
// API #1d — This month's appointments
// ---------------------------------------------------------------
public function monthly(Request $request): JsonResponse
{
    Gate::authorize('is-dentist');

    $dentistId = $this->getDentistId($request);

    $monthStart = Carbon::now()->startOfMonth();
    $monthEnd   = Carbon::now()->endOfMonth();

    $appointments = Appointment::with([
        'patient.user',
        'appointmentType',
        'treatment',
    ])
    ->where('dentist_id', $dentistId)
    ->whereMonth('appointment_time', Carbon::now()->month)
    ->whereYear('appointment_time', Carbon::now()->year)
    ->orderBy('appointment_time')
    ->get();

    // Slice into weeks
    $weeks = [];
    $weekStart = $monthStart->copy();
    $weekNumber = 1;

    while ($weekStart->lte($monthEnd)) {
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::FRIDAY);

        // Don't go past month end
        if ($weekEnd->gt($monthEnd)) {
            $weekEnd = $monthEnd->copy();
        }

        $weekAppointments = $appointments->filter(
            fn($a) => $a->appointment_time->between($weekStart, $weekEnd)
        )->values();

        $weeks[] = [
            'week'       => $weekNumber,
            'start_date' => $weekStart->toDateString(),
            'end_date'   => $weekEnd->toDateString(),
            'count'      => $weekAppointments->count(),
            'data'       => AppointmentResource::collection($weekAppointments),
        ];

        $weekStart = $weekEnd->copy()->addDay()->startOfDay();
        $weekNumber++;
    }

    return response()->json([
        'success' => true,
        'month'   => Carbon::now()->format('Y-m'),
        'weeks'   => $weeks,
    ]);
}
    // ---------------------------------------------------------------
    // GET /dentist/appointments/{appointment}
    // Show single appointment detail
    // ---------------------------------------------------------------
    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        Gate::authorize('is-dentist');

        if ($appointment->dentist_id !== $this->getDentistId($request)) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الموعد لا ينتمي إليك.',
            ], 403);
        }

        $appointment->load(['patient.user', 'appointmentType', 'treatment']);

        return response()->json([
            'success' => true,
            'data'    => new AppointmentResource($appointment),
        ]);
    }

    // ---------------------------------------------------------------
    // PATCH /dentist/appointments/{appointment}/status
    // API #3  — Approve / Reject / Complete / No-show
    // ---------------------------------------------------------------
    public function updateStatus(
        UpdateAppointmentStatusRequest $request,
        Appointment $appointment
    ): JsonResponse {
        Gate::authorize('is-dentist');

        if ($appointment->dentist_id !== $this->getDentistId($request)) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الموعد لا ينتمي إليك.',
            ], 403);
        }

        // Business rules
        $current = $appointment->status;
        $new      = $request->status;

       $allowed = match ($current) {
            'pending'           => ['approved', 'rejected'],
            'pending_secretary' => ['approved', 'rejected'],
            default             => [],
        };

        if (!in_array($new, $allowed)) {
            return response()->json([
                'success' => false,
                'message' => "لا يمكن تغيير حالة الموعد من '{$current}' إلى '{$new}'.",
            ], 422);
        }

        $appointment->update([
            'status'           => $new,
            'rejection_reason' => $request->rejection_reason ?? $appointment->rejection_reason,
        ]);


        $appointment->loadMissing('patient.user');

        if ($appointment->patient?->user) {
            if ($new === 'approved') {
                NotificationService::send(
                    user: $appointment->patient->user,
                    title: 'تم قبول الموعد',
                    body: "تم قبول موعدك رقم #{$appointment->id}",
                    type: 'appointment_approved',
                    referenceable: $appointment
                );
            }

            if ($new === 'rejected') {
                NotificationService::send(
                    user: $appointment->patient->user,
                    title: 'تم رفض الموعد',
                    body: "تم رفض موعدك رقم #{$appointment->id}",
                    type: 'appointment_rejected',
                    referenceable: $appointment
                );
            }
        }


        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الموعد بنجاح.',
            'data'    => new AppointmentResource($appointment->fresh(['patient.user', 'appointmentType'])),
        ]);
    }
}
