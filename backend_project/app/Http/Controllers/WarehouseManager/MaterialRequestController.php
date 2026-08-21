<?php

namespace App\Http\Controllers\WarehouseManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseManager\FulfillMaterialRequestRequest;
use App\Http\Requests\WarehouseManager\RejectMaterialRequestRequest;
use App\Http\Resources\MaterialRequestResource;
use App\Models\MaterialRequest;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockLog;
use App\Models\WarehouseFulfillment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;
use App\Models\User;
use App\Enums\UserRole;

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
            'items.fulfillment.fulfilledBy',
            'newItems',
            'requestedBy',
            'clinic',
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

    /**
     * PATCH /warehouse/material-requests/{id}/fulfill
     *
     * Completes the WHOLE request at once (no partial / no item-level status).
     * Allowed from: new, pending
     * Final status: completed
     */
public function fulfill(FulfillMaterialRequestRequest $request, $id)
{
    $materialRequest = MaterialRequest::with('items.material')->find($id);

    if (!$materialRequest) {
        return response()->json([
            'success' => false,
            'message' => 'الطلب غير موجود',
        ], 404);
    }

    // Only new or pending can be fulfilled
    if (!in_array($materialRequest->status, ['new', 'pending'], true)) {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن تلبية طلب تمت معالجته مسبقاً أو تم رفضه أو إلغاؤه',
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
        $lowStockMaterials = [];   // ← 1. Initialize here

        foreach ($materialRequest->items as $requestItem) {
            $remaining = $requestItem->quantity_requested;

            // FIFO: oldest non-expired batch first
            $batches = WarehouseStock::where('material_id', $requestItem->material_id)
                ->where('quantity', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('expiration_date')
                      ->orWhere('expiration_date', '>=', now()->toDateString());
                })
                ->orderByRaw('expiration_date IS NULL ASC')
                ->orderBy('expiration_date', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $totalAvailable = $batches->sum('quantity');

            if ($totalAvailable < $remaining) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'الكمية غير كافية للمادة: '
                        . ($requestItem->material->name ?? '#' . $requestItem->material_id)
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

                // Fulfillment record (per batch)
                WarehouseFulfillment::create([
                    'material_request_item_id' => $requestItem->id,
                    'warehouse_stock_id'       => $stock->id,
                    'quantity_given'           => $take,
                    'fulfilled_by'             => $request->user()->id,
                    'status'                   => 'fulfilled',
                ]);

                $remaining -= $take;

                // ← 2. Check low stock AFTER decrement
                $stock->refresh();
                if ($stock->quantity <= 10) {   // threshold (same as Admin InventoryController)
                    // avoid duplicates if same material appears multiple times
                    $lowStockMaterials[$stock->material_id] = $stock->load('material');
                }
            }
        }

        // Mark whole request as completed
        $materialRequest->update([
            'status'          => 'completed',
            'warehouse_notes' => $request->notes ?? $materialRequest->warehouse_notes,
        ]);

        DB::commit();

        // ← 3. Send notifications AFTER successful commit
        if (!empty($lowStockMaterials)) {
            $admins = User::where('role', UserRole::ADMIN)
                ->where('is_active', true)
                ->get();

            foreach ($lowStockMaterials as $stock) {
                $materialName = $stock->material?->name ?? 'مادة';

                foreach ($admins as $admin) {
                    NotificationService::send(
                        user: $admin,
                        title: 'تنبيه: نقص في مواد المستودع',
                        body: "المادة ({$materialName}) وصلت لحد التنبيه في المستودع (الكمية الحالية: {$stock->quantity})",
                        type: 'warehouse_low_stock',
                        referenceable: $stock
                    );
                }
            }
        }

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
        ], 500);
    }
}

    /**
     * PATCH /warehouse/material-requests/{id}/reject
     *
     * Allowed from: new, pending
     * Final status: rejected
     */
    public function reject(RejectMaterialRequestRequest $request, $id)
    {
        $materialRequest = MaterialRequest::find($id);

        if (!$materialRequest) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        if (!in_array($materialRequest->status, ['new', 'pending'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن رفض طلب تمت معالجته مسبقاً',
            ], 422);
        }

        $materialRequest->update([
            'status'          => 'rejected',
            'warehouse_notes' => $request->reason ?? $materialRequest->warehouse_notes,
        ]);

        // NotificationService::send(...); // when ready

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الطلب',
            'data'    => $materialRequest->fresh([
                'requestedBy',
                'clinic',
                'items.material',
                'newItems',
            ]),
        ]);
    }


    /**
 * PATCH /warehouse/material-requests/{id}/pending
 *
 * Move request from new → pending (warehouse started working on it).
 * Allowed from: new only
 * Final status: pending
 */
    public function markPending($id)
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
                'message' => 'يمكن تحويل الطلب إلى قيد المعالجة فقط إذا كان حالته جديد',
            ], 422);
        }

        $materialRequest->update([
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحويل الطلب إلى قيد المعالجة',
            'data'    => $materialRequest->fresh([
                'requestedBy',
                'clinic',
                'items.material',
                'newItems',
            ]),
        ]);
    }
}


