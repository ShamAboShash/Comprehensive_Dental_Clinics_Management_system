<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dentist\TreatmentPhotoResource;
use App\Models\TreatmentPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientTreatmentPhotoController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-patient');
    }

    /**
     * 16. عرض صور علاجات المريض
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $request->user()->patient->id;

        $photos = TreatmentPhoto::whereHas('session.treatment', function ($q) use ($patientId) {
            $q->where('patient_id', $patientId);
        })
            ->with('session')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $photos->count(),
            'data'    => TreatmentPhotoResource::collection($photos),
        ]);
    }
}
