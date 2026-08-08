<?php

namespace App\Http\Controllers\WarehouseManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseManager\AdjustWarehouseStockRequest;
use App\Http\Requests\WarehouseManager\StoreWarehouseBatchRequest;
use App\Http\Resources\WarehouseStockResource;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockLog;
use App\Models\Material;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class WarehouseStockController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-warehouse-manager');
    }

    // GET /warehouseManager/stock
    // public function index()
    // {
    //     $stocks = WarehouseStock::with('material')
    //         ->orderBy('material_id')
    //         ->orderBy('expiration_date')
    //         ->get();

    //     return response()->json([
    //         'success' => true,
    //         'data'    => WarehouseStockResource::collection($stocks),
    //     ]);
    // }

    // // GET /warehouseManager/stock/{id}
    // public function show($id)
    // {
    //     $stock = WarehouseStock::with('material')->find($id);

    //     if (!$stock) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'دفعة المخزون غير موجودة',
    //         ], 404);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data'    => new WarehouseStockResource($stock),        ]);
    // }

    // GET /warehouseManager/stock
// List materials with total stock
    public function index()
    {
        $materials = Material::with('warehouseStocks')
            ->orderBy('name')
            ->get()
            ->map(function ($material) {
                $totalQuantity = $material->warehouseStocks->sum('quantity');
                $batchesCount  = $material->warehouseStocks->count();

                return [
                    'material_id'     => $material->id,
                    'name'            => $material->name,
                    'name_en'         => $material->name_en,
                    'company_name'    => $material->company_name,
                    'unit'            => $material->unit,
                    'category'        => $material->category,
                    'price_per_unit'  => $material->price_per_unit,
                    'is_active'       => $material->is_active ?? true,
                    'total_quantity'  => $totalQuantity,
                    'batches_count'   => $batchesCount,
                    // 'has_expired'     => $material->warehouseStocks->contains(
                    //     fn ($b) => $b->expiration_date && $b->expiration_date->lt(now()->startOfDay())
                    // ),
                    'is_low'          => $totalQuantity <= 10, // change threshold if you want
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $materials,
        ]);
    }

    // GET /warehouseManager/stock/{materialId}
    // All batches for one material
    public function show($materialId)
    {
        $material = Material::with('warehouseStocks')->find($materialId);

        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'المادة غير موجودة',
            ], 404);
        }

        $batches = $material->warehouseStocks
        ->where('quantity', '>', 0)          // ← hide empty batches
        ->sortBy('expiration_date')
        ->values()
        ->map(function ($stock) {
            return [
                'id'              => $stock->id,
                'quantity'        => $stock->quantity,
                'expiration_date' => $stock->expiration_date
                    ? $stock->expiration_date->format('Y-m-d')
                    : null,
                'is_expired'      => $stock->expiration_date
                    && $stock->expiration_date->lt(now()->startOfDay()),
                'created_at'      => $stock->created_at?->toDateTimeString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'material' => [
                    'id'             => $material->id,
                    'name'           => $material->name,
                    'name_en'        => $material->name_en,
                    'company_name'   => $material->company_name,
                    'unit'           => $material->unit,
                    'category'       => $material->category,
                    'price_per_unit' => $material->price_per_unit,
                    'is_active'      => $material->is_active ?? true,
                ],
                'total_quantity' => $batches->sum('quantity'),
                'batches_count'  => $batches->count(),
                'batches'        => $batches,
            ],
        ]);
    }


    public function storeBatch(StoreWarehouseBatchRequest $request)
    {
        $material = Material::find($request->material_id);

        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'المادة غير موجودة',
            ], 404);
        }

        // Optional: block adding stock for inactive materials
        if (isset($material->is_active) && !$material->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إضافة مخزون لمادة غير مفعّلة',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $stock = WarehouseStock::create([
                'material_id'     => $request->material_id,
                'quantity'        => $request->quantity,
                'expiration_date' => $request->expiration_date,
            ]);

            WarehouseStockLog::create([
                'material_id'        => $request->material_id,
                'warehouse_stock_id' => $stock->id,
                'type'               => 'in',
                'quantity'           => $request->quantity,
                'reason'             => 'adjustment', // manual add (not from purchase invoice)
                'notes'              => $request->notes ?? 'إضافة دفعة جديدة يدوياً',
                'done_by'            => $request->user()->id,
            ]);

            DB::commit();

            $stock->load('material');

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الدفعة بنجاح',
                'data'    => [
                    'id'              => $stock->id,
                    'quantity'        => $stock->quantity,
                    'expiration_date' => $stock->expiration_date
                        ? $stock->expiration_date->format('Y-m-d')
                        : null,
                    'material'        => [
                        'id'           => $material->id,
                        'name'         => $material->name,
                        'unit'         => $material->unit,
                        'company_name' => $material->company_name,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الدفعة',
            ], 500);
        }
    }

    // POST /warehouseManager/stock/{id}/adjust
    public function adjust(AdjustWarehouseStockRequest $request, $id)
    {
        $stock = WarehouseStock::with('material')->find($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'دفعة المخزون غير موجودة',
            ], 404);
        }

        $type     = $request->type;      // in | out
        $quantity = $request->quantity;

        // Prevent negative stock
        if ($type === 'out' && $stock->quantity < $quantity) {
            return response()->json([
                'success' => false,
                'message' => "الكمية غير كافية. المتوفر حالياً: {$stock->quantity}",
            ], 422);
        }

        DB::beginTransaction();

        try {
            if ($type === 'in') {
                $stock->increment('quantity', $quantity);
            } else {
                $stock->decrement('quantity', $quantity);
            }

            WarehouseStockLog::create([
                'material_id'        => $stock->material_id,
                'warehouse_stock_id' => $stock->id,
                'type'               => $type,
                'quantity'           => $quantity,
                'reason'             => $request->reason,
                'notes'              => $request->notes,
                'done_by'            => $request->user()->id,
            ]);

            DB::commit();

            $stock->refresh()->load('material');

            return response()->json([
                'success' => true,
                'message' => $type === 'in'
                    ? 'تم زيادة الكمية بنجاح'
                    : 'تم إنقاص الكمية بنجاح',
                'data'    => $this->formatStock($stock),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تعديل الكمية',
            ], 500);
        }
    }

    // GET /warehouseManager/stock/logs
    // Optional filters: ?material_id=1  or  ?warehouse_stock_id=5
    public function logs()
    {
        $query = WarehouseStockLog::with(['material', 'warehouseStock', 'doneBy'])
            ->orderBy('created_at', 'desc');

        if (request('material_id')) {
            $query->where('material_id', request('material_id'));
        }

        if (request('warehouse_stock_id')) {
            $query->where('warehouse_stock_id', request('warehouse_stock_id'));
        }

        $logs = $query->paginate(20);

        $logs->getCollection()->transform(function ($log) {
            return [
                'id'                 => $log->id,
                'material_id'        => $log->material_id,
                'material_name'      => $log->material->name ?? null,
                'warehouse_stock_id' => $log->warehouse_stock_id,
                'type'               => $log->type,
                'quantity'           => $log->quantity,
                'reason'             => $log->reason,
                'notes'              => $log->notes,
                'done_by'            => $log->doneBy->name ?? null,
                'created_at'         => $log->created_at?->toDateTimeString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $logs,
        ]);
    }

    // ─── Private Helper ───────────────────────────────────────────

    private function formatStock(WarehouseStock $stock): array
    {
        return [
            'id'              => $stock->id,
            'material_id'     => $stock->material_id,
            'material_name'   => $stock->material->name ?? null,
            'unit'            => $stock->material->unit ?? null,
            'company_name'    => $stock->material->company_name ?? null,
            'quantity'        => $stock->quantity,
            'expiration_date' => $stock->expiration_date
                ? $stock->expiration_date->format('Y-m-d')
                : null,
            'is_expired'      => $stock->expiration_date
                && $stock->expiration_date->lt(now()->startOfDay()),
            'created_at'      => $stock->created_at?->toDateTimeString(),
        ];
    }
}
