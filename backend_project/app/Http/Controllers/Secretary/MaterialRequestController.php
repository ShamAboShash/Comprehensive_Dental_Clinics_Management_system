<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\StoreMaterialRequestRequest;
use App\Http\Requests\Secretary\UpdateMaterialRequestRequest;
use App\Http\Resources\MaterialRequestResource;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\NewMaterialRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MaterialRequestController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-secretary');
    }

    /**
     * GET /secretary/showAllMaterialRequests
     */
    public function index(): JsonResponse
    {
        $requests = MaterialRequest::with(['items.material', 'newItems', 'requestedBy', 'clinic'])
            ->where('requested_by', request()->user()->id)
            ->where('requester_type', 'clinic')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $requests->count(),
            'data'    => MaterialRequestResource::collection($requests),
        ]);
    }

    /**
     * GET /secretary/showMaterialRequest/{id}
     */
    public function show(int $id): JsonResponse
    {
        $materialRequest = MaterialRequest::with(['items.material', 'newItems', 'requestedBy', 'clinic'])
            ->where('requested_by', request()->user()->id)
            ->where('requester_type', 'clinic')
            ->find($id);

        if (!$materialRequest) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new MaterialRequestResource($materialRequest),
        ]);
    }

    /**
     * POST /secretary/addMaterialRequest
     */
    public function store(StoreMaterialRequestRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $materialRequest = MaterialRequest::create([
                'requested_by'   => $request->user()->id,
                'requester_type' => 'clinic',
                'clinic_id'      => $request->clinic_id,
                'notes'          => $request->notes,
                'status'         => 'new',
            ]);

            if ($request->filled('items')) {
                foreach ($request->items as $item) {
                    MaterialRequestItem::create([
                        'material_request_id' => $materialRequest->id,
                        'material_id'         => $item['material_id'],
                        'quantity_requested'  => $item['quantity_requested'],
                        'notes'               => $item['notes'] ?? null,
                    ]);
                }
            }

            if ($request->filled('new_items')) {
                foreach ($request->new_items as $item) {
                    NewMaterialRequest::create([
                        'material_request_id' => $materialRequest->id,
                        'material_name'       => $item['material_name'],
                        'quantity'            => $item['quantity'],
                        'unit'                => $item['unit'] ?? null,
                        'company_name'        => $item['company_name'] ?? null,
                        'dosage'              => $item['dosage'] ?? null,
                        'status'              => 'new',
                    ]);
                }
            }

            DB::commit();

            $materialRequest->load(['items.material', 'newItems', 'requestedBy', 'clinic']);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال طلب المواد بنجاح.',
                'data'    => new MaterialRequestResource($materialRequest),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الطلب.',
            ], 500);
        }
    }

    /**
     * POST /secretary/updateMaterialRequest/{id}
     */
    public function update(UpdateMaterialRequestRequest $request, int $id): JsonResponse
    {
        $materialRequest = MaterialRequest::where('requested_by', request()->user()->id)
            ->where('requester_type', 'clinic')
            ->find($id);

        if (!$materialRequest) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود.',
            ], 404);
        }

        if ($materialRequest->status !== 'new') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل الطلب بعد بدء معالجته.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $materialRequest->update([
                'notes'     => $request->has('notes') ? $request->notes : $materialRequest->notes,
                'clinic_id' => $request->has('clinic_id') ? $request->clinic_id : $materialRequest->clinic_id,
            ]);

            if ($request->has('items')) {
                $materialRequest->items()->delete();

                foreach ($request->items ?? [] as $item) {
                    MaterialRequestItem::create([
                        'material_request_id' => $materialRequest->id,
                        'material_id'         => $item['material_id'],
                        'quantity_requested'  => $item['quantity_requested'],
                        'notes'               => $item['notes'] ?? null,
                    ]);
                }
            }

            if ($request->has('new_items')) {
                $materialRequest->newItems()->delete();

                foreach ($request->new_items ?? [] as $item) {
                    NewMaterialRequest::create([
                        'material_request_id' => $materialRequest->id,
                        'material_name'       => $item['material_name'],
                        'quantity'            => $item['quantity'],
                        'unit'                => $item['unit'] ?? null,
                        'company_name'        => $item['company_name'] ?? null,
                        'dosage'              => $item['dosage'] ?? null,
                        'status'              => 'new',
                    ]);
                }
            }

            DB::commit();

            $materialRequest->load(['items.material', 'newItems', 'requestedBy', 'clinic']);

            return response()->json([
                'success' => true,
                'message' => 'تم تعديل الطلب بنجاح.',
                'data'    => new MaterialRequestResource($materialRequest),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تعديل الطلب.',
            ], 500);
        }
    }

    /**
     * POST /secretary/deleteMaterialRequest/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $materialRequest = MaterialRequest::where('requested_by', request()->user()->id)
            ->where('requester_type', 'clinic')
            ->find($id);

        if (!$materialRequest) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود.',
            ], 404);
        }

        if ($materialRequest->status !== 'new') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء الطلب بعد معالجته.',
            ], 422);
        }

        $materialRequest->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الطلب بنجاح.',
        ]);
    }

    /**
     * POST /secretary/updateMaterialRequestStatus/{id}
     */
    public function updateStatus(int $id): JsonResponse
    {
        $materialRequest = MaterialRequest::where('requested_by', request()->user()->id)
            ->where('requester_type', 'clinic')
            ->find($id);

        if (!$materialRequest) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود.',
            ], 404);
        }

        if ($materialRequest->status !== 'new') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تغيير حالة الطلب بعد بدء معالجته من المستودع.',
            ], 422);
        }

        $materialRequest->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الطلب بنجاح.',
            'data'    => [
                'id'     => $materialRequest->id,
                'status' => $materialRequest->status,
            ],
        ]);
    }
}