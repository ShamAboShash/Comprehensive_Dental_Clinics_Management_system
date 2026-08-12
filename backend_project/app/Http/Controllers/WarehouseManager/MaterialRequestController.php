<?php

namespace App\Http\Controllers\WarehouseManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseManager\FulfillMaterialRequestRequest;
use App\Http\Requests\WarehouseManager\RejectMaterialRequestRequest;
use App\Http\Resources\MaterialRequestResource;
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
        $requests = MaterialRequest::with([
            'requestedBy',
            'clinic',
            'items.material',
            'items.fulfillment.fulfilledBy',
            'newItems',
        ])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => MaterialRequestResource::collection($requests),
        ]);
    }

    // GET /warehouse/material-requests/{id}
    public function show($id)
    {
        $materialRequest = MaterialRequest::with([
            'items.material',
            'newItems',
            'requestedBy',
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
            'data'    => new MaterialRequestResource($materialRequest),
        ]);
    }

    // PATCH /warehouse/material-requests/{id}/fulfill
//   public function fulfill(FulfillMaterialRequestRequest $request, $id)
// {
//     $materialRequest = MaterialRequest::with('items.material')->find($id);

//     if (!$materialRequest) {
//         return response()->json([
//             'success' => false,
//             'message' => 'الطلب غير موجود',
//         ], 404);
//     }

//     // FIX 1: allow both pending and processing statuses
//     if (!in_array($materialRequest->status, ['pending', 'processing'])) {
//         return response()->json([
//             'success' => false,
//             'message' => 'لا يمكن تلبية طلب تمت معالجته مسبقاً',
//         ], 422);
//     }

//     DB::beginTransaction();

//     try {
//         foreach ($request->items as $item) {
//             $requestItem = MaterialRequestItem::find($item['material_request_item_id']);

//             // FIX 2: FIFO batch selection — oldest non-expired batch first
//             $stock = WarehouseStock::where('material_id', $requestItem->material_id)
//                 ->where('quantity', '>', 0)
//                 ->where(fn($q) => $q->whereNull('expiration_date')
//                     ->orWhere('expiration_date', '>=', now()->toDateString()))
//                 ->orderBy('expiration_date', 'asc')
//                 ->first();

//             // FIX 3: use quantity_given instead of quantity_fulfilled
//             if (!$stock || $stock->quantity < $item['quantity_given']) {
//                 DB::rollBack();
//                 return response()->json([
//                     'success' => false,
//                     'message' => "الكمية المتوفرة غير كافية للمادة: {$requestItem->material->name}",
//                 ], 422);
//             }

//             // Deduct from warehouse stock
//             $stock->decrement('quantity', $item['quantity_given']);

//             // Log the movement
//             WarehouseStockLog::create([
//                 'material_id'        => $requestItem->material_id,
//                 'warehouse_stock_id' => $stock->id,
//                 'type'               => 'out',
//                 'quantity'           => $item['quantity_given'],   // positive number
//                 'reason'             => 'fulfillment',              // must be enum value
//                 'notes'              => "تلبية طلب مواد #{$materialRequest->id}",
//                 'done_by'            => $request->user()->id,
//             ]);

//             // FIX 4: use correct WarehouseFulfillment fields
//             WarehouseFulfillment::create([
//                 'material_request_item_id' => $requestItem->id,
//                 'warehouse_stock_id'       => $stock->id,
//                 'quantity_given'           => $item['quantity_given'],
//                 'fulfilled_by'             => $request->user()->id,
//                 'status'                   => 'fulfilled',
//             ]);

//             // FIX 5: update individual item status
//             $requestItem->update(['status' => 'fulfilled']);
//         }

//         // FIX 6: determine final status based on how many items got fulfilled
//         $allItems       = $materialRequest->items()->get();
//         $fulfilledCount = $allItems->where('status', 'fulfilled')->count();

//         $finalStatus = match(true) {
//             $fulfilledCount === $allItems->count() => 'fulfilled',
//             $fulfilledCount > 0                    => 'partially_fulfilled',
//             default                                => 'not_available',
//         };

//         $materialRequest->update([
//             'status' => $finalStatus,
//             'notes'  => $request->notes ?? $materialRequest->notes,
//         ]);

