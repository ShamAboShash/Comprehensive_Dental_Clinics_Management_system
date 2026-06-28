<?php

namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dentist\WorkScheduleResource;
use App\Models\DentistWorkSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkScheduleController extends Controller
{
    // ---------------------------------------------------------------
    // GET /dentist/schedule
    // API #2 — Show dentist's weekly work schedule
    // ---------------------------------------------------------------
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('is-dentist');

        $dentistId = $request->user()->employee->dentist->id;

        $schedules = DentistWorkSchedule::where('dentist_id', $dentistId)
            ->orderByRaw("FIELD(day_of_week,
                'saturday','sunday','monday','tuesday',
                'wednesday','thursday','friday'
            )")
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $schedules->count(),
            'data'    => WorkScheduleResource::collection($schedules),
        ]);
    }
}
