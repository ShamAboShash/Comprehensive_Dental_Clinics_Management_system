<?php

namespace App\Http\Controllers\LabManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\LabManager\StoreMaterialRequestRequest;
use App\Http\Resources\MaterialRequestResource;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\NewMaterialRequest;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class MaterialRequestController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-lab-manager');
    }

    // GET /lab/material-requests
    public function index()
    {
        $requests = MaterialRequest::with(['items.material', 'newItems'])
            ->where('requested_by', request()->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => MaterialRequestResource::collection($requests),
        ]);
    }

    // GET /lab/material-requests/{id}
    public function show($id)
    {
        $materialRequest = MaterialRequest::with([
            'items.material',
            'newItems',
        ])
        ->where('requested_by', request()->user()->id)
        ->find($id);

        if (!$materialRequest) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new MaterialRequestResource($materialRequest),
        ]);
    }

    // POST /lab/material-requests
    public function store(StoreMaterialRequestRequest $request)
    {
        DB::beginTransaction();

        try {
            $materialRequest = MaterialRequest::create([
                'requested_by'   => $request->user()->id,
                'requester_type' => 'lab',
                'notes'          => $request->notes,
                'status'         => 'pending',
            ]);

            // Existing materials
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

            // New materials
            if ($request->filled('new_items')) {
                foreach ($request->new_items as $item) {
                    NewMaterialRequest::create([
                        'material_request_id' => $materialRequest->id,
                        'material_name'       => $item['material_name'],
                        'quantity'            => $item['quantity'],
                        'unit'                => $item['unit'] ?? null,
                        'company_name'        => $item['company_name'] ?? null,
                        'reason'              => $item['reason'] ?? null,
                    ]);
                }
            }

            // Notify warehouse managers
            $warehouseManagers = User::where('role', UserRole::WAREHOUSE_MANAGER)
                ->where('is_active', true)
                ->get();

            // foreach ($warehouseManagers as $manager) {
            //     NotificationService::send(
            //         user: $manager,
            //         title: 'طلب مواد جديد من المختبر',
            //         body: "أرسل مدير المختبر طلب مواد جديد #{$materialRequest->id}",
            //         type: 'material_request_created',
            //         referenceable: $materialRequest,
            //     );
            // }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال طلب المواد بنجاح',
                'data'    => new MaterialRequestResource(
                    $materialRequest->load(['items.material', 'newItems'])
                ),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الطلب',
            ], 500);
        }
    }

    // DELETE /lab/material-requests/{id}
    public function destroy($id)
    {
        $materialRequest = MaterialRequest::where('requested_by', request()->user()->id)
            ->find($id);

        if (!$materialRequest) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        if ($materialRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء الطلب بعد معالجته',
            ], 422);
        }

        $materialRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الطلب بنجاح',
        ]);
    }
}
