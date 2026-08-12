<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dentist\StoreLabOrderRequest;
use App\Http\Requests\Dentist\UpdateLabOrderRequest;
use App\Http\Requests\Dentist\StoreLabOrderModificationRequest;
use App\Http\Requests\Dentist\UpdateLabOrderModificationRequest;
use App\Http\Resources\Dentist\LabOrderDetailResource;
use App\Http\Resources\Dentist\LabOrderModificationResource;
use App\Http\Resources\Dentist\LabOrderResource;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabOrderModification;
use App\Models\LabOrderType;
use App\Models\Treatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LabOrderController extends Controller
{
    private function getDentistId(Request $request): int
    {
        return $request->user()->employee->dentist->id;
    }

    // ---------------------------------------------------------------
    // GET /dentist/lab-orders
    // API #19 — Show all dentist's lab orders
    // ---------------------------------------------------------------
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        $orders = LabOrder::with(['patient.user', 'technician.user', 'items.labOrderType', 'modifications'])
            ->where('dentist_id', $dentistId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $orders->count(),
            'data'    => LabOrderResource::collection($orders),
        ]);
    }

    // ---------------------------------------------------------------
    // GET /dentist/lab-orders/{labOrder}
    // API #20 — Show specific lab order with items and modifications
    // ---------------------------------------------------------------
    public function show(Request $request, LabOrder $labOrder): JsonResponse
    {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if ($labOrder->dentist_id !== $dentistId) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب لا ينتمي إليك.',
            ], 403);
        }

        $labOrder->load([
            'patient.user',
            'technician.user',
            'items.labOrderType',
            'modifications',
        ]);

        return response()->json([
            'success' => true,
            'data'    => new LabOrderDetailResource($labOrder),
        ]);
    }

    // ---------------------------------------------------------------
    // POST /dentist/lab-orders
    // API #21 — Create a new lab order
    // ---------------------------------------------------------------
    public function store(StoreLabOrderRequest $request): JsonResponse
    {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        // Verify the treatment belongs to this dentist
        $treatment = Treatment::find($request->treatment_id);
        if ($treatment->dentist_id !== $dentistId) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إنشاء طلب مخبري لعلاج لم تقم بإنشائه.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $order = LabOrder::create([
                'treatment_id'            => $request->treatment_id,
                'session_id'              => $request->session_id,
                'dentist_id'              => $dentistId,
                'patient_id'              => $treatment->patient_id,
                'status'                  => 'new',
                'delivery_date_requested' => $request->delivery_date_requested,
                'notes'                   => $request->notes,
                'price'                   => 0,
            ]);

           $totalPrice = 0;

            foreach ($request->items as $item) {
                // Snapshot price from lab_order_type at order time
                $type = LabOrderType::find($item['lab_order_type_id']);

                LabOrderItem::create([
                    'lab_order_id'      => $order->id,
                    'lab_order_type_id' => $item['lab_order_type_id'],
                    'tooth_number'      => $item['tooth_number'],
                    'price'             => $type->price,   // snapshot
                    'notes'             => $item['notes'] ?? null,
                ]);

                $totalPrice += $type->price;
            }

            $order->update(['price' => $totalPrice]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الطلب.',
            ], 500);
        }

        $order->load(['patient.user', 'technician.user', 'items.labOrderType', 'modifications']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الطلب المخبري بنجاح.',
            'data'    => new LabOrderDetailResource($order),
        ], 201);
    }

    // ---------------------------------------------------------------
    // PUT /dentist/lab-orders/{labOrder}
    // API #22 — Edit lab order (only when status = new)
    // ---------------------------------------------------------------
    public function update(
        UpdateLabOrderRequest $request,
        LabOrder $labOrder
    ): JsonResponse {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if ($labOrder->dentist_id !== $dentistId) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب لا ينتمي إليك.',
            ], 403);
        }

        if ($labOrder->status !== 'new') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل الطلب بعد أن بدأ المختبر العمل عليه.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $labOrder->update($request->only([
                'session_id',
                'delivery_date_requested',
                'notes',
            ]));

            // Replace items if provided
            if ($request->has('items')) {
                $labOrder->items()->delete();

                $totalPrice = 0;

                foreach ($request->items as $item) {
                $type = LabOrderType::find($item['lab_order_type_id']);

                LabOrderItem::create([
                    'lab_order_id'      => $labOrder->id,
                    'lab_order_type_id' => $item['lab_order_type_id'],
                    'tooth_number'      => $item['tooth_number'],
                    'price'             => $type->price,
                    'notes'             => $item['notes'] ?? null,
                ]);

                $totalPrice += $type->price;
            }

            $labOrder->update(['price' => $totalPrice]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الطلب.',
            ], 500);
        }

        $labOrder->load(['patient.user', 'technician.user', 'items.labOrderType', 'modifications']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الطلب المخبري بنجاح.',
            'data'    => new LabOrderDetailResource($labOrder),
        ]);
    }

    // ---------------------------------------------------------------
    // PATCH /dentist/lab-orders/{labOrder}/cancel
    // API #23 — Cancel a lab order (only when status = new)
    // ---------------------------------------------------------------
    public function cancel(Request $request, LabOrder $labOrder): JsonResponse
    {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if ($labOrder->dentist_id !== $dentistId) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب لا ينتمي إليك.',
            ], 403);
        }

        if ($labOrder->status !== 'new') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء الطلب بعد أن بدأ المختبر العمل عليه.',
            ], 422);
        }

        $labOrder->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الطلب المخبري بنجاح.',
            'data'    => new LabOrderResource($labOrder->fresh(['patient.user', 'items'])),
        ]);
    }

     public function storeModification(
        StoreLabOrderModificationRequest $request,
        LabOrder $labOrder
    ): JsonResponse {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if ($labOrder->dentist_id !== $dentistId) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب لا ينتمي إليك.',
            ], 403);
        }

        // Can only request modifications on in_progress or completed orders
        if (!in_array($labOrder->status, ['completed'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إضافة طلب تعديل إلا للطلبات المكتملة.',
            ], 422);
        }

        $modification = LabOrderModification::create([
            'lab_order_id'         => $labOrder->id,
            'modification_details' => $request->modification_details,
            'fault'                => $request->fault,
            'status'               => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب التعديل بنجاح.',
            'data' => new LabOrderModificationResource($modification),
        ], 201);
    }

    // ---------------------------------------------------------------
    // POST /dentist/lab-orders/{labOrder}/modifications/{modification}/update
    // Edit a modification (only when status = pending)
    // ---------------------------------------------------------------
    public function updateModification(
        UpdateLabOrderModificationRequest $request,
        LabOrder $labOrder,
        LabOrderModification $modification
    ): JsonResponse {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if ($labOrder->dentist_id !== $dentistId) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب لا ينتمي إليك.',
            ], 403);
        }

        if ($modification->lab_order_id !== $labOrder->id) {
            return response()->json([
                'success' => false,
                'message' => 'هذا التعديل لا ينتمي إلى هذا الطلب.',
            ], 404);
        }

        if ($modification->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل الطلب بعد أن تمت معالجته من قبل المختبر.',
            ], 422);
        }

        $modification->update($request->only([
            'modification_details',
            'fault',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث طلب التعديل بنجاح.',
            'data'    => new LabOrderModificationResource($modification->fresh()),
        ]);
    }

    // ---------------------------------------------------------------
    // POST /dentist/lab-orders/{labOrder}/modifications/{modification}/cancel
    // Cancel a modification (only when status = pending)
    // ---------------------------------------------------------------
    public function cancelModification(
        Request $request,
        LabOrder $labOrder,
        LabOrderModification $modification
    ): JsonResponse {
        Gate::authorize('is-dentist');

        $dentistId = $this->getDentistId($request);

        if ($labOrder->dentist_id !== $dentistId) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب لا ينتمي إليك.',
            ], 403);
        }

        if ($modification->lab_order_id !== $labOrder->id) {
            return response()->json([
                'success' => false,
                'message' => 'هذا التعديل لا ينتمي إلى هذا الطلب.',
            ], 404);
        }

        if ($modification->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء الطلب بعد أن تمت معالجته من قبل المختبر.',
            ], 422);
        }

        $modification->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء طلب التعديل بنجاح.',
            'data'    => new LabOrderModificationResource($modification->fresh()),
        ]);
    }
}
