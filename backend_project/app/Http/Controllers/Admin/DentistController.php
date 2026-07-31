<?php

namespace App\Http\Controllers\Admin;

// use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeProfileResource;
use App\Http\Resources\DentistProfileResource;
use App\Models\Dentist;
use App\Models\Employee;
use App\Models\User;
use App\Models\EmployeeEducation;
use App\Models\EmployeeExperience;
use App\Models\EmployeeSkill;
use App\Models\EmployeeTraining;
use App\Models\DentistWorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Enums\UserRole;
use App\Http\Resources\EmployeeResource;
use Illuminate\Validation\Rule;

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

            if ($request->work_schedules) {
            foreach ($request->work_schedules as $schedule) {
                DentistWorkSchedule::create([
                    'dentist_id' => $dentist->id,
                    'day_of_week' => $schedule['day_of_week'],
                    'start_time'  => $schedule['start_time'],
                    'end_time'    => $schedule['end_time'],
                ]);
            }
        }

            $dentist->load([
                'employee.dentist',
                'employee.educations',
                'employee.experiences',
                'employee.trainings',
                'employee.skills',
                'workSchedules',
            ]);

            return response()->json([
                'message' => 'Dentist created successfully',
                'data'    => new DentistProfileResource($dentist)
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

        $dentist = $user->employee?->dentist;

        if (!$dentist) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد سجل طبيب مرتبط بهذا المستخدم.'
            ], 404);
        }

        $dentist->load([
            'employee.user',
            'employee.educations',
            'employee.experiences',
            'employee.trainings',
            'employee.skills',
            'specialization',
            'workSchedules',
        ]);

        return response()->json([
            'success' => true,
            'data'    => new DentistProfileResource($dentist),
        ]);
    }


public function dentistMonthly(Request $request, Dentist $dentist)
{
    Gate::authorize('is-admin');

    $validated = $request->validate([
        'month' => 'nullable|integer|between:1,12',
        'year'  => 'nullable|integer|min:2000|max:2099',
    ]);

    $month = $validated['month'] ?? now()->month;
    $year  = $validated['year'] ?? now()->year;

    $dentist->load([
        'employee.user:id,name,role',
        'specialization:id,name,name_en',
        'employee.attendances' => fn ($q) => $q
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date'),
    ]);

    return response()->json([
        'success' => true,
        'filter'  => [
            'dentist_id' => $dentist->id,
            'month'      => (int) $month,
            'year'       => (int) $year,
        ],
        'data' => [
            'dentist_id'          => $dentist->id,
            'employee_id'         => $dentist->employee?->id,
            'name'                => $dentist->employee?->user?->name,
            'role'                => $dentist->employee?->user?->role?->value,
            'specialization'      => $dentist->specialization?->name,
            'specialization_en'   => $dentist->specialization?->name_en,
            'attendance' => $dentist->employee?->attendances->map(fn ($a) => [
                'id'        => $a->id,
                'date'      => $a->date?->toDateString(),
                'check_in'  => $a->check_in,
                'check_out' => $a->check_out,
                'status'    => $a->status,
                'notes'     => $a->notes,
            ])->values() ?? [],
        ],
    ]);
}
    public function updateDentistWS(Request $request, DentistWorkSchedule $schedule)
{
    Gate::authorize('is-admin');

    $validated = $request->validate([
        'day_of_week' => [
            'required',
            'string',
            Rule::in(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
        ],
        'start_time' => 'sometimes|required|date_format:H:i',
        'end_time'   => 'sometimes|required|date_format:H:i|after:start_time',
    ]);

    // If only end_time is sent, still validate it against the current start_time
    if (isset($validated['end_time']) && !isset($validated['start_time'])) {
        if ($validated['end_time'] <= $schedule->start_time) {
            return response()->json([
                'success' => false,
                'message' => 'وقت الانتهاء يجب أن يكون بعد وقت البدء.',
            ], 422);
        }
    }

    $schedule->update($validated);
    $schedule->load('dentist.employee.user:id,name,email');

    return response()->json([
        'success' => true,
        'message' => 'تم تعديل جدول دوام الطبيب بنجاح',
        'data'    => [
            'id'         => $schedule->id,
            'dentist_id' => $schedule->dentist_id,
            'day'        => $schedule->day_of_week,
            'start_time' => $schedule->start_time,
            'end_time'   => $schedule->end_time,
            'dentist'    => [
                'name'  => $schedule->dentist?->employee?->user?->name,
                'email' => $schedule->dentist?->employee?->user?->email,
            ],
        ],
    ]);
}

    public function bySpecialization(Request $request, $specializationId = null)
    {
        Gate::authorize('is-admin');

        $specId = $specializationId ?? $request->query('specialization_id');

        $query = Dentist::with([
            'employee.user:id,name,email,phone,gender',
            'specialization:id,name,name_en',
        ]);

        if ($specId) {
            $query->where('specialization_id', $specId);
        }

        $dentists = $query->get();

        return response()->json([
            'success' => true,
            'count'   => $dentists->count(),
            'data'    => $dentists,
        ]);
    }
}
