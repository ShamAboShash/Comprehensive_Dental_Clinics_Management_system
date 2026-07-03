<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientPrescriptionController extends Controller
{
    /**
     * 15. عرض وصفات المريض
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $request->user()->patient->id;

        $prescriptions = Prescription::with(['dentist.employee.user', 'medications.medication'])
            ->where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $prescriptions->count(),
            'data'    => PrescriptionResource::collection($prescriptions),
        ]);
    }

    /**
     * 14. عرض وصفة محددة
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $patientId = $request->user()->patient->id;

        $prescription = Prescription::with(['dentist.employee.user', 'medications.medication'])
            ->where('patient_id', $patientId)
            ->find($id);

        if (!$prescription) {
            return response()->json([
                'success' => false,
                'message' => 'الوصفة غير موجودة.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new PrescriptionResource($prescription),
        ]);
    }
}
