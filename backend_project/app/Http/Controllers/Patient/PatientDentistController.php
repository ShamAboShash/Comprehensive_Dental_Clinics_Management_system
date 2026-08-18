<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\patient\DentistResource;
use App\Models\AppointmentType;
use App\Models\Dentist;
use Illuminate\Support\Facades\Gate;

class PatientDentistController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-patient');
    }
    public function index($specialization_id = null)
    {
        $query = Dentist::with(['employee.user', 'specialization']);

        if ($specialization_id !== null) {
            $query->where('specialization_id', $specialization_id);
        }

        $dentists = $query->get();

        return response()->json([
            'success' => true,
            'count'   => $dentists->count(),
            'data'    => DentistResource::collection($dentists),
        ]);
    }

    /**
     * 10. عرض طبيب محدد
     */
    public function show(Dentist $dentist)
    {
        $dentist->load(['employee.user', 'specialization']);

        return response()->json([
            'success' => true,
            'data'    => new DentistResource($dentist),
        ]);
    }


    public function byAppointmentType(int $appointmentTypeId)
{
    // Check appointment type exists
    $appointmentType =AppointmentType::with('specializations')->find($appointmentTypeId);

    if (!$appointmentType) {
        return response()->json([
            'success' => false,
            'message' => 'نوع الموعد غير موجود',
        ], 404);
    }

    $specializationIds = $appointmentType->specializations->pluck('id');

    if ($specializationIds->isEmpty()) {
        return response()->json([
            'success' => true,
            'data'    => [],
            'message' => 'لا يوجد أطباء مرتبطين بهذا النوع من المواعيد',
        ]);
    }

    $dentists = Dentist::with(['employee.user', 'specialization', 'clinics'])
        ->whereIn('specialization_id', $specializationIds)
        ->whereHas('employee.user', function ($q) {
            $q->where('is_active', true);
        })
        ->get();

    return response()->json([
        'success' => true,
        'data'    => DentistResource::collection($dentists),
    ]);
}
}
