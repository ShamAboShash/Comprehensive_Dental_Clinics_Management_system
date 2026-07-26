<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Dentist;
use App\Models\User;
use App\Http\Resources\DentistProfileResource;
use App\Http\Resources\DentistsResource;
use App\Http\Requests\AddMedicalInfoRequest;
use App\Http\Requests\UpdateDentistProfileRequest;
use App\Enums\UserRole;
use Carbon\Carbon;

class DentistProfileController extends Controller
{

    public function showProfile(Request $request)
    {
        $user = $request->user();

        if (!Gate::allows('view-dentist-profile')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        $dentist = Dentist::with([
            'specialization',
            'workSchedules',
            'employee.user',
            'employee.educations',
            'employee.experiences',
            'employee.skills',
            'employee.trainings',
        ])
        ->whereHas('employee', fn($q) => $q->where('user_id', $user->id))
        ->first();

        if (!$dentist) {
            return response()->json([
                'success' => false,
                'message' => 'ملف الطبيب غير موجود في سجلات الموظفين.'
            ], 404);
        }

        $weekStart = Carbon::now()->startOfWeek(Carbon::SATURDAY);
        $weekEnd   = Carbon::now()->endOfWeek(Carbon::FRIDAY);

        $weeklyAppointments = Appointment::where('dentist_id', $dentist->id)
            ->whereBetween('appointment_time', [$weekStart, $weekEnd])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'data'    => new DentistProfileResource($dentist),
            'weekly_appointments' => [
                // 'week_start'    => $weekStart->toDateString(),
                // 'week_end'      => $weekEnd->toDateString(),
                'count'         => $weeklyAppointments->sum(),
                // 'pending'       => $weeklyAppointments->get('pending', 0),
                // 'pending_secretary' => $weeklyAppointments->get('pending_secretary', 0),
                // 'approved'      => $weeklyAppointments->get('approved', 0),
                // 'completed'     => $weeklyAppointments->get('completed', 0),
                // 'cancelled'     => $weeklyAppointments->get('cancelled', 0),
                // 'rejected'      => $weeklyAppointments->get('rejected', 0),
                // 'patient_no_show' => $weeklyAppointments->get('patient_no_show', 0),
            ],
        ], 200);
    }

    // public function showProfile(Request $request)
    // {
    //     $user = $request->user();

    //     if (!Gate::allows('view-dentist-profile')) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'غير مصرح لك بالوصول.'
    //         ], 403);
    //     }

    //     $dentist = Dentist::with(['specialization', 'workSchedules', 'employee.user',
    //     'employee.educations',
    //     'employee.experiences',
    //     'employee.skills',
    //     'employee.trainings', ])
    //         ->whereHas('employee', fn($q) => $q->where('user_id', $user->id))
    //         ->first();

    //     if (!$dentist) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'ملف الطبيب غير موجود في سجلات الموظفين.'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data' => new DentistProfileResource($dentist)
    //     ], 200);
    // }


public function editProfile(UpdateDentistProfileRequest $request)
{
    $user = $request->user()->load('employee.dentist');

    if (!Gate::allows('edit-dentist-profile')) {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح لك بالوصول.'
        ], 403);
    }

    // Safety checks
    if (!$user->employee) {
        return response()->json([
            'success' => false,
            'message' => 'هذا الحساب ليس مرتبطاً بسجل موظف.'
        ], 404);
    }

    if (!$user->employee->dentist) {
        return response()->json([
            'success' => false,
            'message' => 'هذا الموظف ليس مسجلاً كطبيب أسنان.'
        ], 404);
    }

    // Handle profile picture
    if ($request->hasFile('profile_picture')) {
        $path = $request->file('profile_picture')
            ->store('profile_pictures', 'public');
        $user->update(['profile_picture' => $path]);
    }

    // Update User
    $user->update($request->only([
        'name', 'phone', 'date_of_birth', 'address', 'gender'
    ]));

    // Update Employee
    $user->employee->update($request->only([
        'secondary_phone', 'marital_status'
    ]));

    // Update Dentist
    $user->employee->dentist->update($request->only([
        'license_number', 'years_of_experience', 'bio'
    ]));

    return response()->json([
        'success' => true,
        'message' => 'تم تحديث الملف الشخصي بنجاح.',
        'data'    => new DentistProfileResource(
            $user->employee->dentist->load(['specialization', 'workSchedules', 'employee.user'])
        ),
    ]);
}



 public function showAllDentists(Request $request)
    {
        if (!Gate::allows('manage-employees')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        $dentists = User::where('role', UserRole::DENTIST->value)
            ->with([
                'employee.dentist.specialization',

            ])
            ->get();

        return response()->json([
            'success' => true,
            'data'    => DentistsResource::collection($dentists),
        ]);
    }

//     public function editProfile(UpdateDentistProfileRequest $request)
// {
//     $user = $request->user();

//     if (!Gate::allows('edit-dentist-profile')) {
//         return response()->json([
//             'success' => false,
//             'message' => 'غير مصرح لك بالوصول.'
//         ], 403);
//     }

//     // handle profile picture upload
//     if ($request->hasFile('profile_picture')) {
//         $path = $request->file('profile_picture')
//             ->store('profile_pictures', 'public');
//         $user->update(['profile_picture' => $path]);
//     }

//     // update User fields
//     $user->update($request->only([
//         'name', 'phone', 'date_of_birth', 'address', 'gender'
//     ]));

//     // update Employee fields
//     $user->employee->update($request->only([
//         'secondary_phone', 'marital_status'
//     ]));

//     // update Dentist fields
//     $user->employee->dentist->update($request->only([
//         'license_number', 'years_of_experience', 'bio'
//     ]));

//     return response()->json([
//         'success' => true,
//         'message' => 'تم تحديث الملف الشخصي بنجاح.',
//         'data'    => new DentistProfileResource(
//             $user->employee->dentist->load(['specialization', 'workSchedules', 'employee.user'])
//         ),
//     ]);
// }


}
