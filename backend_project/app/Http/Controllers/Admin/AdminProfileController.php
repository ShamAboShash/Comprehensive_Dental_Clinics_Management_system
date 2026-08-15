<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\UpdateAdminProfileRequest;
use App\Http\Resources\AdminProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
 public function updateProfile(UpdateAdminProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Gate::allows('is-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.',
            ], 403);
        }

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')
                ->store('profile_pictures', 'public');
            $user->update(['profile_picture' => $path]);
        }

        $user->update($request->only([
            'name',
            'email',
            'phone',
            'date_of_birth',
            'address',
            'gender',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح.',
            'data'    => new AdminProfileResource($user->fresh()),
        ]);
    }
}
