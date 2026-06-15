<?php

namespace App\Http\Controllers\LabManager;

use App\Http\Controllers\Controller;
use App\Models\LabStock;
use App\Models\LabStockLog;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\LabStockLogResource;
use App\Http\Resources\LabStockResource;

class LabStockController extends Controller
{
    const LOW_STOCK_THRESHOLD = 10;

    public function __construct()
    {
        Gate::authorize('is-lab-manager');
    }

    // GET /lab/stock
    public function index()
    {
        $stocks = LabStock::with('material')->get();

        return response()->json([
            'success' => true,
            'data'    => LabStockResource::collection($stocks),
        ]);
    }

    // GET /lab/stock/{id}
    public function show($id)
    {
        $stock = LabStock::with('material')->find($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'المادة غير موجودة في مخزن المختبر',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new LabStockResource($stock),
        ]);
    }

    // POST /lab/stock/{id}/add
    public function add(Request $request, $id)
    {
        $stock = LabStock::with('material')->find($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'المادة غير موجودة في مخزن المختبر',
            ], 404);
        }

        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'notes'    => 'nullable|string|max:500',
        ]);

        $stock->increment('quantity', $request->quantity);

        LabStockLog::create([
            'material_id'  => $stock->material_id,
            'lab_stock_id' => $stock->id,
            'type'         => 'add',
            'quantity'     => $request->quantity, // positive
            'reason'       => 'إضافة يدوية للمخزون',
            'notes'        => $request->notes,
            'done_by'      => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الكمية بنجاح',
            'data'    => new LabStockResource($stock->fresh('material')),
        ]);
    }

    // POST /lab/stock/{id}/subtract
    public function subtract(Request $request, $id)
    {
        $stock = LabStock::with('material')->find($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'المادة غير موجودة في مخزن المختبر',
            ], 404);
        }

        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'notes'    => 'nullable|string|max:500',
        ]);

        if ($stock->quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'الكمية المطلوبة أكبر من المخزون المتاح',
            ], 422);
        }

        $stock->decrement('quantity', $request->quantity);

        LabStockLog::create([
            'material_id'  => $stock->material_id,
            'lab_stock_id' => $stock->id,
            'type'         => 'subtract',
            'quantity'     => -$request->quantity, // negative
            'reason'       => 'خصم يدوي من المخزون',
            'notes'        => $request->notes,
            'done_by'      => $request->user()->id,
        ]);

        // Auto-notify lab manager if stock dropped below threshold
        $stock->refresh();
        if ($stock->quantity <= self::LOW_STOCK_THRESHOLD) {
            $this->notifyLowStock($stock);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم خصم الكمية بنجاح',
            'data'    => new LabStockResource($stock->fresh('material')),
        ]);
    }

    // GET /lab/stock/logs
    public function logs()
{
    $logs = LabStockLog::with('labStock.material')
        ->when(request('stock_id'), fn($q) => $q->where('lab_stock_id', request('stock_id')))
        ->orderBy('created_at', 'desc')
        ->paginate(20);
        
    return LabStockLogResource::collection($logs);
}

    // POST /lab/stock/notify-short
    public function notifyShort(Request $request)
    {
        $request->validate([
            'items'         => 'required|array|min:1',
            'items.*.id'    => 'required|exists:lab_stocks,id',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $shortItems = LabStock::with('material')
            ->whereIn('id', collect($request->items)->pluck('id'))
            ->get();

        // Notify lab manager about the shortage
        $labManagers = User::where('role', UserRole::LAB_MANAGER)
            ->where('is_active', true)
            ->get();

        $itemNames = $shortItems->pluck('material.name')->join('، ');

        // foreach ($labManagers as $manager) {
        //     NotificationService::send(
        //         user: $manager,
        //         title: 'تنبيه: نقص في مواد المختبر',
        //         body: "المواد التالية تحتاج إلى طلب تزويد: {$itemNames}",
        //         type: 'stock_shortage_alert',
        //         referenceable: null,
        //     );
        // }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال تنبيه النقص بنجاح',
        ]);
    }

    // ====================== Private Helpers ======================

    private function notifyLowStock(LabStock $stock): void
    {
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
