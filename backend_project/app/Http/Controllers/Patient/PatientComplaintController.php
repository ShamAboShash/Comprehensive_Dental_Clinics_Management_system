<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StoreComplaintRequest;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientComplaintController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-patient');
    }

    /**
     * 17. عرض شكاوى المريض
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $request->user()->patient->id;

        $complaints = Complaint::where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $complaints->count(),
            'data'    => ComplaintResource::collection($complaints),
        ]);
    }

    /**
     * 18. إضافة شكوى جديدة
     */
    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $user = $request->user();

        $complaint = Complaint::create([
            'patient_id'    => $user->patient->id,
            'submitted_by'  => $user->id,
            'title'         => $request->title,
            'description'   => $request->description,
            'phone_number'  => $request->phone_number,
            'priority'      => $request->priority ?? 'medium',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال شكواك بنجاح.',
            'data'    => new ComplaintResource($complaint),
        ], 201);
    }
}
