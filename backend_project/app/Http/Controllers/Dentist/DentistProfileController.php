<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Dentist;
use App\Http\Resources\DentistProfileResource;
use App\Http\Resources\DentistsResource;
use App\Http\Requests\AddMedicalInfoRequest;
use App\Http\Requests\UpdateDentistProfileRequest;
use App\Models\User;
use App\Enums\UserRole;

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

        // $dentist = $user->dentist()
        //     ->with(['specialization', 'workSchedules', 'employee'])
        //     ->first();

        $dentist = Dentist::with(['specialization', 'workSchedules', 'employee.user',
        'employee.educations',
        'employee.experiences',
        'employee.skills',
        'employee.trainings', ])
            ->whereHas('employee', fn($q) => $q->where('user_id', $user->id))
            ->first();

        if (!$dentist) {
            return response()->json([
                'success' => false,
                'message' => 'ملف الطبيب غير موجود في سجلات الموظفين.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new DentistProfileResource($dentist)
        ], 200);
    }


    public function addMedicalInfo(AddMedicalInfoRequest  $request, $patientId)
    {

        $user = $request->user();

        if (!Gate::allows('add-medical-info')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول، هذا الحساب ليس لطبيب أسنان.'
            ], 403);
        }

        $patient = Patient::find($patientId);
        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'المريض غير موجود'], 404);
        }

        if ($request->has('allergies')) {
            foreach ($request->allergies as $name) {
                $patient->allergies()->firstOrCreate(['allergy_name' => $name]);
            }
        }

        if ($request->has('chronic_diseases')) {
            foreach ($request->chronic_diseases as $disease) {
                $patient->chronicDiseases()->create($disease);
            }
        }

        if ($request->has('medications')) {
            foreach ($request->medications as $med) {
                $patient->medications()->create($med);
            }
        }
//ask if the frontend want this response or not
        $patient->load(['allergies', 'chronicDiseases.category', 'medications']);

        return response()->json([
        'success' => true,
        'message' => 'تم تحديث السجل الطبي للمريض بنجاح.',
        'data'    => [
            'patient_id'      => $patient->id,
            'allergies'       => $patient->allergies->map(fn($a) => [
                'id'          => $a->id,
                'name'        => $a->allergy_name,
            ]),
            'chronic_diseases' => $patient->chronicDiseases->map(fn($d) => [
                'id'           => $d->id,
                'category'     => $d->category->name,
                'problem_name' => $d->problem_name,
                'severity'     => $d->severity,
            ]),
            'medications'       => $patient->medications->map(fn($m) => [
                'id'            => $m->id,
                'medication_name' => $m->name,
                'dosage'        => $m->dosage,
                'frequency'     => $m->frequency,
            ]),
        ],
    ], 200);
    }


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
