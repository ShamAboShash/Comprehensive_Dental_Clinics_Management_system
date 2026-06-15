<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreMaterialRequest;
use App\Http\Requests\Warehouse\UpdateMaterialRequest;
use App\Models\Material;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class MaterialController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-warehouse-manager');
    }

    // GET /warehouse/materials
    public function index()
    {
        $materials = Material::orderBy('name')
            ->get()
            ->map(fn($material) => $this->formatMaterial($material));

        return response()->json([
            'success' => true,
            'data'    => $materials,
        ]);
    }

    // GET /warehouse/materials/{id}
    public function show($id)
    {
        $material = Material::find($id);

        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'المادة غير موجودة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatMaterial($material),
        ]);
    }

    // POST /warehouse/materials
    public function store(StoreMaterialRequest $request)
    {
        DB::beginTransaction();

        try {
            $material = Material::create($request->validated());

            // Auto-create a warehouse stock entry with 0 quantity
            WarehouseStock::create([
                'material_id' => $material->id,
                'quantity'    => 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المادة بنجاح',
                'data'    => $this->formatMaterial($material),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة المادة',
            ], 500);
        }
    }

    // PATCH /warehouse/materials/{id}
    public function update(UpdateMaterialRequest $request, $id)
    {
        $material = Material::find($id);

        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'المادة غير موجودة',
            ], 404);
        }

        $material->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المادة بنجاح',
            'data'    => $this->formatMaterial($material->fresh()),
        ]);
    }

    // DELETE /warehouse/materials/{id}
    public function destroy($id)
    {
        $material = Material::find($id);

        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'المادة غير موجودة',
            ], 404);
        }

        // Prevent deletion if material has stock movements
        if ($material->warehouseStocks()->where('quantity', '>', 0)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف مادة لا تزال موجودة في المخزن',
            ], 422);
        }

        // Prevent deletion if material is used in any request
        if ($material->materialRequestItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف مادة مرتبطة بطلبات سابقة',
            ], 422);
        }

        $material->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المادة بنجاح',
        ]);
    }

    // ─── Private Helper ───────────────────────────────────────────

    private function formatMaterial(Material $material): array
{
    return [
        'id'           => $material->id,
        'name'         => $material->name,
        'unit'         => $material->unit,
        'price'        => $material->price,
        'description'  => $material->description,
        'total_stock'  => $material->warehouseStocks->sum('quantity'),
        'batches_count'=> $material->warehouseStocks->count(),
    ];
}
}
