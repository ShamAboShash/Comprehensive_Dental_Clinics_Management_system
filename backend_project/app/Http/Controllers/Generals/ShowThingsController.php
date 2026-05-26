<?php

namespace App\Http\Controllers\Generals;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\Specialization;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShowThingsController extends Controller
{
    public function medications(Request $request)
    {
        $user = $request->user();

        if (!Gate::allows('view-medications')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        $medications = Medication::query()->get();

        return response()->json([
            'success' => true,
            'data'    => $medications->map(fn($med) => [
            'id'   => $med->id,
            'name' => $med->name,
        ]),
        ]);
    }

    public function specializations(Request $request)
    {
        if (!Gate::allows('view-specializations')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        $specializations = Specialization::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data'    =>  $specializations->map(fn($spec) => [
            'id'   => $spec->id,
            'name' => $spec->name,
             ]),
        ]);
    }

    public function roles(Request $request)
    {
        if (!Gate::allows('view-roles')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        $roles = collect(UserRole::cases());

        return response()->json([
            'success' => true,
            'data'    =>  $roles->map(fn($role) => [
            'id'   => $role->value,
            'name' => $role->label(),
             ]),
        ]);
    }
}
