<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dentist\AddMedicalInfoRequest;
use App\Http\Resources\Dentist\PatientListResource;
use App\Http\Resources\Dentist\PatientProfileResource;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientController extends Controller
{
    // ---------------------------------------------------------------
    // Helper: verify this dentist has an appointment with the patient
    // ---------------------------------------------------------------
    private function hasAccess(int $dentistId, Patient $patient): bool
    {
        return Appointment::where('dentist_id', $dentistId)
            ->where('patient_id', $patient->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->exists();
    }

    // ---------------------------------------------------------------
    // GET /dentist/patients
    // API #4 — Show all dentist's patients (distinct, ever had appointment)
    // ---------------------------------------------------------------
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('is-dentist');

        $dentistId = $request->user()->employee->dentist->id;

        // Distinct patients who have a non-cancelled appointment with this dentist
        $patients = Patient::with('user')
            ->whereHas('appointments', fn($q) => $q
                ->where('dentist_id', $dentistId)
                ->whereNotIn('status', ['cancelled', 'rejected'])
            )
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $patients->count(),
            'data'    => PatientListResource::collection($patients),
        ]);
    }

    // ---------------------------------------------------------------
    // GET /dentist/patients/{patient}
    // API #5 — Show full patient profile + medical info + treatments
    // ---------------------------------------------------------------
    public function show(Request $request, Patient $patient): JsonResponse
    {
        Gate::authorize('is-dentist');

        $dentistId = $request->user()->employee->dentist->id;

        if (!$this->hasAccess($dentistId, $patient)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية الوصول إلى بيانات هذا المريض.',
            ], 403);
        }

        $patient->load([
            'user',
            'allergies',
            'chronicDiseases.category',
            'medications',              // pivot: dosage, frequency, notes
            'treatments.treatmentType',
            'treatments.dentist.employee.user',
        ]);

        return response()->json([
            'success' => true,
            'data'    => new PatientProfileResource($patient),
        ]);
    }

    // ---------------------------------------------------------------
    // POST /dentist/patients/{patient}/medical-info
    // API #8 — Add allergies / chronic diseases / medications
    // ---------------------------------------------------------------
    public function addMedicalInfo(
        AddMedicalInfoRequest $request,
        Patient $patient
    ): JsonResponse {
        Gate::authorize('is-dentist');

        $dentistId = $request->user()->employee->dentist->id;

        if (!$this->hasAccess($dentistId, $patient)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية تعديل بيانات هذا المريض.',
            ], 403);
        }

        if ($request->has('allergies')) {
            foreach ($request->allergies as $name) {
                $patient->allergies()->firstOrCreate(['allergy_name' => $name]);
            }
        }

        if ($request->has('chronic_diseases')) {
            foreach ($request->chronic_diseases as $disease) {
                $patient->chronicDiseases()->firstOrCreate(
                    [
                        'category_id'  => $disease['category_id'] ?? null,
                        'problem_name' => $disease['problem_name'],
                    ],
                    [
                        'details'  => $disease['details'] ?? null,
                        'severity' => $disease['severity'] ?? null,
                    ]
                );
            }
        }

        if ($request->has('medications')) {
            foreach ($request->medications as $med) {
                $patient->medications()->syncWithoutDetaching([
                    $med['medication_id'] => [
                        'dosage'    => $med['dosage'] ?? null,
                        'frequency' => $med['frequency'] ?? null,
                        'notes'     => $med['notes'] ?? null,
                    ],
                ]);
            }
        }

        $patient->load(['allergies', 'chronicDiseases.category', 'medications']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث السجل الطبي للمريض بنجاح.',
            'data'    => new PatientProfileResource($patient),
        ]);
    }
}
