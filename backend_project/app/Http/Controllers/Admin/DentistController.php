<?php

namespace App\Http\Controllers\Admin;

// use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeProfileResource;
use App\Models\Dentist;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Enums\UserRole;
use App\Http\Resources\EmployeeResource;

class DentistController extends EmployeeController
{
    /**
     * Create a new Dentist (with Employee + User)
     */
    public function createDentist(StoreEmployeeRequest $request)
    {
        if (!Gate::allows('manage-employees')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return DB::transaction(function () use ($request) {

            $profilePicturePath = null;

            if ($request->hasFile('profile_picture')) {
                $profilePicturePath = $request->file('profile_picture')
                    ->store('profile_pictures', 'public');
            } else {
                $gender = $request->gender; // 1 = male, 2 = female (assuming your convention)

                $defaultImage = ($gender == 1) ? 'profile_pictures/male.png' : 'profile_pictures/female.png';

                $profilePicturePath = $defaultImage;
            }

            $user = User::create([
                'name'            => $request->name,
                'email'           => $request->email,
                'phone'           => $request->phone,
                'date_of_birth'   => $request->date_of_birth,
                'address'         => $request->address,
                'profile_picture' => $profilePicturePath,
                'gender'          => $request->gender,
                'role'            => $request->role,
                'is_active'       => false,
            ]);

            $employee = Employee::create([
                'user_id'         => $user->id,
                'secondary_phone' => $request->secondary_phone,
                'marital_status'  => $request->marital_status,
                'hire_date'       => $request->hire_date,
            ]);

            // Create Dentist record
            $dentist = Dentist::create([
                'employee_id'       => $employee->id,
                'specialization_id' => $request->specialization_id,
                'license_number'    => $request->license_number,
                'years_of_experience'=> $request->years_of_experience,
                'commission_rate' => $request->commission_rate,
                'bio'               => $request->bio ?? null,
            ]);

            // Create related data (education, experience, etc.)
            $this->createRelatedData($employee, $request);

            $user->load([
                'employee.dentist',
                'employee.educations',
                'employee.experiences',
                'employee.trainings',
                'employee.skills'
            ]);

            return response()->json([
                'message' => 'Dentist created successfully',
                'data'    => new EmployeeProfileResource($employee)
            ], 201);
        });
    }

    /**
     * Update Dentist
     */
    public function updateDentist(UpdateEmployeeRequest $request, User $user)
    {
        if (!Gate::allows('manage-employees')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return DB::transaction(function () use ($request, $user) {

            $user->update([
                'name'          => $request->name ?? $user->name,
                'phone'         => $request->phone ?? $user->phone,
                'date_of_birth' => $request->date_of_birth ?? $user->date_of_birth,
                'address'       => $request->address ?? $user->address,
                'gender'        => $request->gender ?? $user->gender,
            ]);

            $employee = $user->employee ?? Employee::create(['user_id' => $user->id]);

            $employee->update([
                'secondary_phone' => $request->secondary_phone,
                'marital_status'  => $request->marital_status,
                // 'salary'          => $request->salary,
                'hire_date'       => $request->hire_date,
            ]);

            // Update or create Dentist record
            Dentist::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'specialization_id'   => $request->specialization_id,
                    'license_number'      => $request->license_number,
                    'years_of_experience' => $request->years_of_experience,
                    'commission_rate'     => $request->commission_rate,
                    'bio'                 => $request->bio ?? null,
                ]
            );

            $this->syncRelatedData($employee, $request);

            $user->load([
                'employee.dentist',
                'employee.educations',
                'employee.experiences',
                'employee.trainings',
                'employee.skills'
            ]);

            return response()->json([
                'message' => 'Dentist updated successfully',
                'data'    => new EmployeeProfileResource($employee)
            ]);
        });
    }



    public function showDentistDetails(Request $request, User $user)
    {
        if (!Gate::allows('manage-employees')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        if ($user->role !== UserRole::DENTIST) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المستخدم ليس طبيباً.'
            ], 422);
        }

        $user->load([
            'employee.dentist.specialization',
            'employee.educations',
            'employee.experiences',
            'employee.trainings',
            'employee.skills',
        ]);

        return response()->json([
            'success' => true,
            'data'    => new EmployeeResource($user),
            /// add the dentist treatments count for the week
            // most treated treatments type for the week
            // the rank of the dentist for the week
            // 
        ]);
    }
    // private function createRelatedData(Employee $employee, Request $request): void
    // {
    //     if ($request->educations) {
    //         foreach ($request->educations as $edu) {
    //             $employee->educations()->create($edu);
    //         }
    //     }

    //     if ($request->experiences) {
    //         foreach ($request->experiences as $exp) {
    //             $employee->experiences()->create($exp);
    //         }
    //     }

    //     if ($request->trainings) {
    //         foreach ($request->trainings as $training) {
    //             $employee->trainings()->create($training);
    //         }
    //     }

    //     if ($request->skills) {
    //         foreach ($request->skills as $skill) {
    //             $employee->skills()->create([
    //                 'skill_name' => $skill['skill_name']
    //             ]);
    //         }
    //     }
    // }

    // private function syncRelatedData(Employee $employee, $request): void
    // {
    //     if ($request->has('educations')) {
    //         $employee->educations()->delete();
    //         foreach ($request->educations as $edu) {
    //             $employee->educations()->create($edu);
    //         }
    //     }

    //     if ($request->has('experiences')) {
    //         $employee->experiences()->delete();
    //         foreach ($request->experiences as $exp) {
    //             $employee->experiences()->create($exp);
    //         }
    //     }

    //     if ($request->has('trainings')) {
    //         $employee->trainings()->delete();
    //         foreach ($request->trainings as $training) {
    //             $employee->trainings()->create($training);
    //         }
    //     }

    //     if ($request->has('skills')) {
    //         $employee->skills()->delete();
    //         foreach ($request->skills as $skill) {
    //             $employee->skills()->create([
    //                 'skill_name' => $skill['skill_name']
    //             ]);
    //         }
    //     }
    // }
}
