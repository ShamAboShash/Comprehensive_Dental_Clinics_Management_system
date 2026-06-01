<?php

namespace App\Http\Controllers\LabManager;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeesResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TechnicianController extends Controller
{
    public function showAllTechnicians(Request $request)
{
    if (!Gate::allows('show-technicians')) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بالوصول.'
        ], 403);
    }

    $technicians = User::where('role', UserRole::TECHNICIAN->value)
        ->with(['employee'])
        ->get();

    return response()->json([
        'success' => true,
        'data'    => EmployeesResource::collection($technicians),
    ]);
}
}
