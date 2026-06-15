<?php

// namespace App\Http\Controllers\LabManager;

// use App\Http\Controllers\Controller;
// use App\Http\Requests\LabManager\UpdateLabOrderStatusRequest;
// use App\Http\Requests\LabManager\SetOrderCompletedRequest;
// use App\Http\Requests\LabManager\SetOrderPendingRequest;
// use App\Http\Resources\LabOrderResource;
// use App\Models\LabOrder;
// use App\Models\LabStock;
// use App\Models\LabStockLog;
// use App\Models\User;
// use App\Services\NotificationService;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Gate;
// use Carbon\Carbon;
// use App\Enums\UserRole;


// class LabOrderController extends Controller
// {
//     public function __construct()
//     {
//         Gate::authorize('is-lab-manager');
//     }

//     // GET /lab/orders
//     public function index()
//     {
//         $orders = LabOrder::with(['labOrderType', 'items', 'treatment.patient.user'])
//             ->when(request('status'), fn($q) => $q->where('status', request('status')))
//             ->orderBy('created_at', 'desc')
//             ->get();

//         return response()->json([
//             'success' => true,
//             'data'    =>  LabOrderResource::collection($orders),
//         ]);
//     }

//     // GET /lab/orders/today
//     public function today()
//     {
//         $orders = LabOrder::with(['labOrderType', 'items', 'treatment.patient.user'])
//             ->whereDate('created_at', Carbon::today())
//             ->orderBy('created_at', 'desc')
//             ->get();

//         return response()->json([
//             'success' => true,
//             'data'    => new LabOrderResource($orders),
//         ]);
//     }

//     // GET /lab/orders/{id}
//     public function show($id)
//     {
//         $order = LabOrder::with([
//             'labOrderType',
//             'items',
//             'modifications',
//             'treatment.patient.user',
//             'treatment.dentist.user',
//         ])->find($id);

//         if (!$order) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'الطلب غير موجود',
//             ], 404);
//         }

//         return response()->json([
//             'success' => true,
//             'data'    => new LabOrderResource($order),
//         ]);
//     }



// // PATCH /lab/orders/{id}/set-pending
// public function setPending(SetOrderPendingRequest $request, $id)
// {
//     $order = LabOrder::find($id);

//     if (!$order) {
//         return response()->json([
//             'success' => false,
//             'message' => 'الطلب غير موجود',
//         ], 404);
//     }

//     if ($order->status !== 'new') {
//         return response()->json([
//             'success' => false,
//             'message' => 'يمكن تعيين الفني فقط للطلبات الجديدة',
//         ], 422);
//     }

//     $technician = User::where('id', $request->technician_id)
//         ->where('role', UserRole::Technician)
//         ->where('is_active', true)
//         ->first();

//     if (!$technician) {
//         return response()->json([
//             'success' => false,
//             'message' => 'الفني غير متاح',
//         ], 422);
//     }

//     $order->update([
//         'status'        => 'pending',
//         'technician_id' => $technician->id,
//     ]);

//     NotificationService::send(
//         user: $technician,
//         title: 'طلب مختبر جديد',
//         body: "تم تعيينك على طلب المختبر #{$order->id}",
//         type: 'lab_order_assigned',
//         referenceable: $order,
//     );

//     return response()->json([
//         'success' => true,
//         'message' => 'تم تعيين الفني بنجاح',
//         'data'    => new LabOrderResource($order->fresh(['labOrderType', 'technician'])),
//     ]);
// }

// // PATCH /lab/orders/{id}/set-in-progress
// public function setInProgress($id)
// {
//     $order = LabOrder::find($id);

//     if (!$order) {
//         return response()->json([
//             'success' => false,
//             'message' => 'الطلب غير موجود',
//         ], 404);
//     }

//     if ($order->status !== 'pending') {
//         return response()->json([
//             'success' => false,
//             'message' => 'يمكن بدء العمل فقط على الطلبات المعلقة',
//         ], 422);
//     }

//     $order->update(['status' => 'in_progress']);

//     return response()->json([
//         'success' => true,
//         'message' => 'تم بدء العمل على الطلب',
//         'data'    => new LabOrderResource($order->fresh(['labOrderType', 'technician'])),
//     ]);
// }

// // PATCH /lab/orders/{id}/set-completed
// public function setCompleted(SetOrderCompletedRequest $request, $id)
// {
//     $order = LabOrder::find($id);

//     if (!$order) {
//         return response()->json([
//             'success' => false,
//             'message' => 'الطلب غير موجود',
//         ], 404);
//     }

//     if ($order->status !== 'in_progress') {
//         return response()->json([
//             'success' => false,
//             'message' => 'يمكن إكمال الطلبات قيد التنفيذ فقط',
//         ], 422);
//     }

//     DB::beginTransaction();

//     try {
//         foreach ($request->materials as $item) {
//             $stock = LabStock::where('material_id', $item['material_id'])->first();

