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

            $user->load([
                'employee.dentist',
                'employee.educations',
                'employee.experiences',
                'employee.trainings',
                'employee.skills',
                'employee.workSchedules',
            ]);

            return response()->json([
                'message' => 'Dentist created successfully',
                'data'    => new DentistProfileResource($employee)
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


    public function monthlyDentists(Request $request)
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year'  => 'nullable|integer|min:2000|max:2099',
        ]);

        $month = $validated['month'] ?? now()->month;
        $year  = $validated['year'] ?? now()->year;

        $dentists = Dentist::with([
            'employee.user:id,name,email,phone',
            'specialization:id,name,name_en',
            'employee.attendances' => fn ($q) => $q->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->orderBy('date'),
        ])->get();

        return response()->json([
            'success'        => true,
            'filter'         => ['month' => (int) $month, 'year' => (int) $year],
            'total_dentists' => $dentists->count(),
            'data'           => $dentists,
        ]);
    }

    public function updateDentistWS(Request $request, DentistWorkSchedule $schedule)
    {
        Gate::authorize('is-admin');

        $validated = $request->validate([
            'day_of_week' => ['sometimes', 'required', 'string', Rule::in(['saturday','sunday','monday','tuesday','wednesday','thursday','friday'])],
            'start_time'  => 'sometimes|required|date_format:H:i',
            'end_time'    => 'sometimes|required|date_format:H:i|after:start_time',
        ]);

        $dayOfWeek = $validated['day_of_week'] ?? $schedule->day_of_week;
        $startTime = $validated['start_time'] ?? $schedule->start_time;

        $exists = DentistWorkSchedule::where('dentist_id', $schedule->dentist_id)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', $startTime)
            ->where('id', '!=', $schedule->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد وردية أخرى تبدأ في نفس الوقت لهذا اليوم للطبيب.',
            ], 422);
        }

        $schedule->update($validated);
        $schedule->load('dentist.employee.user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل جدول دوام الطبيب بنجاح',
            'data'    => $schedule,
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
