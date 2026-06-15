<?php

namespace App\Http\Controllers\LabManager;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\WarehouseMaterialResource;

class WarehouseMaterialController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-lab-manager');
    }

    // GET /lab/warehouse/materials
    public function index()
    {
        $materials = WarehouseStock::with('material')
            ->orderBy('quantity', 'asc')
            ->get()
            ->map(fn($stock) => new WarehouseMaterialResource($stock));

        return response()->json([
            'success' => true,
            'data'    => $materials,
        ]);
    }

    // GET /lab/warehouse/materials/{id}
    public function show($id)
    {
        $stock = WarehouseStock::with('material')->find($id);

        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'المادة غير موجودة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new WarehouseMaterialResource($stock),
        ]);
    }

    // ─── Private Helper ───────────────────────────────────────────

    // private function formatMaterial(WarehouseStock $stock): array
    // {
    //     return [
    //         'id'          => $stock->id,
    //         'material_id' => $stock->material_id,
    //         'name'        => $stock->material->name ?? null,
    //         'unit'        => $stock->material->unit ?? null,
    //         'quantity'    => $stock->quantity,
    //         'description' => $stock->material->description ?? null,
    //     ];
    // }
}
