<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\StoreEmployeeRequest;
use App\Models\Dentist;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\EmployeeExperience;
use App\Models\EmployeeSkill;
use App\Models\EmployeeTraining;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function store(StoreEmployeeRequest $request)
    {
        return DB::transaction(function () use ($request) {

            // 1. Create User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'address' => $request->address,
                'gender' => $request->gender,
                'role' => $request->role,
                'password' => bcrypt('password'), // Temporary password - should be changed later
            ]);

            // 2. Create Employee
            $employee = Employee::create([
                'user_id' => $user->id,
                'secondary_phone' => $request->secondary_phone,
                'marital_status' => $request->marital_status,
                'salary' => $request->salary,
                'hire_date' => $request->hire_date,
                'is_active' => true,
            ]);

            // 3. Create related data
            $this->createRelatedData($employee, $request);

            // 4. If Dentist → create Dentist record
            if ($request->role === UserRole::DENTIST->value) {
                Dentist::create([
                    'employee_id' => $employee->id,
                    'specialization_id' => $request->specialization_id,
                    'work_hours' => $request->work_hours,
                    'license_number' => $request->license_number,
                    'years_of_experience' => $request->years_of_experience,
                ]);
            }

            $user->load(['employee.dentist', 'employee.educations', 'employee.experiences', 'employee.trainings', 'employee.skills']);

            return response()->json([
                'message' => 'Employee created successfully',
                'data' => new EmployeeResource($user)
            ], 201);
        });
    }

    private function createRelatedData(Employee $employee, $request): void
    {
        // Educations
        if ($request->educations) {
            foreach ($request->educations as $edu) {
                EmployeeEducation::create([
                    'employee_id' => $employee->id,
                    ...$edu
                ]);
            }
        }

        // Experiences
        if ($request->experiences) {
            foreach ($request->experiences as $exp) {
                EmployeeExperience::create([
                    'employee_id' => $employee->id,
                    ...$exp
                ]);
            }
        }

        // Trainings
        if ($request->trainings) {
            foreach ($request->trainings as $training) {
                EmployeeTraining::create([
                    'employee_id' => $employee->id,
                    ...$training
                ]);
            }
        }

        // Skills
        if ($request->skills) {
            foreach ($request->skills as $skill) {
                EmployeeSkill::create([
                    'employee_id' => $employee->id,
                    'skill_name' => $skill['skill_name']
                ]);
            }
        }
    }


    public function update(UpdateEmployeeRequest $request, User $employee)
    {
        return DB::transaction(function () use ($request, $employee) {

            // 1. Update User basic info
            $employee->update([
                'name' => $request->name ?? $employee->name,
                // 'email'         => $request->email ?? $employee->email,
                'phone' => $request->phone ?? $employee->phone,
                'date_of_birth' => $request->date_of_birth ?? $employee->date_of_birth,
                'address' => $request->address ?? $employee->address,
                'gender' => $request->gender ?? $employee->gender,
                'role' => $request->role ?? $employee->role,
            ]);

            // 2. Update or Create Employee record
            $empData = $employee->employee ?? Employee::create(['user_id' => $employee->id]);

            $empData->update([
                'secondary_phone' => $request->secondary_phone,
                'marital_status' => $request->marital_status,
                'salary' => $request->salary,
                'hire_date' => $request->hire_date,
                'is_active' => $request->is_active ?? $empData->is_active,
            ]);

            // 3. Update related data (replace existing)
            $this->syncRelatedData($empData, $request);

            // 4. Handle Dentist specific data
            if ($employee->role === UserRole::DENTIST->value) {
                Dentist::updateOrCreate(
                    ['employee_id' => $empData->id],
                    [
                        'specialization_id' => $request->specialization_id,
                        'work_hours' => $request->work_hours,
                        'license_number' => $request->license_number,
                        'years_of_experience' => $request->years_of_experience,
                    ]
                );
            } // If role changed from Dentist to something else → delete dentist record
            elseif ($employee->wasChanged('role') && $employee->role !== UserRole::DENTIST->value) {
                Dentist::where('employee_id', $empData->id)->delete();
            }

            // Reload with relations
            $employee->load([
                'employee.dentist',
                'employee.educations',
                'employee.experiences',
                'employee.trainings',
                'employee.skills'
            ]);

            return response()->json([
                'message' => 'Employee updated successfully',
                'data' => new EmployeeResource($employee)
            ]);
        });
    }

    /**
     * Sync related data (Education, Experience, Training, Skills)
     */
    private function syncRelatedData(Employee $employee, $request): void
    {
        // Educations
        if ($request->has('educations')) {
            $employee->educations()->delete();
            foreach ($request->educations as $edu) {
                $employee->educations()->create($edu);
            }
        }

        // Experiences
        if ($request->has('experiences')) {
            $employee->experiences()->delete();
            foreach ($request->experiences as $exp) {
                $employee->experiences()->create($exp);
            }
        }

        // Trainings
        if ($request->has('trainings')) {
            $employee->trainings()->delete();
            foreach ($request->trainings as $training) {
                $employee->trainings()->create($training);
            }
        }

        // Skills
        if ($request->has('skills')) {
            $employee->skills()->delete();
            foreach ($request->skills as $skill) {
                $employee->skills()->create([
                    'skill_name' => $skill['skill_name']
                ]);
            }
        }
    }



}
