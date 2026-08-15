<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseMaterialResource;
use App\Http\Resources\ComplaintResource;
use App\Models\CenterInfo;
use App\Models\WarehouseStock;
use App\Models\Complaint;
use App\Models\Clinic;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CenterController extends Controller
{
// public function showCenterInfo()
//     {
//         Gate::authorize('is-admin');

//         $centerInfo = CenterInfo::first();

//         if (!$centerInfo) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'معلومات المركز غير موجودة',
//             ], 404);
//         }

//         return response()->json([
//             'success' => true,
//             'data'    => $this->formatCenterInfo($centerInfo),
//         ]);
//     }

    // PATCH/PUT center info
    public function updateCenterInfo(Request $request)
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'name'            => 'sometimes|required|string|max:255',
            'description'     => 'nullable|string',
            'phone'           => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
            'address'         => 'nullable|string|max:500',
            'work_start_time' => 'nullable|date_format:H:i,H:i:s',
            'work_end_time'   => 'nullable|date_format:H:i,H:i:s|after:work_start_time',
            'facebook_url'    => 'nullable|url|max:255',
            'instagram_url'   => 'nullable|url|max:255',
            'whatsapp_number' => 'nullable|string|max:50',
            'logo'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $centerInfo = CenterInfo::first() ?? new CenterInfo();

        if ($request->hasFile('logo')) {
            if ($centerInfo->logo_path && Storage::disk('public')->exists($centerInfo->logo_path)) {
                Storage::disk('public')->delete($centerInfo->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('center_info', 'public');
        }

        unset($validated['logo']);
        $centerInfo->fill($validated)->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث معلومات المركز بنجاح',
            'data'    => $this->formatCenterInfo($centerInfo),
        ]);
    }

    // public function getWarehouseInventory(): JsonResponse
    // {
    //     Gate::authorize('is-admin');

    //     $stocks = WarehouseStock::with('material')->orderByDesc('id')->get();

    //     return response()->json([
    //         'success' => true,
    //         'count'   => $stocks->count(),
    //         'data'    => WarehouseMaterialResource::collection($stocks),
    //     ]);
    // }

    // GET all clinics
    public function ClinicIndex(): JsonResponse
    {
        Gate::authorize('is-admin');

        $clinics = Clinic::with('specialization')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'count'   => $clinics->count(),
            'data'    => $clinics->map(fn ($clinic) => $this->formatClinic($clinic))->values(),
        ]);
    }

    // POST create clinic
    public function clinicStore(Request $request): JsonResponse
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'room_number'       => 'required|string|max:50|unique:clinics,room_number',
            'floor'             => 'nullable|string|max:50',
            'equipments'        => 'nullable|string',
            'notes'             => 'nullable|string',
            'specialization_id' => 'nullable|exists:specializations,id',
            'is_active'         => 'sometimes|boolean',
        ]);

        $clinic = Clinic::create([
            'name'              => $validated['name'],
            'room_number'       => $validated['room_number'],
            'floor'             => $validated['floor'] ?? null,
            'equipments'        => $validated['equipments'] ?? null,
            'notes'             => $validated['notes'] ?? null,
            'specialization_id' => $validated['specialization_id'] ?? null,
            'is_active'         => $validated['is_active'] ?? true,
        ]);

        $clinic->load('specialization');

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة العيادة بنجاح',
            'data'    => $this->formatClinic($clinic),
        ], 201);
    }

    // PUT/PATCH update clinic
    public function clinicUpdate(Request $request, Clinic $clinic): JsonResponse
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'name'              => 'sometimes|required|string|max:255',
            'room_number'       => 'sometimes|required|string|max:50|unique:clinics,room_number,' . $clinic->id,
            'floor'             => 'nullable|string|max:50',
            'equipments'        => 'nullable|string',
            'notes'             => 'nullable|string',
            'specialization_id' => 'nullable|exists:specializations,id',
            'is_active'         => 'sometimes|boolean',
        ]);

        $clinic->update($validated);
        $clinic->load('specialization');

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل معلومات العيادة بنجاح',
            'data'    => $this->formatClinic($clinic),
        ]);
    }


    public function clinicShow(int $id): JsonResponse
{
    Gate::authorize('is-admin');

    $clinic = Clinic::with('specialization')->find($id);

    if (!$clinic) {
        return response()->json([
            'success' => false,
            'message' => 'العيادة غير موجودة',
        ], 404);
    }

    // Dentists linked to this clinic
    $dentists = $clinic->dentists()
        ->with(['specialization', 'employee.user'])
        ->get();

    $dentistIds = $dentists->pluck('id');

    $dentistsData = $dentists->map(function ($dentist) {
        $user = $dentist->employee?->user;

        return [
            'id'             => $dentist->id,
            'name'           => $user?->name,
            'phone'          => $user?->phone,
            'specialization' => $dentist->specialization ? [
                'id'      => $dentist->specialization->id,
                'name'    => $dentist->specialization->name,
                'name_en' => $dentist->specialization->name_en ?? null,
            ] : null,
        ];
    })->values();

    // Today's appointments for dentists of this clinic
    // (appointments have no clinic_id — linked via dentist_clinics)
    $today = Carbon::today();

    $appointments = Appointment::with([
            'patient.user:id,name,phone',
            'dentist.employee.user:id,name',
            'appointmentType:id,name,name_en',
        ])
        ->whereIn('dentist_id', $dentistIds)
        ->whereDate('appointment_time', $today)
        ->orderBy('appointment_time')
        ->get()
        ->map(function ($apt) {
            return [
                'id'               => $apt->id,
                'appointment_time' => $apt->appointment_time?->format('Y-m-d H:i'),
                'status'           => $apt->status,
                'type'             => $apt->type,
                'patient'          => $apt->patient?->user ? [
                    'id'    => $apt->patient->id,
                    'name'  => $apt->patient->user->name,
                ] : null,
                'dentist'          => [
                    'id'   => $apt->dentist_id,
                    'name' => $apt->dentist?->employee?->user?->name,
                ],
                'appointment_type' => $apt->appointmentType ? [
                    'id'   => $apt->appointmentType->id,
                    'name' => $apt->appointmentType->name,
                ] : null,
            ];
        })->values();

    return response()->json([
        'success' => true,
        'data'    => [
            'clinic'             => $this->formatClinic($clinic),
            'dentists'           => $dentistsData,
            'today_appointments' => $appointments,
            'date'               => $today->toDateString(),
        ],
    ]);
}

