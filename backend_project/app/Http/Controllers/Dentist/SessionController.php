<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dentist\StoreSessionRequest;
use App\Http\Requests\Dentist\UpdateSessionRequest;
use App\Http\Resources\Dentist\SessionDetailResource;
use App\Http\Resources\Dentist\SessionResource;
use App\Models\Appointment;
use App\Models\TreatmentSession;
use App\Models\Treatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SessionController extends Controller
{
    private function getDentistId(Request $request): int
    {
        return $request->user()->employee->dentist->id;
    }

    // ---------------------------------------------------------------
    // GET /dentist/treatments/{treatment}/sessions/{session}
    // API #7 — Session detail with tooth treatments, photos, prescription
    // ---------------------------------------------------------------

    // ---------------------------------------------------------------
    // GET /dentist/treatments/{treatment}/sessions
    // Show all sessions for a treatment
    // ---------------------------------------------------------------
    public function show(
        Request $request,
        Treatment $treatment,
        TreatmentSession $session
    ): JsonResponse {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        // Verify session belongs to this treatment
        if ($session->treatment_id !== $treatment->id) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الجلسة لا تنتمي إلى هذا العلاج.',
            ], 404);
        }

        // Access check — dentist must have appointment with patient
        $patient = $treatment->patient;
        $hasAccess = Appointment::where('dentist_id', $dentistId)
            ->where('patient_id', $patient->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->exists();

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية الوصول إلى هذه الجلسة.',
            ], 403);
        }

        $session->load([
            'dentist.employee.user',
            'toothTreatments.procedure',
            'photos',
            'prescription.medications',
        ]);

        return response()->json([
            'success' => true,
            'data'    => new SessionDetailResource($session),
        ]);
    }

    // ---------------------------------------------------------------
    // POST /dentist/treatments/{treatment}/sessions
    // API #11 — Add new session to a treatment
    // ---------------------------------------------------------------
    public function store(
        StoreSessionRequest $request,
        Treatment $treatment
    ): JsonResponse {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if ($treatment->dentist_id !== $dentistId) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إضافة جلسة لعلاج لم تقم بإنشائه.',
            ], 403);
        }

        if ($treatment->status !== 'ongoing') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إضافة جلسة لعلاج غير نشط.',
            ], 422);
        }

        $appointment = Appointment::find($request->appointment_id);
        if (
            $appointment->dentist_id !== $dentistId ||
            $appointment->patient_id !== $treatment->patient_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'الموعد المحدد لا ينتمي إلى هذا الطبيب أو المريض.',
            ], 422);
        }

        $nextNumber = TreatmentSession::where('treatment_id', $treatment->id)
            ->max('session_number') + 1;

        $session = TreatmentSession::create([
            'treatment_id'      => $treatment->id,
            'appointment_id'    => $request->appointment_id,
            'dentist_id'        => $dentistId,
            'session_number'    => $nextNumber,
            'actual_start_time' => $request->actual_start_time,
            'actual_end_time'   => $request->actual_end_time,
            'status'            => $request->status ?? 'completed',
            'notes'             => $request->notes,
        ]);

        // Create tooth treatments if provided
        if ($request->has('tooth_treatments')) {
            foreach ($request->tooth_treatments as $item) {
                $session->toothTreatments()->create([
                    'procedure_id' => $item['procedure_id'],
                    'tooth_number' => $item['tooth_number'],
                    'notes'        => $item['notes'] ?? null,
                ]);
            }
        }

        if ($session->status === 'completed') {
            $treatment->increment('sessions_completed');
            $treatment->refresh();
            if ($treatment->sessions_completed >= $treatment->total_sessions_needed) {
                $treatment->update(['status' => 'completed']);
            }
        }

        $session->load(['dentist.employee.user', 'toothTreatments.procedure']);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الجلسة بنجاح.',
            'data'    => new SessionDetailResource($session),
        ], 201);
    }

    // ---------------------------------------------------------------
    // PUT /dentist/treatments/{treatment}/sessions/{session}
    // API #12 — Edit session (own sessions only)
    // ---------------------------------------------------------------
    public function update(
        UpdateSessionRequest $request,
        Treatment $treatment,
        TreatmentSession $session
    ): JsonResponse {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if ($session->treatment_id !== $treatment->id) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الجلسة لا تنتمي إلى هذا العلاج.',
            ], 404);
        }

        if ($session->dentist_id !== $dentistId) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تعديل جلسة لم تقم بإنشائها.',
            ], 403);
        }

        $oldStatus = $session->status;

        $session->update($request->only([
            'actual_start_time',
            'actual_end_time',
            'status',
            'notes',
        ]));

        // Adjust sessions_completed if status changed
        if ($oldStatus !== $session->status) {
            if ($session->status === 'completed' && $oldStatus === 'missed') {
                $treatment->increment('sessions_completed');
            } elseif ($session->status === 'missed' && $oldStatus === 'completed') {
                $treatment->decrement('sessions_completed');
            }

            // Re-check if treatment should be marked completed
            $treatment->refresh();
            if (
                $treatment->status === 'ongoing' &&
                $treatment->sessions_completed >= $treatment->total_sessions_needed
            ) {
                $treatment->update(['status' => 'completed']);
            }
        }

        $session->load(['dentist.employee.user']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الجلسة بنجاح.',
            'data'    => new SessionResource($session),
        ]);
    }
}