//         // Notify the lab manager
//         // NotificationService::send(
//         //     user: $materialRequest->requestedBy,
//         //     title: 'تم تلبية طلب المواد',
//         //     body: "تم تلبية طلب المواد #{$materialRequest->id} من المستودع",
//         //     type: 'material_request_fulfilled',
//         //     referenceable: $materialRequest,
//         // );

//         DB::commit();

//         // FIX 7: use items.fulfillments not top-level fulfillments
//         return response()->json([
//             'success' => true,
//             'message' => 'تم تلبية الطلب بنجاح',
//             'data'    => $materialRequest->fresh(['items.material', 'items.fulfillments']),
//         ]);

//     } catch (\Exception $e) {
//         DB::rollBack();

//         return response()->json([
//             'success' => false,
//             'message' => 'حدث خطأ أثناء تلبية الطلب',
//         ], 500);
//     }
// }


public function fulfill(FulfillMaterialRequestRequest $request, $id)
{
    $materialRequest = MaterialRequest::with('items.material')->find($id);

    if (!$materialRequest) {
        return response()->json([
            'success' => false,
            'message' => 'الطلب غير موجود',
        ], 404);
    }

    // Only new or in_progress can be fulfilled
    if (!in_array($materialRequest->status, ['new'])) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن تلبية طلب تمت معالجته مسبقاًاو تم رفضه',
        ], 422);
    }

    if ($materialRequest->items->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'الطلب لا يحتوي على مواد للتلبية',
        ], 422);
    }

    DB::beginTransaction();

    try {
        foreach ($materialRequest->items as $requestItem) {

            // Skip already fulfilled items
            if ($requestItem->status === 'fulfilled') {
                continue;
            }

            $remaining = $requestItem->quantity_requested;

            // FIFO: oldest non-expired batch first
            $batches = WarehouseStock::where('material_id', $requestItem->material_id)
                ->where('quantity', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('expiration_date')
                      ->orWhere('expiration_date', '>=', now()->toDateString());
                })
                ->orderByRaw('expiration_date IS NULL ASC') // nulls last
                ->orderBy('expiration_date', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $totalAvailable = $batches->sum('quantity');

            if ($totalAvailable < $remaining) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "الكمية غير كافية للمادة: " . ($requestItem->material->name ?? '#' . $requestItem->material_id)
                        . " (المطلوب: {$remaining}, المتوفر: {$totalAvailable})",
                ], 422);
            }

            // Deduct from batches until quantity is covered
            foreach ($batches as $stock) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($stock->quantity, $remaining);

                $stock->decrement('quantity', $take);

                // Stock log
                WarehouseStockLog::create([
                    'material_id'        => $requestItem->material_id,
                    'warehouse_stock_id' => $stock->id,
                    'type'               => 'out',
                    'quantity'           => $take,
                    'reason'             => 'fulfillment',
                    'notes'              => "تلبية طلب مواد #{$materialRequest->id}",
                    'done_by'            => $request->user()->id,
                ]);

                // Fulfillment record
                WarehouseFulfillment::create([
                    'material_request_item_id' => $requestItem->id,
                    'warehouse_stock_id'       => $stock->id,
                    'quantity_given'           => $take,
                    'fulfilled_by'             => $request->user()->id,
                    'status'                   => 'fulfilled',
                ]);

                $remaining -= $take;
            }

            // Mark item as fulfilled
            $requestItem->update(['status' => 'fulfilled']);
        }

        // Mark whole request as completed
        $materialRequest->update([
            'status'          => 'completed',
            'warehouse_notes' => $request->notes ?? $materialRequest->warehouse_notes,
        ]);

        DB::commit();

        $materialRequest->load([
            'requestedBy',
            'clinic',
            'items.material',
            'items.fulfillment.fulfilledBy',
            'newItems',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تلبية الطلب بالكامل بنجاح',
            'data'    => new MaterialRequestResource($materialRequest),
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تلبية الطلب',
            // 'error' => $e->getMessage(), // enable while debugging
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

        if ($materialRequest->status !== 'new') {
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
        // NotificationService::send(
        //     user: $materialRequest->requestedBy,
        //     title: 'تم رفض طلب المواد',
        //     body: "تم رفض طلب المواد #{$materialRequest->id} — السبب: {$request->reason}",
        //     type: 'material_request_rejected',
        //     referenceable: $materialRequest,
        // );

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الطلب',
            'data'    => $materialRequest->fresh(),
        ]);
    }
}
