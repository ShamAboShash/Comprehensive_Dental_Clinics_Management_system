<?php

// namespace App\Http\Controllers\LabManager;

// use App\Http\Controllers\Controller;
// use App\Http\Requests\LabManager\StoreLabItemRequest;
// use App\Http\Requests\LabManager\UpdateLabItemRequest;
// use App\Http\Resources\LabItemResource;
// use App\Models\LabOrderType;
// use Illuminate\Support\Facades\Gate;

// class LabItemController extends Controller
// {
//     public function __construct()
//     {
//         Gate::authorize('is-lab-manager');
//     }

//     // GET /lab/items
//     public function index()
//     {
//         $items = LabOrderType::get();

//         return response()->json([
//             'success' => true,
//             'data'    => $items,
//         ]);
//     }

//     // GET /lab/items/{id}
//     public function show($id)
//     {
//         $item = LabOrderType::find($id);

//         if (!$item) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'العنصر غير موجود',
//             ], 404);
//         }

//         return response()->json([
//             'success' => true,
//             'data'    => $item,
//         ]);
//     }

//     // POST /lab/items
//     public function store(StoreLabItemRequest $request)
//     {
//         $item = LabOrderType::create($request->validated());

//         return response()->json([
//             'success' => true,
//             'message' => 'تم إضافة العنصر بنجاح',
//             'data'    => $item,
//         ], 201);
//     }

//     // PATCH /lab/items/{id}
//     public function update(UpdateLabItemRequest $request, $id)
//     {
//         $item = LabOrderType::find($id);

//         if (!$item) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'العنصر غير موجود',
//             ], 404);
//         }

//         $item->update($request->validated());

//         return response()->json([
//             'success' => true,
//             'message' => 'تم تحديث العنصر بنجاح',
//             'data'    => $item,
//         ]);
//     }

//     // DELETE /lab/items/{id}
//     public function destroy($id)
//     {
//         $item = LabOrderType::find($id);

//         if (!$item) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'العنصر غير موجود',
//             ], 404);
//         }

//         // Prevent deletion if item is used in existing orders
//         if ($item->labOrders()->exists()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'لا يمكن حذف هذا العنصر لأنه مرتبط بطلبات مختبر',
//             ], 422);
//         }

//         $item->delete();

//         return response()->json([
//             'success' => true,
//             'message' => 'تم حذف العنصر بنجاح',
//         ]);
//     }

//     // PATCH /lab/items/{id}/toggle
// public function toggle($id)
// {
//     $item = LabOrderType::find($id);

//     if (!$item) {
//         return response()->json([
//             'success' => false,
//             'message' => 'المنتج غير موجود',
//         ], 404);
//     }

//     $item->update(['is_active' => !$item->is_active]);

//     return response()->json([
//         'success' => true,
//         'message' => $item->is_active ? 'تم تفعيل المنتج' : 'تم إيقاف المنتج',
//         'data'    => new LabItemResource($item),
//     ]);
// }
// }



namespace App\Http\Controllers\LabManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\LabManager\StoreLabItemRequest;
use App\Http\Requests\LabManager\UpdateLabItemRequest;
use App\Http\Resources\LabItemResource;
use App\Models\LabOrderType;
use Illuminate\Support\Facades\Gate;

class LabItemController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-lab-manager');
    }

    // GET /lab/items
    public function index()
    {
        $items = LabOrderType::with('category')->get();

        return response()->json([
            'success' => true,
            'data'    => LabItemResource::collection($items),
        ]);
    }

    // GET /lab/items/{id}
    public function show($id)
    {
        $item = LabOrderType::with('category')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'العنصر غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new LabItemResource($item),
        ]);
    }

    // POST /lab/items
    public function store(StoreLabItemRequest $request)
    {
        $item = LabOrderType::create($request->validated());
        $item->load('category');

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة العنصر بنجاح',
            'data'    => new LabItemResource($item),
        ], 201);
    }

    // PATCH /lab/items/{id}
    public function update(UpdateLabItemRequest $request, $id)
    {
        $item = LabOrderType::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'العنصر غير موجود',
            ], 404);
        }

        $item->update($request->validated());
        $item->load('category');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث العنصر بنجاح',
            'data'    => new LabItemResource($item),
        ]);
    }

    // DELETE /lab/items/{id}
    public function destroy($id)
    {
        $item = LabOrderType::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'العنصر غير موجود',
            ], 404);
        }

        if ($item->labOrderItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف هذا العنصر لأنه مرتبط بطلبات مختبر',
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العنصر بنجاح',
        ]);
    }

    // PATCH /lab/items/{id}/toggle
    public function toggle($id)
    {
        $item = LabOrderType::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'المنتج غير موجود',
            ], 404);
        }

        $item->update(['is_active' => !$item->is_active]);
        $item->load('category');

        return response()->json([
            'success' => true,
            'message' => $item->is_active ? 'تم تفعيل المنتج' : 'تم إيقاف المنتج',
            'data'    => new LabItemResource($item),
        ]);
    }
}
