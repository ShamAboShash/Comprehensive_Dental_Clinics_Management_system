<?php

namespace App\Http\Controllers\WarehouseManager;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialRequestItem;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryController extends Controller
{
    const LOW_STOCK_THRESHOLD = 10;
    const EXPIRING_SOON_DAYS  = 20;

    public function __construct()
    {
        Gate::authorize('is-warehouse-manager');
    }

    // =========================================================
    // 1) المواد الأكثر طلباً
    // GET /warehouseManager/inventory/most-requested
    // Optional: ?limit=10
    // =========================================================
    public function mostRequested()
    {
        $limit = (int) request('limit', 10);

        $items = MaterialRequestItem::query()
            ->select(
                'material_id',
                DB::raw('SUM(quantity_requested) as total_quantity'),
                DB::raw('COUNT(*) as request_count')
            )
            ->whereNotNull('material_id')
            ->groupBy('material_id')
            ->orderByDesc('request_count')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->with('material')
            ->get()
            ->map(function ($row) {
                return [
                    'material_id'     => $row->material_id,
                    'name'            => $row->material->name ?? null,
                    'name_en'         => $row->material->name_en ?? null,
                    'unit'            => $row->material->unit ?? null,
                    'company_name'    => $row->material->company_name ?? null,
                    'category'        => $row->material->category ?? null,
                    'request_count'   => (int) $row->request_count,
                    'total_quantity'  => (int) $row->total_quantity,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    // =========================================================
    // 2) المواد التي ستنتهي صلاحيتها قريباً
    // GET /warehouseManager/inventory/expiring-soon
    // Optional: ?days=30
    // =========================================================
    public function expiringSoon()
    {
        $days = (int) request('days', self::EXPIRING_SOON_DAYS);
        $today = Carbon::today();
        $limitDate = Carbon::today()->addDays($days);

        $batches = WarehouseStock::with('material')
            ->where('quantity', '>', 0)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '>=', $today)
            ->whereDate('expiration_date', '<=', $limitDate)
            ->orderBy('expiration_date', 'asc')
            ->get()
            ->map(function ($stock) use ($today) {
                $expiry = Carbon::parse($stock->expiration_date);
                return [
                    'batch_id'         => $stock->id,
                    'material_id'      => $stock->material_id,
                    'name'             => $stock->material->name ?? null,
                    'unit'             => $stock->material->unit ?? null,
                    'company_name'     => $stock->material->company_name ?? null,
                    'quantity'         => $stock->quantity,
                    'expiration_date'  => $expiry->format('Y-m-d'),
                    'days_remaining'   => $today->diffInDays($expiry, false),
                ];
            });

        // Also group by material (optional summary)
        $byMaterial = $batches->groupBy('material_id')->map(function ($group) {
            return [
                'material_id'       => $group->first()['material_id'],
                'name'              => $group->first()['name'],
                'unit'              => $group->first()['unit'],
                'total_quantity'    => $group->sum('quantity'),
                'batches_count'     => $group->count(),
                'nearest_expiry'    => $group->min('expiration_date'),
                // 'batches'           => $group->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'days'            => $days,
                'batches_count'   => $batches->count(),
                'batches'         => $batches->values(),
                'by_material'     => $byMaterial,
            ],
        ]);
    }

    // =========================================================
    // 3) المواد التي ستنفد (low stock)
    // GET /warehouseManager/inventory/low-stock
    // Optional: ?threshold=10
    // =========================================================
    public function lowStock()
    {
        $threshold = (int) request('threshold', self::LOW_STOCK_THRESHOLD);

        $materials = Material::with('warehouseStocks')
            ->orderBy('name')
            ->get()
            ->map(function ($material) use ($threshold) {
                $total = $material->warehouseStocks->sum('quantity');

                return [
                    'material_id'    => $material->id,
                    'name'           => $material->name,
                    'name_en'        => $material->name_en,
                    'unit'           => $material->unit,
                    'company_name'   => $material->company_name,
                    'category'       => $material->category,
                    'is_active'      => $material->is_active ?? true,
                    'total_quantity' => $total,
                    'batches_count'  => $material->warehouseStocks->where('quantity', '>', 0)->count(),
                    'is_low'         => $total <= $threshold,
                    'is_out'         => $total <= 0,
                ];
            })
            ->filter(fn($m) => $m['total_quantity'] <= $threshold)
            ->sortBy('total_quantity')
            ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'threshold' => $threshold,
                'count'     => $materials->count(),
                'items'     => $materials,
            ],
        ]);
    }
}
