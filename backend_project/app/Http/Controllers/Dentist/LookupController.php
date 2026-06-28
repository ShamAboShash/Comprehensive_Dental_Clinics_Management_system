<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dentist\LabOrderTypeResource;
use App\Http\Resources\Dentist\ProcedureResource;
use App\Models\LabOrderType;
use App\Models\Procedure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LookupController extends Controller
{
    // ---------------------------------------------------------------
    // GET /dentist/lookups/procedures
    // API #24 — Show all procedures
    // ---------------------------------------------------------------
    public function procedures(Request $request): JsonResponse
    {
        Gate::authorize('is-dentist');

        $procedures = Procedure::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'count'   => $procedures->count(),
            'data'    => ProcedureResource::collection($procedures),
        ]);
    }

    // ---------------------------------------------------------------
    // GET /dentist/lookups/lab-order-types
    // API #25 — Show all active lab order types grouped by category
    // ---------------------------------------------------------------
    public function labOrderTypes(Request $request): JsonResponse
    {
        Gate::authorize('is-dentist');

        $types = LabOrderType::with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Group by category for easier frontend rendering
        $grouped = $types->groupBy(fn($t) => $t->category?->name ?? 'غير مصنف')
            ->map(fn($items, $categoryName) => [
                'category' => $categoryName,
                'items'    => LabOrderTypeResource::collection($items),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'count'   => $types->count(),
            'data'    => $grouped,
        ]);
    }
}
