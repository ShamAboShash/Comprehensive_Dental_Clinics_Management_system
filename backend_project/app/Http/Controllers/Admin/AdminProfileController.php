<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\AdminProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminProfileController extends Controller
{
    public function showProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!Gate::allows('view-admin-profile')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        if (!$user ) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول، هذا الحساب ليس لمدير نظام.'
            ], 403);
        }

        // 2. إعادة بيانات الأدمن مباشرة من الـ User Model
        return response()->json([
            'success' => true,
            'data' => new AdminProfileResource($user)
        ], 200);
    }
}
