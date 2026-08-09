<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseMaterialResource;
use App\Models\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class WarehouseMaterialController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-secretary');
    }

    /**
     * 20. عرض منتجات المستودع
     * GET /secretary/showAllWarehouseMaterials
     */
    public function index(): JsonResponse
    {
        $materials = WarehouseStock::with('material')
            ->orderBy('quantity', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $materials->count(),
            'data'    => WarehouseMaterialResource::collection($materials),
        ]);
    }

    /**
     * عرض مادة محددة من المستودع
     * GET /secretary/showWarehouseMaterial/{id}
     */
    public function show(int $id): JsonResponse
    {
        $stock = WarehouseStock::with('material')->find($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'المادة غير موجودة في المستودع.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new WarehouseMaterialResource($stock),
        ]);
    }
}
