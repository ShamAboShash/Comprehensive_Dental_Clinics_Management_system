<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\StoreComplaintRequest;
use App\Http\Requests\Secretary\UpdateComplaintStatusRequest;
use App\Models\Complaint;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ComplaintController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-secretary');
    }

    /**
     * 24. عرض الشكاوى
     * GET /secretary/showAllComplaints?status=&priority=&patient_id=
     */
    // public function index(Request $request): JsonResponse
    // {
    //     $query = Complaint::with(['patient.user', 'submittedBy'])
    //         ->orderByDesc('created_at');

    //     if ($request->filled('status')) {
    //         $query->where('status', $request->status);
    //     }
    //     if ($request->filled('priority')) {
    //         $query->where('priority', $request->priority);
    //     }
    //     if ($request->filled('patient_id')) {
    //         $query->where('patient_id', $request->patient_id);
    //     }

    //     $complaints = $query->get();

    //     return response()->json([
    //         'success' => true,
    //         'count'   => $complaints->count(),
    //         'data'    => $complaints->map(fn ($c) => $this->format($c)),
    //     ]);
    // }

    // /**
    //  * 26. عرض شكوى حسب id
    //  * GET /secretary/showComplaint/{id}
    //  */
    // public function show(int $id): JsonResponse
    // {
    //     $complaint = Complaint::with(['patient.user', 'submittedBy'])->find($id);

    //     if (!$complaint) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'الشكوى غير موجودة.',
    //         ], 404);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $this->format($complaint),
    //     ]);
    // }

    /**
     * 25. إضافة شكوى
     * POST /secretary/addComplaint
     *
     * Secretary can submit on behalf of a patient.
     */
    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $patient = Patient::with('user')->find($request->patient_id);

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'المريض غير موجود.',
            ], 404);
        }

        $complaint = Complaint::create([
            'patient_id'    => $patient->id,
            'submitted_by'  => $request->user()->id,   // secretary
            'title'         => $request->title,
            'description'   => $request->description,
            'phone_number'  => $request->phone_number ?? $patient->user?->phone,
            'status'        => 'pending',
            'priority'      => $request->priority ?? 'medium',
        ]);

        $complaint->load(['patient.user', 'submittedBy']);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الشكوى بنجاح.',
            'data'    => $this->format($complaint),
        ], 201);
    }

    /**
     * 27. تعديل حالة الشكوى
     * POST /secretary/updateComplaintStatus/{id}
     */
    public function updateStatus(UpdateComplaintStatusRequest $request, int $id): JsonResponse
    {
        $complaint = Complaint::with(['patient.user', 'submittedBy'])->find($id);

        if (!$complaint) {
            return response()->json([
                'success' => false,
                'message' => 'الشكوى غير موجودة.',
            ], 404);
        }

        $current = $complaint->status;
        $new     = $request->status;

        // Allowed transitions for secretary
        $allowed = match ($current) {
            'pending'   => ['in_review', 'resolved', 'rejected'],
            'in_review' => ['resolved', 'rejected', 'pending'],
            'resolved', 'rejected' => [], // final — admin can reopen if needed
            default     => [],
        };

        if (!in_array($new, $allowed, true)) {
            return response()->json([
                'success' => false,
                'message' => "لا يمكن تغيير حالة الشكوى من '{$current}' إلى '{$new}'.",
            ], 422);
        }

        $complaint->update([
            'status'         => $new,
            'priority'       => $request->priority ?? $complaint->priority,
            'admin_response' => $request->has('admin_response')
                ? $request->admin_response
                : $complaint->admin_response,
        ]);

        $complaint->load(['patient.user', 'submittedBy']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الشكوى بنجاح.',
            'data'    => $this->format($complaint),
        ]);
    }

    private function format(Complaint $c): array
    {
        return [
            'id'             => $c->id,
            'title'          => $c->title,
            'description'    => $c->description,
            'status'         => $c->status,
            'priority'       => $c->priority,
            'phone_number'   => $c->phone_number,
            'admin_response' => $c->admin_response,
            'patient'        => [
                'id'    => $c->patient_id,
                'name'  => $c->patient?->user?->name,
                'phone' => $c->patient?->user?->phone,
            ],
            'submitted_by'   => [
                'id'   => $c->submitted_by,
                'name' => $c->submittedBy?->name,
            ],
            'created_at'     => $c->created_at?->toDateTimeString(),
            'updated_at'     => $c->updated_at?->toDateTimeString(),
        ];
    }
}
