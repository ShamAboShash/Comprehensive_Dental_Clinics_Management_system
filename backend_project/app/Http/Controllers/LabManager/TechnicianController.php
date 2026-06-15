<?php

// namespace App\Http\Controllers\LabManager;

// use App\Http\Controllers\Controller;
// use App\Http\Resources\TechnicianResource;
// use App\Models\User;
// use App\Models\LabOrder;
// use App\Enums\UserRole;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Gate;
// use Carbon\Carbon;
// use App\Http\Requests\LabManager\UpdateTechnicianWorkTimeRequest;

// class TechnicianController extends Controller
// {
//     public function __construct()
//     {
//         Gate::authorize('is-lab-manager');
//     }

//     public function showAllTechnicians(Request $request)
//     {
//         if (!Gate::allows('show-technicians')) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'غير مصرح لك بالوصول.'
//             ], 403);
//         }

//     $technicians = User::where('role', UserRole::TECHNICIAN->value)
//         ->with(['employee'])
//         ->get();

//         return response()->json([
//             'success' => true,
//             'data'    => TechnicianResource::collection($technicians),
//         ]);
//     }

//     // // GET /lab/technicians
//     // public function index()
//     // {
//     //     $technicians = User::where('role', UserRole::TECHNICIAN->value)
//     //         ->with('employee')
//     //         ->get()
//     //         ->map(fn($user) => $this->formatTechnician($user));

//     //     return response()->json([
//     //         'success' => true,
//     //         'data'    => $technicians,
//     //     ]);
//     // }

//     // GET /lab/technicians/{id}
//     public function show($id)
//     {
//         $user = User::where('role', UserRole::TECHNICIAN)
//             ->with(['employee', 'employee.educations', 'employee.experiences', 'employee.skills'])
//             ->find($id);

//         if (!$user) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'الفني غير موجود',
//             ], 404);
//         }

//         return response()->json([
//             'success' => true,
//             'data'    => new TechnicianResource($user),
//         ]);
//     }

//     // PATCH /lab/technicians/{id}/work-time
//     public function updateWorkTime(UpdateTechnicianWorkTimeRequest $request, $id)
//     {
//         $user = User::where('role', UserRole::TECHNICIAN)->find($id);

//         if (!$user) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'الفني غير موجود',
//             ], 404);
//         }

//         $user->employee->update([
//             'shift_start' => $request->shift_start,
//             'shift_end'   => $request->shift_end,
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'تم تحديث وقت الدوام بنجاح',
//             'data'    => $user->fresh('employee'),
//         ]);
//     }

//    // GET /lab/technicians/performance
//     public function performance()
//     {
//         $from = request('from') ? Carbon::parse(request('from')) : Carbon::now()->startOfMonth();
//         $to   = request('to')   ? Carbon::parse(request('to'))   : Carbon::now()->endOfMonth();

//         $technicians = User::where('role', UserRole::TECHNICIAN)
//             ->with('employee')
//             ->get();

//         $report = $technicians->map(function ($user) use ($from, $to) {
//             $orders = LabOrder::where('technician_id', $user->id)
//                 ->whereBetween('updated_at', [$from, $to]);

//             return [
//                 'technician_id'    => $user->id,
//                 'name'             => $user->name,
//                 'shift_start'      => $user->employee->shift_start ?? null,
//                 'shift_end'        => $user->employee->shift_end ?? null,
//                 'total_assigned'   => (clone $orders)->count(),
//                 'total_completed'  => (clone $orders)->where('status', 'completed')->count(),
//                 'total_cancelled'  => (clone $orders)->where('status', 'cancelled')->count(),
//                 'total_in_progress'=> (clone $orders)->where('status', 'in_progress')->count(),
//                 'period' => [
//                     'from' => $from->toDateString(),
//                     'to'   => $to->toDateString(),
//                 ],
//             ];
//         });

//         return response()->json([
//             'success' => true,
//             'data'    => $report,
//         ]);
//     }

