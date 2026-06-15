<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\FulfillMaterialRequestRequest;
use App\Http\Requests\Warehouse\RejectMaterialRequestRequest;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockLog;
use App\Models\WarehouseFulfillment;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class MaterialRequestController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-warehouse-manager');
    }

    // GET /warehouse/material-requests
    public function index()
    {
        $requests = MaterialRequest::with(['items.material', 'newItems', 'requester'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $requests,
        ]);
    }

    // GET /warehouse/material-requests/{id}
    public function show($id)
    {
        $materialRequest = MaterialRequest::with([
            'items.material',
            'newItems',
            'requester',
            'fulfillments',
        ])->find($id);

        if (!$materialRequest) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $materialRequest,
        ]);
    }

    // PATCH /warehouse/material-requests/{id}/fulfill
  public function fulfill(FulfillMaterialRequestRequest $request, $id)
{
    $materialRequest = MaterialRequest::with('items.material')->find($id);

    if (!$materialRequest) {
        return response()->json([
            'success' => false,
            'message' => 'الطلب غير موجود',
        ], 404);
    }

    // FIX 1: allow both pending and processing statuses
    if (!in_array($materialRequest->status, ['pending', 'processing'])) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن تلبية طلب تمت معالجته مسبقاً',
        ], 422);
    }

    DB::beginTransaction();

    try {
        foreach ($request->items as $item) {
            $requestItem = MaterialRequestItem::find($item['material_request_item_id']);

            // FIX 2: FIFO batch selection — oldest non-expired batch first
            $stock = WarehouseStock::where('material_id', $requestItem->material_id)
                ->where('quantity', '>', 0)
                ->where(fn($q) => $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', now()->toDateString()))
                ->orderBy('expiration_date', 'asc')
                ->first();

            // FIX 3: use quantity_given instead of quantity_fulfilled
            if (!$stock || $stock->quantity < $item['quantity_given']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "الكمية المتوفرة غير كافية للمادة: {$requestItem->material->name}",
                ], 422);
            }

            // Deduct from warehouse stock
            $stock->decrement('quantity', $item['quantity_given']);

            // Log the movement
            WarehouseStockLog::create([
                'warehouse_stock_id' => $stock->id,
                'quantity'           => -$item['quantity_given'],
                'reason'             => "تلبية طلب مواد #{$materialRequest->id}",
            ]);

            // FIX 4: use correct WarehouseFulfillment fields
            WarehouseFulfillment::create([
                'material_request_item_id' => $requestItem->id,
                'warehouse_stock_id'       => $stock->id,
                'quantity_given'           => $item['quantity_given'],
                'fulfilled_by'             => $request->user()->id,
                'status'                   => 'fulfilled',
            ]);

            // FIX 5: update individual item status
            $requestItem->update(['status' => 'fulfilled']);
        }

        // FIX 6: determine final status based on how many items got fulfilled
        $allItems       = $materialRequest->items()->get();
        $fulfilledCount = $allItems->where('status', 'fulfilled')->count();

        $finalStatus = match(true) {
            $fulfilledCount === $allItems->count() => 'fulfilled',
            $fulfilledCount > 0                    => 'partially_fulfilled',
            default                                => 'not_available',
        };

        $materialRequest->update([
            'status' => $finalStatus,
            'notes'  => $request->notes ?? $materialRequest->notes,
        ]);

        // Notify the lab manager
        NotificationService::send(
            user: $materialRequest->requester,
            title: 'تم تلبية طلب المواد',
            body: "تم تلبية طلب المواد #{$materialRequest->id} من المستودع",
            type: 'material_request_fulfilled',
            referenceable: $materialRequest,
        );

        DB::commit();

        // FIX 7: use items.fulfillments not top-level fulfillments
        return response()->json([
            'success' => true,
            'message' => 'تم تلبية الطلب بنجاح',
            'data'    => $materialRequest->fresh(['items.material', 'items.fulfillments']),
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تلبية الطلب',
        ], 500);
    }
}

    // PATCH /warehouse/material-requests/{id}/reject
    public function reject(RejectMaterialRequestRequest $request, $id)
    {
        $materialRequest = MaterialRequest::find($id);

        if (!$materialRequest) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        if ($materialRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن رفض طلب تمت معالجته مسبقاً',
            ], 422);
        }

        $materialRequest->update([
            'status' => 'rejected',
            'notes'  => $request->reason,
        ]);

        // Notify the lab manager
        NotificationService::send(
            user: $materialRequest->requester,
            title: 'تم رفض طلب المواد',
            body: "تم رفض طلب المواد #{$materialRequest->id} — السبب: {$request->reason}",
            type: 'material_request_rejected',
            referenceable: $materialRequest,
        );

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الطلب',
            'data'    => $materialRequest->fresh(),
        ]);
    }
}
