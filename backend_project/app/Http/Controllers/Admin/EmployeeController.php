<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeProfileResource;
use App\Http\Resources\PatientProfileResource;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\EmployeeExperience;
use App\Models\EmployeeSkill;
use App\Models\EmployeeTraining;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{


    public function createEmployee(StoreEmployeeRequest $request)
    {
        if (!Gate::allows('manage-employees')) {
            return response()->json(['message' => 'غير مصرح لك بالوصول.'], 403);
        }



        return DB::transaction(function () use ($request) {

            $profilePicturePath = null;

            if ($request->hasFile('profile_picture')) {
                $profilePicturePath = $request->file('profile_picture')
                    ->store('profile_pictures', 'public');
            } else {
                $gender = $request->gender; 

                $defaultImage = ($gender == 1) ? 'profile_pictures/male.png' : 'profile_pictures/female.png';

                $profilePicturePath = $defaultImage;
            }
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'address' => $request->address,
                'profile_picture' => $profilePicturePath,
                'gender' => $request->gender,
                'role' => $request->role,
                'is_active' => false,
            ]);

            $employee = Employee::create([
                'user_id' => $user->id,
                'secondary_phone' => $request->secondary_phone,
                'marital_status' => $request->marital_status,
                'salary' => $request->salary,
                'hire_date' => $request->hire_date,
            ]);

            $this->createRelatedData($employee, $request);

            // if ($request->role === UserRole::DENTIST->value) {
            //     Dentist::create([
            //         'employee_id' => $employee->id,
            //         'specialization_id' => $request->specialization_id,
            //         'work_hours' => $request->work_hours,
            //         'license_number' => $request->license_number,
            //         'years_of_experience' => $request->years_of_experience,
            //     ]);
            // }

            $user->load([ 'employee.educations', 'employee.experiences', 'employee.trainings', 'employee.skills']);

            return response()->json([
                'message' => 'Employee created successfully',
                'data' => $user
            ], 201);
        });
    }

    public function createRelatedData(Employee $employee, Request $request): void
    {
        if ($request->educations) {
            foreach ($request->educations as $edu) {
                EmployeeEducation::create([
                    'employee_id' => $employee->id,
                    ...$edu
                ]);
            }
        }

        if ($request->experiences) {
            foreach ($request->experiences as $exp) {
                EmployeeExperience::create([
                    'employee_id' => $employee->id,
                    ...$exp
                ]);
            }
        }

        if ($request->trainings) {
            foreach ($request->trainings as $training) {
                EmployeeTraining::create([
                    'employee_id' => $employee->id,
                    ...$training
                ]);
            }
        }

        if ($request->skills) {
            foreach ($request->skills as $skill) {
                EmployeeSkill::create([
                    'employee_id' => $employee->id,
                    'skill_name' => $skill['skill_name']
                ]);
            }
        }
    }

    public function updateEmployee(UpdateEmployeeRequest $request, User $employee)
    {
        if (!Gate::allows('manage-employees')) {
            return response()->json([
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        return DB::transaction(function () use ($request, $employee) {
            $employee->update([
                'name'          => $request->name ?? $employee->name,
                'phone'         => $request->phone ?? $employee->phone,
                'date_of_birth' => $request->date_of_birth ?? $employee->date_of_birth,
                'address'       => $request->address ?? $employee->address,
                'gender'        => $request->gender ?? $employee->gender,
                'role'          => $request->role->value ?? $employee->role,
            ]);

            $empData = $employee->employee ?? Employee::create(['user_id' => $employee->id]);

            $empData->update([
                'secondary_phone' => $request->secondary_phone,
                'marital_status'  => $request->marital_status,
                'salary'          => $request->salary,
                'hire_date'       => $request->hire_date,
            ]);

            $this->syncRelatedData($empData, $request);

            // if ($employee->role === UserRole::DENTIST->value) {
            //     Dentist::updateOrCreate(
            //         ['employee_id' => $empData->id],
            //         [
            //             'specialization_id'   => $request->specialization_id,
            //             'license_number'      => $request->license_number,
            //             'years_of_experience' => $request->years_of_experience,
            //             'bio'                 => $request->bio ?? null,
            //         ]
            //     );
            // } elseif ($employee->wasChanged('role') && $employee->role !== UserRole::DENTIST->value) {
            //     Dentist::where('employee_id', $empData->id)->delete();
            // }

            $employee->load([
                // 'employee.dentist',
                'employee.educations',
                'employee.experiences',
                'employee.trainings',
                'employee.skills'
            ]);

            return response()->json([
                'message' => 'Employee updated successfully',
                'data'    => new EmployeeResource($employee)
            ]);
        });
    }

    public function syncRelatedData(Employee $employee, $request)
    {
        if ($request->has('educations')) {
            $employee->educations()->delete();
            foreach ($request->educations as $edu) {
                $employee->educations()->create($edu);
            }
        }

        if ($request->has('experiences')) {
            $employee->experiences()->delete();
            foreach ($request->experiences as $exp) {
                $employee->experiences()->create($exp);
            }
        }

        if ($request->has('trainings')) {
            $employee->trainings()->delete();
            foreach ($request->trainings as $training) {
                $employee->trainings()->create($training);
            }
        }

        if ($request->has('skills')) {
            $employee->skills()->delete();
            foreach ($request->skills as $skill) {
                $employee->skills()->create([
                    'skill_name' => $skill['skill_name']
                ]);
            }
        }
    }

    public function toggleUser(Request $request, $id)
    {
        if (!Gate::allows('manage-employees')) {
            return response()->json([
                'message' => 'Unauthorized. Only administrators can block or unblock users.'
            ], 403);
        }
        $user = User::findOrFail($id);

            // Optional: Prevent deactivating admin or yourself
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك تعطيل حسابك الخاص.'
                ], 403);
            }
        $newStatus = !$user->is_active;
        $user->update(['is_active' => $newStatus]);

        $statusText = $newStatus ? 'تفعيل' : 'تعطيل';

        return response()->json([
            'message' => "User has been {$statusText} successfully.",
            'data' => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'phone'     => $user->phone,
                'role'      => $user->role->value,
                'is_active' => $user->is_active,
            ]
        ]);
    }


    public function showEmployeeDetails($employeeId)
    {
        $employee = Employee::with([
            'user',
            'educations',
            'experiences',
            'skills',
            'trainings'
        ])->findOrFail($employeeId);

        // Authorization using Gate
        if (!Gate::allows('view-employee-details', $employee)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول. هذا الإجراء للمدراء فقط.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => new EmployeeProfileResource($employee)
        ], 200);
    }

    public function showPatientProfile($patientId)
    {
        $patient = Patient::with([
            'user',
            'allergies',
            'chronicDiseases.category',      // if you have category relation
            'medications.medication'         // adjust if your relation name is different
        ])->findOrFail($patientId);

        // Authorization using Gate
        if (!Gate::allows('view-patient-profile', $patient)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول. هذا الإجراء مخصص للأطباء فقط.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => new PatientProfileResource($patient)
        ], 200);
    }



}
