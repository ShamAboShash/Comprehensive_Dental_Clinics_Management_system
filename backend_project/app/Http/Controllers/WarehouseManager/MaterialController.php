<?php

namespace App\Http\Controllers\WarehouseManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseManager\StoreMaterialRequest;
use App\Http\Requests\WarehouseManager\UpdateMaterialRequest;
use App\Models\Material;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class MaterialController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-warehouse-manager');
    }

    // GET /warehouseManager/materials
    public function index()
    {
        $materials = Material::with('warehouseStocks')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($material) => $this->formatMaterial($material));

        return response()->json([
            'success' => true,
            'data'    => $materials,
        ]);
    }

    // GET /warehouseManager/materials/{id}
    public function show($id)
    {
        $material = Material::with('warehouseStocks')->find($id);

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

    // POST /warehouseManager/materials
    public function store(StoreMaterialRequest $request)
    {
        DB::beginTransaction();

        try {
            $material = Material::create($request->validated());

            // Auto-create empty stock batch
            WarehouseStock::create([
                'material_id' => $material->id,
                'quantity'    => 0,
            ]);

            DB::commit();

            $material->load('warehouseStocks');

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

    // PATCH /warehouseManager/materials/{id}
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
        $material->load('warehouseStocks');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المادة بنجاح',
            'data'    => $this->formatMaterial($material),
        ]);
    }

    // PATCH /warehouseManager/materials/{id}/status
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ], [
            'is_active.required' => 'حالة التفعيل مطلوبة',
            'is_active.boolean'  => 'حالة التفعيل يجب أن تكون true أو false',
        ]);

        $material = Material::find($id);

        if (!$material) {
            return response()->json([
                'success' => false,
                'message' => 'المادة غير موجودة',
            ], 404);
        }

        // Optional safety: cannot deactivate if still has stock
        if ($request->is_active === false && $material->warehouseStocks()->where('quantity', '>', 0)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء تفعيل مادة لا تزال موجودة في المخزن. قم بتصفير الكمية أولاً',
            ], 422);
        }

        $material->update(['is_active' => $request->is_active]);

        return response()->json([
            'success' => true,
            'message' => $request->is_active ? 'تم تفعيل المادة بنجاح' : 'تم إلغاء تفعيل المادة بنجاح',
            'data'    => $this->formatMaterial($material->fresh('warehouseStocks')),
        ]);
    }


    // DELETE /warehouseManager/materials/{id}
    // public function destroy($id)
    // {
    //     $material = Material::find($id);

    //     if (!$material) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'المادة غير موجودة',
    //         ], 404);
    //     }

    //     // Prevent deletion if still has stock
    //     if ($material->warehouseStocks()->where('quantity', '>', 0)->exists()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'لا يمكن حذف مادة لا تزال موجودة في المخزن',
    //         ], 422);
    //     }

    //     // Prevent deletion if used in any request
    //     if ($material->materialRequestItems()->exists()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'لا يمكن حذف مادة مرتبطة بطلبات سابقة',
    //         ], 422);
    //     }

    //     $material->delete();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'تم حذف المادة بنجاح',
    //     ]);
    // }

    // ─── Private Helper ───────────────────────────────────────────

    private function formatMaterial(Material $material): array
    {
        return [
            'id'            => $material->id,
            'name'          => $material->name,
            'name_en'       => $material->name_en,
            'company_name'  => $material->company_name,
            'price_per_unit'=> $material->price_per_unit,
            'dosage'        => $material->dosage,
            'unit'          => $material->unit,
            'category'      => $material->category,
            'total_stock'   => $material->warehouseStocks->sum('quantity'),
            'batches_count' => $material->warehouseStocks->count(),
        ];
    }
}
