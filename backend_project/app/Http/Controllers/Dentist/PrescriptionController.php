<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dentist\StorePrescriptionRequest;
use App\Http\Requests\Dentist\UpdatePrescriptionRequest;
use App\Http\Resources\Dentist\PrescriptionResource;
use App\Models\DrugInteraction;
use App\Models\Prescription;
use App\Models\TreatmentSession;
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
    // Helper: check interactions and build the warning response
    // Returns null if no interactions (or overridden), otherwise the response array
    // ---------------------------------------------------------------
    private function checkInteractions($patientMedIds, $newMedIds): ?array
    {
        $interactions = DrugInteraction::findInteractions($patientMedIds, $newMedIds);

        if ($interactions->isEmpty()) {
            return null;
        }

        return [
            'message'      => 'تحذير: توجد تفاعلات دوائية محتملة بين الوصفة الجديدة وأدوية المريض الحالية.',
            'interactions' => $interactions->map(fn($i) => [
                'medication_1' => $i->medication1->scientific_name,
                'medication_2' => $i->medication2->scientific_name,
                'severity'     => $i->severity,
                'description'  => $i->description,
            ]),
            'can_override' => true,
        ];
    }

    // ---------------------------------------------------------------
    // POST /dentist/treatments/{treatment}/sessions/{session}/prescription
    // API #15 — Add prescription to a session (one per session)
    // ---------------------------------------------------------------
    public function store(
        StorePrescriptionRequest $request,
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
                'message' => 'لا يمكنك إضافة وصفة لجلسة لم تقم بإنشائها.',
            ], 403);
        }

        if ($session->prescription()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الجلسة تحتوي على وصفة طبية بالفعل، يمكنك تعديلها.',
            ], 422);
        }

        // -------------------------------------------------------
        // Drug interaction check
        // -------------------------------------------------------
        $patient       = $treatment->patient->load('medications');
        $patientMedIds = $patient->medications->pluck('id');
        $newMedIds     = collect($request->medications)->pluck('medication_id');

        $warning = $this->checkInteractions($patientMedIds, $newMedIds);

        if ($warning && !$request->boolean('force')) {
            return response()->json(array_merge(
                ['success' => false],
                $warning
            ), 422);
        }

        // -------------------------------------------------------
        // Save the prescription (log override if forced)
        // -------------------------------------------------------
        $prescription = Prescription::create([
            'session_id'            => $session->id,
            'dentist_id'            => $dentistId,
            'patient_id'            => $treatment->patient_id,
            'notes'                 => $request->notes,
            'interaction_override'  => $warning ? true : false,
        ]);

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

        // -------------------------------------------------------
        // Drug interaction check on updated medications
        // -------------------------------------------------------
        if ($request->has('medications')) {
            $patient       = $treatment->patient->load('medications');
            $patientMedIds = $patient->medications->pluck('id');
            $newMedIds     = collect($request->medications)->pluck('medication_id');

            $warning = $this->checkInteractions($patientMedIds, $newMedIds);

            if ($warning && !$request->boolean('force')) {
                return response()->json(array_merge(
                    ['success' => false],
                    $warning
                ), 422);
            }

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

            // Update override flag based on this update's outcome
            $prescription->update([
                'interaction_override' => $warning ? true : false,
            ]);
        }

        if ($request->filled('notes')) {
            $prescription->update(['notes' => $request->notes]);
        }

        $prescription->load('medications');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الوصفة الطبية بنجاح.',
            'data'    => new PrescriptionResource($prescription),
        ]);
    }
}
