<?php

namespace App\Http\Controllers\WarehouseManager;

use App\Http\Controllers\Controller;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Gate;

class InventoryController extends Controller
{
    const LOW_STOCK_THRESHOLD = 10;

    public function __construct()
    {
        Gate::authorize('is-warehouse-manager');
    }

    // GET /warehouse/inventory/stock-levels
    public function stockLevels()
{
    $stocks = WarehouseStock::with('material')->get();

    $grouped = $stocks->groupBy('material_id')->map(function ($batches) {
        $totalQuantity = $batches->sum('quantity');
        return [
            'material'        => $batches->first()->material->name ?? null,
            'unit'            => $batches->first()->material->unit ?? null,
            'total_quantity'  => $totalQuantity,
            'batches_count'   => $batches->count(),
            'is_low'          => $totalQuantity <= WarehouseStockController::LOW_STOCK_THRESHOLD,
            'has_expired'     => $batches->contains(
                fn($b) => $b->expiration_date && $b->expiration_date < now()->toDateString()
            ),
            'batches'         => $batches->map(fn($b) => [
                'id'              => $b->id,
                'quantity'        => $b->quantity,
                'expiration_date' => $b->expiration_date,
                'is_expired'      => $b->expiration_date && $b->expiration_date < now()->toDateString(),
            ])->values(),
        ];
    })->values();

    $low    = $grouped->where('is_low', true);
    $normal = $grouped->where('is_low', false);

    return response()->json([
        'success' => true,
        'data'    => [
            'summary' => [
                'total_materials'    => $grouped->count(),
                'low_stock_count'    => $low->count(),
                'normal_stock_count' => $normal->count(),
                'expired_batches'    => $stocks->filter(
                    fn($b) => $b->expiration_date && $b->expiration_date < now()->toDateString()
                )->count(),
            ],
            'low_stock' => $low->values(),
            'normal'    => $normal->values(),
        ],
    ]);
}

    // GET /warehouse/inventory/stock-value
    public function stockValue()
{
    $stocks = WarehouseStock::with('material')->get();

    $grouped = $stocks->groupBy('material_id')->map(function ($batches) {
        $price      = $batches->first()->material->price ?? 0;
        $totalQty   = $batches->sum('quantity');
        $totalValue = $totalQty * $price;

        return [
            'material'      => $batches->first()->material->name ?? null,
            'unit'          => $batches->first()->material->unit ?? null,
            'total_quantity'=> $totalQty,
            'unit_price'    => $price,
            'total_value'   => round($totalValue, 2),
            'batches_count' => $batches->count(),
        ];
    })->sortByDesc('total_value')->values();

    return response()->json([
        'success' => true,
        'data'    => [
            'summary' => [
                'total_materials' => $grouped->count(),
                'total_value'     => round($grouped->sum('total_value'), 2),
            ],
            'items' => $grouped,
        ],
    ]);
}

    // GET /warehouse/inventory/by-category
    public function byCategory()
    {
        $stock = WarehouseStock::with('material')
            ->get();

        // Group by unit as a proxy for category
        // Replace 'unit' with a real category_id if your materials table has one
        $grouped = $stock->groupBy(fn($item) => $item->material->unit ?? 'غير محدد');

        $result = $grouped->map(function ($items, $category) {
            return [
                'category'        => $category,
                'total_materials' => $items->count(),
                'total_quantity'  => round($items->sum('quantity'), 2),
                'items'           => $items->map(fn($item) => [
                    'id'       => $item->id,
                    'material' => $item->material->name ?? null,
                    'quantity' => $item->quantity,
                    'is_low'   => $item->quantity <= self::LOW_STOCK_THRESHOLD,
                ])->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    // ─── Private Helper ───────────────────────────────────────────

    private function formatStockLevel(WarehouseStock $stock): array
    {
        return [
            'id'       => $stock->id,
            'material' => $stock->material->name ?? null,
            'unit'     => $stock->material->unit ?? null,
            'quantity' => $stock->quantity,
            'is_low'   => $stock->quantity <= self::LOW_STOCK_THRESHOLD,
        ];
    }
}
