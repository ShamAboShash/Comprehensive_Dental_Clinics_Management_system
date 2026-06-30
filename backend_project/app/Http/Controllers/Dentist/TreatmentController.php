<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dentist\StoreTreatmentRequest;
use App\Http\Requests\Dentist\UpdateTreatmentRequest;
use App\Http\Resources\Dentist\TreatmentDetailResource;
use App\Http\Resources\Dentist\TreatmentResource;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TreatmentController extends Controller
{
    // ---------------------------------------------------------------
    // Helper: verify dentist has access to this patient
    // ---------------------------------------------------------------
    private function hasPatientAccess(int $dentistId, Patient $patient): bool
    {
        return Appointment::where('dentist_id', $dentistId)
            ->where('patient_id', $patient->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->exists();
    }

    // ---------------------------------------------------------------
    // Helper: resolve dentist ID from request
    // ---------------------------------------------------------------
    private function getDentistId(Request $request): int
    {
        return $request->user()->employee->dentist->id;
    }

    // ---------------------------------------------------------------
    // GET /dentist/patients/{patient}/treatments
    // API #6 (list) — All treatments for a patient (all dentists)
    // ---------------------------------------------------------------
    public function index(Request $request, Patient $patient): JsonResponse
    {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if (!$this->hasPatientAccess($dentistId, $patient)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية الوصول إلى بيانات هذا المريض.',
            ], 403);
        }

        $treatments = Treatment::with(['treatmentType', 'dentist.employee.user', 'sessions.toothTreatments.procedure'])
            ->where('patient_id', $patient->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $treatments->count(),
            'data'    => TreatmentResource::collection($treatments),
        ]);
    }

    // ---------------------------------------------------------------
    // GET /dentist/treatments/{treatment}
    // API #6 (detail) — Treatment with all its sessions
    // ---------------------------------------------------------------
    public function show(Request $request, Treatment $treatment): JsonResponse
    {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);
        $patient   = $treatment->patient;

        if (!$this->hasPatientAccess($dentistId, $patient)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية الوصول إلى هذا العلاج.',
            ], 403);
        }

        $treatment->load([
            'treatmentType',
            'dentist.employee.user',
            'patient.user',
            'sessions.dentist.employee.user',
            'sessions.toothTreatments.procedure',

        ]);

        return response()->json([
            'success' => true,
            'data'    => new TreatmentDetailResource($treatment),
        ]);
    }

    // ---------------------------------------------------------------
    // POST /dentist/patients/{patient}/treatments
    // API #9 — Add new treatment for a patient
    // ---------------------------------------------------------------
    public function store(
        StoreTreatmentRequest $request,
        Patient $patient
    ): JsonResponse {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if (!$this->hasPatientAccess($dentistId, $patient)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية إضافة علاج لهذا المريض.',
            ], 403);
        }

        $treatment = Treatment::create([
            'patient_id'            => $patient->id,
            'dentist_id'            => $dentistId,
            'treatment_type_id'     => $request->treatment_type_id,
            'total_sessions_needed' => $request->total_sessions_needed,
            'sessions_completed'    => 0,
            'status'                => 'ongoing',
            'notes'                 => $request->notes,
        ]);

        $treatment->load(['treatmentType', 'dentist.employee.user', 'patient.user']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء العلاج بنجاح.',
            'data'    => new TreatmentResource($treatment),
        ], 201);
    }

    // ---------------------------------------------------------------
    // PUT /dentist/treatments/{treatment}
    // API #10 — Edit treatment (own treatments only)
    // ---------------------------------------------------------------
    public function update(
        UpdateTreatmentRequest $request,
        Treatment $treatment
    ): JsonResponse {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if ($treatment->dentist_id !== $dentistId) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تعديل علاج لم تقم بإنشائه.',
            ], 403);
        }

        $treatment->update($request->only([
            'treatment_type_id',
            'total_sessions_needed',
            'status',
            'notes',
        ]));

        $treatment->load(['treatmentType', 'dentist.employee.user', 'patient.user']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث العلاج بنجاح.',
            'data'    => new TreatmentResource($treatment),
        ]);
    }
}
