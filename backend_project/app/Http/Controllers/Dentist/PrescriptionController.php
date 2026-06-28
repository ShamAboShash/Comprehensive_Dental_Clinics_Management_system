<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dentist\StorePrescriptionRequest;
use App\Http\Requests\Dentist\UpdatePrescriptionRequest;
use App\Http\Resources\Dentist\PrescriptionResource;
use App\Models\Prescription;
use App\Models\Session;
use App\Models\Treatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PrescriptionController extends Controller
{
    private function getDentistId(Request $request): int
    {
        return $request->user()->employee->dentist->id;
    }

    // ---------------------------------------------------------------
    // POST /dentist/treatments/{treatment}/sessions/{session}/prescription
    // API #15 — Add prescription to a session (one per session)
    // ---------------------------------------------------------------
    public function store(
        StorePrescriptionRequest $request,
        Treatment $treatment,
        Session $session
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
                'message' => 'لا يمكنك إضافة وصفة لجلسة لم تقم بإنشائها.',
            ], 403);
        }

        // One prescription per session
        if ($session->prescription()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الجلسة تحتوي على وصفة طبية بالفعل، يمكنك تعديلها.',
            ], 422);
        }

        $prescription = Prescription::create([
            'session_id' => $session->id,
            'dentist_id' => $dentistId,
            'patient_id' => $treatment->patient_id,
            'notes'      => $request->notes,
        ]);

        // Attach medications via pivot
        $pivot = collect($request->medications)->mapWithKeys(
            fn($med) => [
                $med['medication_id'] => [
                    'dosage'    => $med['dosage'],
                    'frequency' => $med['frequency'],
                    'duration'  => $med['duration'] ?? null,
                    'notes'     => $med['notes'] ?? null,
                ],
            ]
        );

        $prescription->medications()->attach($pivot);
        $prescription->load('medications');

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الوصفة الطبية بنجاح.',
            'data'    => new PrescriptionResource($prescription),
        ], 201);
    }

    // ---------------------------------------------------------------
    // PUT /dentist/treatments/{treatment}/sessions/{session}/prescription
    // API #16 — Edit prescription
    // ---------------------------------------------------------------
    public function update(
        UpdatePrescriptionRequest $request,
        Treatment $treatment,
        Session $session
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
                'message' => 'لا يمكنك تعديل وصفة لجلسة لم تقم بإنشائها.',
            ], 403);
        }

        $prescription = $session->prescription;

        if (!$prescription) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد وصفة طبية لهذه الجلسة.',
            ], 404);
        }

        if ($request->filled('notes')) {
            $prescription->update(['notes' => $request->notes]);
        }

        // Sync replaces all medications with the new list
        if ($request->has('medications')) {
            $pivot = collect($request->medications)->mapWithKeys(
                fn($med) => [
                    $med['medication_id'] => [
                        'dosage'    => $med['dosage'],
                        'frequency' => $med['frequency'],
                        'duration'  => $med['duration'] ?? null,
                        'notes'     => $med['notes'] ?? null,
                    ],
                ]
            );

            $prescription->medications()->sync($pivot);
        }

        $prescription->load('medications');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الوصفة الطبية بنجاح.',
            'data'    => new PrescriptionResource($prescription),
        ]);
    }
}
