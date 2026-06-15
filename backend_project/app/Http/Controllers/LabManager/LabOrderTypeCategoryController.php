<?php

namespace App\Http\Controllers\LabManager;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabOrderTypeCategoryResource;
use App\Models\LabOrderTypeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LabOrderTypeCategoryController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-lab-manager');
    }

    // GET /lab/item-categories
    public function index()
    {
        $categories = LabOrderTypeCategory::withCount('labOrderTypes')->get();

        return response()->json([
            'success' => true,
            'data'    => LabOrderTypeCategoryResource::collection($categories),
        ]);
    }

    // GET /lab/item-categories/{id}
    public function show($id)
    {
        $category = LabOrderTypeCategory::withCount('labOrderTypes')->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'الفئة غير موجودة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new LabOrderTypeCategoryResource($category),
        ]);
    }

    // POST /lab/item-categories
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:lab_order_type_categories,name',
        ]);

        $category = LabOrderTypeCategory::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الفئة بنجاح',
            'data'    => new LabOrderTypeCategoryResource($category),
        ], 201);
    }

    // PATCH /lab/item-categories/{id}
    public function update(Request $request, $id)
    {
        $category = LabOrderTypeCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'الفئة غير موجودة',
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:lab_order_type_categories,name,' . $id,
        ]);

        $category->update(['name' => $request->name]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الفئة بنجاح',
            'data'    => new LabOrderTypeCategoryResource($category),
        ]);
    }

    // DELETE /lab/item-categories/{id}
    public function destroy($id)
    {
        $category = LabOrderTypeCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'الفئة غير موجودة',
            ], 404);
        }

        if ($category->labOrderTypes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف هذه الفئة لأنها مرتبطة بمنتجات',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الفئة بنجاح',
        ]);
    }
}