//             if (!$stock || $stock->quantity < $item['quantity_used']) {
//                 DB::rollBack();
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'الكمية المتاحة في مخزن المختبر غير كافية لإحدى المواد',
//                 ], 422);
//             }

//             // Deduct stock
//             $stock->decrement('quantity', $item['quantity_used']);

//             // Log as negative
//             LabStockLog::create([
//                 'lab_stock_id' => $stock->id,
//                 'quantity'     => -$item['quantity_used'],
//                 'reason'       => "استخدام في طلب مختبر #{$order->id}",
//             ]);

//             // Record materials used — store positive
//             LabOrderMaterial::create([
//                 'lab_order_id'  => $order->id,
//                 'material_id'   => $item['material_id'],
//                 'quantity_used' => $item['quantity_used'],
//             ]);

//             // Auto notify lab manager if stock is low after deduction
//             $stock->refresh();
//             if ($stock->quantity <= LabStockController::LOW_STOCK_THRESHOLD) {
//                 NotificationService::send(
//                     user: $request->user(),
//                     title: 'تنبيه: مخزون منخفض',
//                     body: "المادة ({$stock->material->name}) وصلت لحد التنبيه في مخزن المختبر",
//                     type: 'low_stock_alert',
//                     referenceable: null,
//                 );
//             }
//         }

//         $order->update(['status' => 'completed']);

//         // Notify dentist
//         $dentist = $order->treatment->dentist->user ?? null;
//         if ($dentist) {
//             NotificationService::send(
//                 user: $dentist,
//                 title: 'طلب المختبر مكتمل',
//                 body: "تم إنجاز طلب المختبر #{$order->id} بنجاح",
//                 type: 'lab_order_completed',
//                 referenceable: $order,
//             );
//         }

//         DB::commit();

//         return response()->json([
//             'success' => true,
//             'message' => 'تم إكمال الطلب بنجاح',
//             'data'    => new LabOrderResource($order->fresh(['labOrderType', 'technician', 'modifications'])),
//         ]);

//     } catch (\Exception $e) {
//         DB::rollBack();

//         return response()->json([
//             'success' => false,
//             'message' => 'حدث خطأ أثناء إكمال الطلب',
//         ], 500);
//     }
// }

// // PATCH /lab/orders/{id}/set-cancelled
// public function setCancelled($id)
// {
//     $order = LabOrder::find($id);

//     if (!$order) {
//         return response()->json([
//             'success' => false,
//             'message' => 'الطلب غير موجود',
//         ], 404);
//     }

//     if ($order->status === 'completed') {
//         return response()->json([
//             'success' => false,
//             'message' => 'لا يمكن إلغاء طلب مكتمل',
//         ], 422);
//     }

//     $order->update(['status' => 'cancelled']);

//     // Notify technician if already assigned
//     if ($order->technician) {
//         NotificationService::send(
//             user: $order->technician,
//             title: 'تم إلغاء طلب مختبر',
//             body: "تم إلغاء طلب المختبر #{$order->id}",
//             type: 'lab_order_cancelled',
//             referenceable: $order,
//         );
//     }

//     return response()->json([
//         'success' => true,
//         'message' => 'تم إلغاء الطلب',
//         'data'    => new LabOrderResource($order->fresh(['labOrderType', 'technician'])),
//     ]);
// }

//     // PATCH /lab/orders/{id}/status
// //    public function updateStatus(UpdateLabOrderStatusRequest $request, $id)
// //     {
// //         $order = LabOrder::find($id);

// //         if (!$order) {
// //             return response()->json([
// //                 'success' => false,
// //                 'message' => 'الطلب غير موجود',
// //             ], 404);
// //         }

// //         // Assign technician when status → pending
// //         if ($request->status === 'pending') {
// //             $technician = User::where('role', UserRole::TECHNICIAN)
// //                 ->where('id', $request->technician_id)
// //                 ->where('is_active', true)
// //                 ->first();

// //             if (!$technician) {
// //                 return response()->json([
// //                     'success' => false,
// //                     'message' => 'الفني غير متاح',
// //                 ], 422);
// //             }

// //             $order->update([
// //                 'status'        => 'pending',
// //                 'technician_id' => $technician->id,
// //             ]);

// //             // // Notify technician
// //             // NotificationService::send(
// //             //     user: $technician,
// //             //     title: 'طلب مختبر جديد',
// //             //     body: "تم تعيينك على طلب المختبر #{$order->id}",
// //             //     type: 'lab_order_assigned',
// //             //     referenceable: $order,
// //             // );
// //         }

// //         // Deduct stock when status → completed
// //         elseif ($request->status === 'completed') {
// //             foreach ($request->materials as $item) {
// //                 $stock = LabStock::where('material_id', $item['material_id'])->first();

// //                 if (!$stock || $stock->quantity < $item['quantity_used']) {
// //                     return response()->json([
// //                         'success' => false,
// //                         'message' => 'الكمية المتاحة في المخزن غير كافية لإحدى المواد',
// //                     ], 422);
// //                 }

// //                 // Deduct stock
// //                 $stock->decrement('quantity', $item['quantity_used']);

