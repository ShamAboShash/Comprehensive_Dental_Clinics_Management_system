<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\StoreAppointmentRequest;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AppointmentController extends Controller
{
    // ---------------------------------------------------------------
    // GET /secretary/appointments
    // Show all appointments (for testing)
    // ---------------------------------------------------------------
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('is-secretary');

        $appointments = Appointment::with(['patient.user', 'dentist.employee.user', 'appointmentType'])
            ->orderBy('appointment_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $appointments->count(),
            'data'    => $appointments->map(fn($a) => [
                'id'               => $a->id,
                'patient'          => $a->patient->user->name,
                'dentist'          => $a->dentist->employee->user->name,
                'type'             => $a->type,
                'status'           => $a->status,
                'appointment_time' => $a->appointment_time?->toDateTimeString(),
                'treatment_id'     => $a->treatment_id,
                'notes'            => $a->notes,
            ]),
        ]);
    }

    // ---------------------------------------------------------------
    // POST /secretary/appointments
    // Create a new appointment
    // ---------------------------------------------------------------
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        Gate::authorize('is-secretary');

        $appointment = Appointment::create([
            'patient_id'          => $request->patient_id,
            'dentist_id'          => $request->dentist_id,
            'appointment_type_id' => $request->appointment_type_id,
            'treatment_id'        => $request->treatment_id,
            'type'                => $request->type,
            'status'              => $request->status ?? 'approved',
            'appointment_time'    => $request->appointment_time,
            'notes'               => $request->notes,
        ]);

        $appointment->load(['patient.user', 'dentist.employee.user', 'appointmentType']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الموعد بنجاح.',
            'data'    => [
                'id'               => $appointment->id,
                'patient'          => $appointment->patient->user->name,
                'dentist'          => $appointment->dentist->employee->user->name,
                'type'             => $appointment->type,
                'status'           => $appointment->status,
                'appointment_time' => $appointment->appointment_time?->toDateTimeString(),
                'treatment_id'     => $appointment->treatment_id,
                'notes'            => $appointment->notes,
            ],
        ], 201);
    }
}
