<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\patient\TreatmentResource;
use App\Models\Treatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientTreatmentController extends Controller
{

     function __construct()
    {
        Gate::authorize('is-patient');
    }

    /**
     * 12. عرض علاجات المريض
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $request->user()->patient->id;

        $treatments = Treatment::with(['dentist.employee.user', 'treatmentType'])
            ->where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $treatments->count(),
            'data'    => TreatmentResource::collection($treatments),
        ]);
    }

    /**
     * 13. عرض علاج محدد
     */
    // public function show(Request $request, int $id): JsonResponse
    // {
    //     $patientId = $request->user()->patient->id;

   //     $treatment = Treatment::with(['dentist.employee.user', 'treatmentType'])
    //          ->where('patient_id', $patientId)
    //         ->find($id);

    //     if (!$treatment) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'العلاج غير موجود.'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data'    => new TreatmentResource($treatment),
    //     ]);
    // }


    public function show(Request $request, int $id): JsonResponse
    {


        $patientId = $request->user()->patient->id;

             $treatment = Treatment::
                where('patient_id', $patientId)
                ->find($id);

            if (!$treatment) {
                return response()->json([
                    'success' => false,
                    'message' => 'العلاج غير موجود.'
                ], 404);
            }


        $treatment->load([
            'treatmentType',
            'dentist.employee.user',
            'sessions.dentist.employee.user',
            'sessions.toothTreatments.procedure',
        ]);

        return response()->json([
            'success' => true,
            'data'    => new TreatmentResource($treatment),
        ]);
    }

}