// //                 // Log the movement
// //                 LabStockLog::create([
// //                     'lab_stock_id' => $stock->id,
// //                     'quantity'     => -$item['quantity_used'],
// //                     'reason'       => "استخدام في طلب مختبر #{$order->id}",
// //                 ]);

// //                 // Save materials used on order
// //                 LabOrderMaterial::create([
// //                     'lab_order_id' => $order->id,
// //                     'material_id'  => $item['material_id'],
// //                     'quantity_used' => $item['quantity_used'],
// //                     'created_at' => Carbon::now(),
// //                     'updated_at' => Carbon::now(),
// //                 ]);
// //             }

// //             $order->update(['status' => 'completed']);

// //             // Notify dentist
// //             $dentist = $order->treatment->dentist->user ?? null;
// //             if ($dentist) {
// //                 NotificationService::send(
// //                     user: $dentist,
// //                     title: 'طلب المختبر مكتمل',
// //                     body: "تم إنجاز طلب المختبر #{$order->id} بنجاح",
// //                     type: 'lab_order_completed',
// //                     referenceable: $order,
// //                 );
// //             }
// //         }

// //         else {
// //             $order->update(['status' => $request->status]);
// //         }

// //         return response()->json([
// //             'success' => true,
// //             'message' => 'تم تحديث حالة الطلب بنجاح',
// //             'data'    => $order->fresh(['labOrderType', 'items', 'technician']),
// //         ]);
// //     }
// }




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
use App\Models\User;
use App\Services\NotificationService;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

        $technician = Employee::whereHas('user', fn($q) =>
            $q->where('role', UserRole::TECHNICIAN)
        )->find($request->technician_id);

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

    // // PATCH /lab/orders/{id}/set-in-progress
    // public function setInProgress($id)
    // {
    //     $order = LabOrder::find($id);

    //     if (!$order) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'الطلب غير موجود',
    //         ], 404);
    //     }

    //     if ($order->status !== 'pending') {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'يمكن بدء العمل فقط على الطلبات المعلقة',
    //         ], 422);
    //     }

    //     $order->update(['status' => 'in_progress']);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'تم بدء العمل على الطلب',
    //         'data'    => new LabOrderResource($order->fresh(['items.labOrderType', 'technician.user'])),
    //     ]);
    // }

    // PATCH /lab/orders/{id}/set-completed
    public function setCompleted(SetOrderCompletedRequest $request, $id)
    {
        $order = LabOrder::find($id);

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
If($request->materials){
        DB::beginTransaction();

        try {
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

                // Log as negative — deduction
                LabStockLog::create([
                    'material_id'  => $stock->material_id,
                    'lab_stock_id' => $stock->id,
                    'type'         => 'subtract',
                    'quantity'     => -$item['quantity_used'],
                    'reason'       => "استخدام في طلب مختبر #{$order->id}",
                    'done_by'      => $request->user()->id,
                ]);

                // Record materials used — store positive (how much was used)
                LabOrderMaterial::create([
                    'lab_order_id'  => $order->id,
                    'material_id'   => $item['material_id'],
                    'quantity_used' => $item['quantity_used'],
                ]);

                // Auto notify lab manager if stock dropped below threshold
                $stock->refresh();
                if ($stock->quantity <= LabStockController::LOW_STOCK_THRESHOLD) {
                    $labManagers = User::where('role', UserRole::LAB_MANAGER)
                        ->where('is_active', true)
                        ->get();

                    // foreach ($labManagers as $manager) {
                    //     NotificationService::send(
                    //         user: $manager,
                    //         title: 'تنبيه: مخزون منخفض',
                    //         body: "المادة ({$stock->material->name}) وصلت لحد التنبيه في مخزن المختبر",
                    //         type: 'low_stock_alert',
                    //         referenceable: null,
                    //     );
                    // }
                }
            }


            // Notify dentist
            // if ($order->dentist?->user) {
            //     NotificationService::send(
            //         user: $order->dentist->user,
            //         title: 'طلب المختبر مكتمل',
            //         body: "تم إنجاز طلب المختبر #{$order->id} بنجاح",
            //         type: 'lab_order_completed',
            //         referenceable: $order,
            //     );
            // }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إكمال الطلب',
            ], 500);
        }
    }

            $order->update(['status' => 'completed']);


        return response()->json([
                'success' => true,
                'message' => 'تم إكمال الطلب بنجاح',
                'data'    => new LabOrderResource($order->fresh(['items.labOrderType', 'technician.user', 'modifications'])),
            ]);
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

        // Notify technician if already assigned
        if ($order->technician?->user) {
            NotificationService::send(
                user: $order->technician->user,
                title: 'تم إلغاء طلب مختبر',
                body: "تم إلغاء طلب المختبر #{$order->id}",
                type: 'lab_order_cancelled',
                referenceable: $order,
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الطلب',
            'data'    => new LabOrderResource($order->fresh(['items.labOrderType', 'technician.user'])),
        ]);
    }
}