//     // ─── Private Helper ───────────────────────────────────────────
//     // private function formatTechnician(User $user): array
//     // {
//     //     return [
//     //         'id'          => $user->id,
//     //         'name'        => $user->name,
//     //         'email'       => $user->email,
//     //         'phone'       => $user->phone,
//     //         'shift_start' => $user->employee->shift_start ?? null,
//     //         'shift_end'   => $user->employee->shift_end ?? null,
//     //         'is_active'   => $user->is_active,
//     //         'employee'    => $user->employee,
//     //     ];
//     // }
// }



namespace App\Http\Controllers\LabManager;

use App\Http\Controllers\Controller;
use App\Http\Resources\TechnicianResource;
use App\Models\Employee;
use App\Models\LabOrder;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class TechnicianController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-lab-manager');
    }

   // GET /lab/technicians
public function index()
{
    $technicians = Employee::whereHas('user', fn($q) =>
        $q->where('role', UserRole::TECHNICIAN)
    )
    ->with(['user', 'workSchedules'])
    // Count active orders for this technician
    ->withCount(['labOrders as active_orders_count' => fn($q) =>
        $q->where('status', 'in_progress')
    ])
    ->get();

    return response()->json([
        'success' => true,
        'data'    => TechnicianResource::collection($technicians),
    ]);
}

    // GET /lab/technicians/{id}
    public function show($id)
    {
        $technician = Employee::whereHas('user', fn($q) =>
            $q->where('role', UserRole::TECHNICIAN)
        )->with(['user', 'workSchedules', 'educations', 'experiences', 'skills'])
         ->find($id);

        if (!$technician) {
            return response()->json([
                'success' => false,
                'message' => 'الفني غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new TechnicianResource($technician),
        ]);
    }

    // PUT /lab/technicians/{id}/schedule
    public function updateWorkTime(Request $request, $id)
    {
        $technician = Employee::whereHas('user', fn($q) =>
            $q->where('role', UserRole::TECHNICIAN)
        )->find($id);

        if (!$technician) {
            return response()->json([
                'success' => false,
                'message' => 'الفني غير موجود',
            ], 404);
        }

        $request->validate([
            'schedule'               => 'required|array|min:1',
            'schedule.*.day_of_week' => 'required|in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            'schedule.*.start_time'  => 'required|date_format:H:i',
            'schedule.*.end_time'    => 'required|date_format:H:i|after:schedule.*.start_time',
        ]);

        // Replace all schedules for this technician
        $technician->workSchedules()->delete();

        foreach ($request->schedule as $entry) {
            $technician->workSchedules()->create([
                'day_of_week' => $entry['day_of_week'],
                'start_time'  => $entry['start_time'],
                'end_time'    => $entry['end_time'],
            ]);
        }

        $technician->load(['user', 'workSchedules']);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث جدول العمل بنجاح',
            'data'    => new TechnicianResource($technician),
        ]);
    }

    // GET /lab/technicians/performance
    public function performance()
    {
        $from = request('from') ? Carbon::parse(request('from')) : Carbon::now()->startOfMonth();
        $to   = request('to')   ? Carbon::parse(request('to'))   : Carbon::now()->endOfMonth();

        $technicians = Employee::whereHas('user', fn($q) =>
            $q->where('role', UserRole::TECHNICIAN)
        )->with(['user', 'workSchedules'])->get();

        $report = $technicians->map(function ($technician) use ($from, $to) {
            $orders = LabOrder::where('technician_id', $technician->id)
                ->whereBetween('updated_at', [$from, $to]);

            $todaySchedule = $technician->workSchedules
                ->firstWhere('day_of_week', strtolower(now()->format('l')));

            return [
                'technician_id'     => $technician->id,
                'name'              => $technician->user->name,
                'shift_start'       => $todaySchedule?->start_time,
                'shift_end'         => $todaySchedule?->end_time,
                'total_assigned'    => (clone $orders)->count(),
                'total_completed'   => (clone $orders)->where('status', 'completed')->count(),
                // 'total_cancelled'   => (clone $orders)->where('status', 'cancelled')->count(),
                'total_in_progress' => (clone $orders)->where('status', 'in_progress')->count(),
                'period'            => [
                    'from' => $from->toDateString(),
                    'to'   => $to->toDateString(),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $report,
        ]);
    }
}
