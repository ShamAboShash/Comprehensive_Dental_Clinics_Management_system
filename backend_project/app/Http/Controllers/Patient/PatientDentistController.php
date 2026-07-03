<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\patient\DentistResource;
use App\Models\Dentist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientDentistController extends Controller
{
    /**
     * 9 & 4. عرض كل الأطباء، مع إمكانية الفلترة حسب الاختصاص
     */
    public function index(Request $request,$specialization_id = null): JsonResponse
    {
        $query = Dentist::with(['employee.user', 'specialization']);

        if ($specialization_id !== null) {
            $query->where('specialization_id', $specialization_id);
        }

        $dentists = $query->get();

        return response()->json([
            'success' => true,
            'count'   => $dentists->count(),
            'data'    => DentistResource::collection($dentists),
        ]);
    }

    /**
     * 10. عرض طبيب محدد
     */
    public function show(Dentist $dentist): JsonResponse
    {
        $dentist->load(['employee.user', 'specialization']);

        return response()->json([
            'success' => true,
            'data'    => new DentistResource($dentist),
        ]);
    }
}
