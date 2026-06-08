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
    public function showProfile(Request $request)
    {
        $user = $request->user();

        if (!Gate::allows('view-employee-profile')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        $employee = Employee::with(['user', 'educations', 'experiences', 'skills','trainings'])
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



    public function showAllEmployees(Request $request)
    {
        if (!Gate::allows('view-employees')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        $employees = User::whereNotIn('role', [UserRole::PATIENT->value,UserRole::ADMIN->value,UserRole::DENTIST->value,])->get();

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

    $employee = $user->employee;

    // Add/Update educations (NO delete)
    if ($request->filled('educations')) {
        foreach ($request->input('educations') as $edu) {
            $attributes = [
                'employee_id' => $employee->id,
                'degree' => $edu['degree'] ?? null,
                'institution' => $edu['institution'] ?? null,
                'completion_date' => $edu['completion_date'] ?? null,
                'grade' => $edu['grade'] ?? null,
            ];

            if (!empty($edu['id'])) {
                $employee->educations()
                    ->where('id', $edu['id'])
                    ->update($attributes);
            } else {
                $employee->educations()->create($attributes);
            }
        }
    }

    // Add/Update experiences (NO delete)
    if ($request->filled('experiences')) {
        foreach ($request->input('experiences') as $exp) {
            $attributes = [
                'employee_id' => $employee->id,
                'title' => $exp['title'] ?? null,
                'company' => $exp['company'] ?? null,
                'start_date' => $exp['start_date'] ?? null,
                'end_date' => $exp['end_date'] ?? null,
            ];

            if (!empty($exp['id'])) {
                $employee->experiences()
                    ->where('id', $exp['id'])
                    ->update($attributes);
            } else {
                $employee->experiences()->create($attributes);
            }
        }
    }

    // Add/Update trainings (NO delete)
    if ($request->filled('trainings')) {
        foreach ($request->input('trainings') as $t) {
            $attributes = [
                'employee_id' => $employee->id,
                'course_title' => $t['course_title'] ?? null,
                'trainer' => $t['trainer'] ?? null,
                'course_date' => $t['course_date'] ?? null,
            ];

            if (!empty($t['id'])) {
                $employee->trainings()
                    ->where('id', $t['id'])
                    ->update($attributes);
            } else {
                $employee->trainings()->create($attributes);
            }
        }
    }

    // Add/Update skills (NO delete)
    if ($request->filled('skills')) {
        foreach ($request->input('skills') as $s) {
            $attributes = [
                'employee_id' => $employee->id,
                'skill_name' => $s['skill_name'] ?? null,
            ];

            if (!empty($s['id'])) {
                $employee->skills()
                    ->where('id', $s['id'])
                    ->update($attributes);
            } else {
                $employee->skills()->create($attributes);
            }
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'تم تحديث الملف الشخصي بنجاح.',
        'data' => new EmployeeProfileResource(
            $employee->load(['user', 'educations', 'experiences', 'trainings', 'skills'])
        ),
    ]);
}

}

