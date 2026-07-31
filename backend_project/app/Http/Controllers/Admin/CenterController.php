<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseMaterialResource;
use App\Http\Resources\ComplaintResource;
use App\Models\CenterInfo;
use App\Models\WarehouseStock;
use App\Models\Complaint;
use App\Models\Clinic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CenterController extends Controller
{
// public function showCenterInfo()
//     {
//         Gate::authorize('is-admin');

//         $centerInfo = CenterInfo::first();

//         return response()->json([
//             'success' => true,
//             'data'    => [
//                 'id'              => $centerInfo->id,
//             'name'            => $centerInfo->name,
//             'description'     => $centerInfo->description,
//             'phone'           => $centerInfo->phone,
//             'email'           => $centerInfo->email,
//             'facebook_url'    => $centerInfo->facebook_url,
//             'instagram_url'   => $centerInfo->instagram_url,
//             'whatsapp_number' => $centerInfo->whatsapp_number,
//             'logo_url'        => $centerInfo->logo_path
//                 ? asset(Storage::url($centerInfo->logo_path))
//                 : null,
//             ],
//         ]);
//     }

    public function updateCenterInfo(Request $request)
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'name'            => 'sometimes|required|string|max:255',
            'description'     => 'nullable|string',
            'phone'           => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
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
            'data'    => [
            'id'              => $centerInfo->id,
            'name'            => $centerInfo->name,
            'description'     => $centerInfo->description,
            'phone'           => $centerInfo->phone,
            'email'           => $centerInfo->email,
            'facebook_url'    => $centerInfo->facebook_url,
            'instagram_url'   => $centerInfo->instagram_url,
            'whatsapp_number' => $centerInfo->whatsapp_number,
            'logo_url'        => $centerInfo->logo_path
                ? asset(Storage::url($centerInfo->logo_path))
                : null,
            ],
        ]);
    }

    public function getWarehouseInventory(): JsonResponse
    {
        Gate::authorize('is-admin');

        $stocks = WarehouseStock::with('material')->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'count'   => $stocks->count(),
            'data'    => WarehouseMaterialResource::collection($stocks),
        ]);
    }

    public function ClinicIndex(): JsonResponse
    {
        Gate::authorize('is-admin');

        $clinics = Clinic::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'count'   => $clinics->count(),
            'data'    => $clinics->map(fn ($clinic) => [
                'id'          => $clinic->id,
                'name'        => $clinic->name,
                'room_number' => $clinic->room_number,
                'is_active'   => (bool) $clinic->is_active,
            ])->values(),
        ]);
    }

    public function clinicStore(Request $request): JsonResponse
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'room_number' => 'required|string|max:50|unique:clinics,room_number',
            'is_active'   => 'sometimes|boolean',
        ]);

        $clinic = Clinic::create([
            'name'        => $validated['name'],
            'room_number' => $validated['room_number'],
            'is_active'   => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة العيادة بنجاح',
            'data'    => [
            'id'          => $clinic->id,
            'name'        => $clinic->name,
            'room_number' => $clinic->room_number,
            'is_active'   => $clinic->is_active,
        ],
        ], 201);
    }

    public function clinicUpdate(Request $request, Clinic $clinic): JsonResponse
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'room_number' => 'sometimes|required|string|max:50|unique:clinics,room_number,' . $clinic->id,
            'is_active'   => 'sometimes|boolean',
        ]);

        $clinic->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل معلومات العيادة بنجاح',
            'data'    => [
                'id'          => $clinic->id,
                'name'        => $clinic->name,
                'room_number' => $clinic->room_number,
                'is_active'   => $clinic->is_active,
            ],
        ]);
    }

    public function complaintIndex(Request $request): JsonResponse
    {
        Gate::authorize('is-admin');

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

}
