<?php

namespace App\Http\Controllers\Employee;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateEmployeeProfileRequest;
use App\Models\Employee;
use App\Models\User;
use App\Http\Resources\EmployeesResource;
use App\Http\Resources\EmployeeProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class EmployeeProfileController extends Controller
{
    public function showProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!Gate::allows('view-employee-profile')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        $employee = Employee::with(['user', 'educations', 'experiences', 'skills'])
            ->where('user_id', $user->id)
            ->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'سجل الموظف غير موجود.'
            ], 404);
        }

        return response()->json([
            'data' => new EmployeeProfileResource($employee)
        ], 200);
    }



    public function showAllEmployees(Request $request): JsonResponse
    {
        if (!Gate::allows('view-employees')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        $employees = User::whereNotIn('role', [UserRole::PATIENT->value,UserRole::ADMIN->value,])->get();

        return response()->json([
            'success' => true,
            'data'    => EmployeesResource::collection($employees),
        ]);
    }


    public function editProfile(UpdateEmployeeProfileRequest $request)
{
    $user = $request->user();

    if (!Gate::allows('edit-employee-profile')) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بالوصول.'
        ], 403);
    }

    if ($request->hasFile('profile_picture')) {
        $path = $request->file('profile_picture')
            ->store('profile_pictures', 'public');
        $user->update(['profile_picture' => $path]);
    }

    $user->update($request->only([
        'name', 'phone', 'date_of_birth', 'address', 'gender'
    ]));

    $user->employee->update($request->only([
        'secondary_phone', 'marital_status'
    ]));

    return response()->json([
        'success' => true,
        'message' => 'تم تحديث الملف الشخصي بنجاح.',
        'data'    => new EmployeeProfileResource(
            $user->employee->load(['user', 'educations', 'experiences', 'skills'])
        ),
    ]);
}

}