// In CenterController (admin)

public function updateWorkHours(Request $request)
{
    Gate::authorize('is-admin'); // or view-admin-profile if is-admin is not defined

    $validated = $request->validate([
        'work_start_time' => 'required|date_format:H:i,H:i:s',
        'work_end_time'   => 'required|date_format:H:i,H:i:s|after:work_start_time',
    ]);

    $centerInfo = CenterInfo::first();

    if (!$centerInfo) {
        return response()->json([
            'success' => false,
            'message' => 'معلومات المركز غير موجودة',
        ], 404);
    }

    $centerInfo->update([
        'work_start_time' => $validated['work_start_time'],
        'work_end_time'   => $validated['work_end_time'],
    ]);

    return response()->json([
        'success' => true,
        'message' => 'تم تحديث أوقات العمل بنجاح',
        'data'    => [
            'work_start_time' => substr((string) $centerInfo->work_start_time, 0, 5),
            'work_end_time'   => substr((string) $centerInfo->work_end_time, 0, 5),
        ],
    ]);
}

    public function complaintIndex(Request $request): JsonResponse
    {
        Gate::authorize('is-admin-secretary');

        $validated = $request->validate([
            'status'   => 'nullable|string|in:pending,in_review,resolved,rejected',
            'priority' => 'nullable|string|in:low,medium,high',
        ]);

        $query = Complaint::with(['patient.user:id,name,email,phone', 'submittedBy:id,name,email,phone,role']);

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (!empty($validated['priority'])) {
            $query->where('priority', $validated['priority']);
        }

        $items = $query->latest()->get();

        return response()->json([
            'success'          => true,
            'total_complaints' => $items->count(),
            'data'             => ComplaintResource::collection($items),
        ]);
    }

    public function complaintUpdate(Request $request, Complaint $complaint): JsonResponse
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'status'         => 'sometimes|required|string|in:pending,in_review,resolved,rejected',
            'admin_response' => 'nullable|string',
        ]);

        $complaint->update($validated);
        $complaint->load(['patient.user:id,name,email,phone', 'submittedBy:id,name,email,phone,role']);

        $message = $validated['status'] === 'resolved'
        ? 'تم حل الشكوى بنجاح'
        : 'تم رفض الشكوى';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => new ComplaintResource($complaint),
        ]);
        }


        private function formatCenterInfo(CenterInfo $centerInfo): array
    {
        return [
            'id'              => $centerInfo->id,
            'name'            => $centerInfo->name,
            'description'     => $centerInfo->description,
            'phone'           => $centerInfo->phone,
            'email'           => $centerInfo->email,
            'address'         => $centerInfo->address,
            'work_start_time' => $centerInfo->work_start_time
                ? substr((string) $centerInfo->work_start_time, 0, 5) // HH:MM
                : null,
            'work_end_time'   => $centerInfo->work_end_time
                ? substr((string) $centerInfo->work_end_time, 0, 5)
                : null,
            'facebook_url'    => $centerInfo->facebook_url,
            'instagram_url'   => $centerInfo->instagram_url,
            'whatsapp_number' => $centerInfo->whatsapp_number,

        ];
    }

    private function formatClinic(Clinic $clinic): array
    {
        return [
            'id'                => $clinic->id,
            'name'              => $clinic->name,
            'room_number'       => $clinic->room_number,
            'floor'             => $clinic->floor,
            'equipments'        => $clinic->equipments,
            'notes'             => $clinic->notes,
            'specialization_id' => $clinic->specialization_id,
            'specialization'    => $clinic->specialization ? [
                'id'      => $clinic->specialization->id,
                'name'    => $clinic->specialization->name,
                'name_en' => $clinic->specialization->name_en ?? null,
            ] : null,
            'is_active'         => (bool) $clinic->is_active,
        ];
    }
}
