<?php

namespace App\Http\Controllers\LabManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\LabManager\SetOrderPendingRequest;
use App\Http\Requests\LabManager\SetOrderCompletedRequest;
use App\Http\Resources\LabOrderResource;
use App\Models\LabOrder;
use App\Models\LabOrderMaterial;
use App\Models\LabStock;
use App\Models\LabStockLog;
use App\Models\Employee;
use App\Services\NotificationService;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LabOrderController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-lab-manager');
    }

    // GET /lab/orders
    public function index()
    {
        $orders = LabOrder::with(['items.labOrderType', 'dentist.employee.user', 'patient.user', 'technician.user'])
            ->when(request('status'), fn($q) => $q->where('status', request('status')))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => LabOrderResource::collection($orders),
        ]);
    }

    // GET /lab/orders/today
    public function today()
    {
        $orders = LabOrder::with(['items.labOrderType', 'dentist.employee.user', 'patient.user', 'technician.user'])
            ->whereDate('delivery_date_requested', Carbon::today())
            ->orderBy('delivery_date_requested', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => LabOrderResource::collection($orders),
        ]);
    }

    // GET /lab/orders/{id}
    public function show($id)
    {
        $order = LabOrder::with([
            'items.labOrderType',
            'modifications',
            'dentist.employee.user',
            'patient.user',
            'technician.user',
        ])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new LabOrderResource($order),
        ]);
    }

    // PATCH /lab/orders/{id}/set-pending
    public function setInProgress(SetOrderPendingRequest $request, $id)
    {
        $order = LabOrder::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        if ($order->status !== 'new') {
            return response()->json([
                'success' => false,
                'message' => 'يمكن تعيين الفني فقط للطلبات الجديدة',
            ], 422);
        }

        $technician = Employee::whereHas('user', function ($q) {
        $q->where('role', UserRole::TECHNICIAN->value)
          ->where('is_active', true);
            })->find($request->technician_id);

        if (!$technician) {
            return response()->json([
                'success' => false,
                'message' => 'الفني غير متاح',
            ], 422);
        }

        $isBusy = LabOrder::where('technician_id', $technician->id)
            ->where('status', 'in_progress')
            ->exists();

        if ($isBusy) {
            return response()->json([
                'success' => false,
                'message' => 'الفني مشغول حالياً على طلب آخر',
            ], 422);
        }

        $order->update([
            'status'       => 'in_progress',
            'technician_id' => $technician->id,
        ]);


        return response()->json([
            'success' => true,
            'message' => 'تم تعيين الفني بنجاح',
            'data'    => new LabOrderResource($order->fresh(['items.labOrderType', 'technician.user'])),
        ]);
    }

    // PATCH /lab/orders/{id}/set-completed
// PATCH /lab/orders/{id}/set-completed
public function setCompleted(SetOrderCompletedRequest $request, $id)
{
    $order = LabOrder::with(['dentist.employee.user', 'items'])->find($id);

    if (!$order) {
        return response()->json([
            'success' => false,
            'message' => 'الطلب غير موجود',
        ], 404);
    }

    if ($order->status !== 'in_progress') {
        return response()->json([
            'success' => false,
            'message' => 'يمكن إكمال الطلبات قيد التنفيذ فقط',
        ], 422);
    }

    DB::beginTransaction();

    try {
        $lowStockMaterials = [];   // collect materials that became low

        if ($request->filled('materials')) {
            foreach ($request->materials as $item) {
                $stock = LabStock::with('material')
                    ->where('material_id', $item['material_id'])
                    ->first();

                if (!$stock || $stock->quantity < $item['quantity_used']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'الكمية المتاحة في مخزن المختبر غير كافية لإحدى المواد',
                    ], 422);
                }

                $stock->decrement('quantity', $item['quantity_used']);

                LabStockLog::create([
                    'material_id'  => $stock->material_id,
                    'lab_stock_id' => $stock->id,
                    'type'         => 'subtract',
                    'quantity'     => -$item['quantity_used'],
                    'reason'       => "استخدام في طلب مختبر #{$order->id}",
                    'done_by'      => $request->user()->id,
                ]);

                LabOrderMaterial::create([
                    'lab_order_id'  => $order->id,
                    'material_id'   => $item['material_id'],
                    'quantity_used' => $item['quantity_used'],
                ]);

                // Check low stock after decrement
                $stock->refresh();
                if ($stock->quantity <= LabStockController::LOW_STOCK_THRESHOLD) {
                    $lowStockMaterials[] = $stock;
                }
            }
        }

        // Update order status
        $order->update(['status' => 'completed']);

        DB::commit();

        // ====================== NOTIFICATIONS (after successful commit) ======================
$dentistUser = $order->dentist?->employee?->user;
        // 1. Notify the dentist that the lab order is completed
        if ($dentistUser) {
            NotificationService::send(
                user: $dentistUser,
                title: 'طلب المختبر مكتمل',
                body: "تم إنجاز طلب المختبر #{$order->id} بنجاح",
                type: 'lab_order_ready',          // or 'lab_order_completed'
                referenceable: $order
            );
        }

        // 2. Notify all active Lab Managers about low stock materials
        if (!empty($lowStockMaterials)) {
            $labManagers = User::where('role', UserRole::LAB_MANAGER)
                ->where('is_active', true)
                ->get();

            foreach ($lowStockMaterials as $stock) {
                foreach ($labManagers as $manager) {
                    NotificationService::send(
                        user: $manager,
                        title: 'تنبيه: مخزون منخفض',
                        body: "المادة ({$stock->material->name}) وصلت لحد التنبيه في مخزن المختبر (الكمية الحالية: {$stock->quantity})",
                        type: 'low_stock_alert',
                        referenceable: $stock
                    );
                }
            }
        }

        // ====================== END NOTIFICATIONS ======================

        return response()->json([
            'success' => true,
            'message' => 'تم إكمال الطلب بنجاح',
            'data'    => new LabOrderResource($order->fresh([
                'items.labOrderType',
                'technician.user',
                'modifications',
                'dentist.employee.user',
            ])),
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Lab order complete failed', [
            'order_id' => $id,
            'error'    => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء إكمال الطلب',
        ], 500);
    }
}

    // PATCH /lab/orders/{id}/set-cancelled
    public function setCancelled($id)
    {
        $order = LabOrder::with('technician.user')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        if ($order->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء طلب مكتمل',
            ], 422);
        }

        $order->update(['status' => 'cancelled']);


        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الطلب',
            'data'    => new LabOrderResource($order->fresh(['items.labOrderType', 'technician.user'])),
        ]);
    }
}
