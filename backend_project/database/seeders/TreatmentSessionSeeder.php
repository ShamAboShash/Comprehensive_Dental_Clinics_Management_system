<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Dentist;
use App\Models\Treatment;
use App\Models\TreatmentSession;
use Illuminate\Database\Seeder;

class TreatmentSessionSeeder extends Seeder
{
    public function run(): void
    {
        $treatments      = Treatment::all();
        $dentists        = Dentist::all();
        $appointmentType = AppointmentType::first(); // use a real type if one exists

        if ($treatments->isEmpty() || $dentists->isEmpty()) {
            $this->command->warn('Skipping TreatmentSessionSeeder: Missing treatments or dentists.');
            return;
        }

        foreach ($treatments as $treatment) {
            $dentistId  = $treatment->dentist_id;
            $patientId  = $treatment->patient_id;

            // Number of sessions to create for this treatment
            $sessionCount = min(max((int) ($treatment->total_sessions_needed ?? 2), 1), 3);

            // Base time anchor — each session moves FORWARD in time from this point
            // so session_number order always matches chronological order
            $sessionAnchor = now()->subDays(rand(30, 90));

            $completedCount = 0;

            for ($i = 1; $i <= $sessionCount; $i++) {

                // Each session is scheduled some days after the previous one
                $sessionAnchor = $sessionAnchor->copy()->addDays(rand(3, 10));

                // Appointment time matches the session's real-world day
                $appointmentTime = $sessionAnchor->copy()->setTime(rand(9, 16), [0, 15, 30, 45][array_rand([0, 15, 30, 45])]);
                $status = rand(0,1) ? 'completed' : 'pending'; // Randomly mark some sessions as completed or pending

                $appointment = Appointment::create([
                    'patient_id'           => $patientId,
                    'dentist_id'           => $dentistId,
                    'appointment_type_id'  => $appointmentType?->id,
                    'treatment_id'         => $treatment->id,
                    'type'                 => 'continue_treatment',
                    'status'               => $status,
                    'appointment_time'     => $appointmentTime,
                    'notes'                => 'موعد تم إنشاؤه تلقائياً لجلسة العلاج',
                ]);

                // actual_start_time / actual_end_time always derived correctly
                // start = appointment time, end = start + a realistic session duration
                $startTime = $appointmentTime->copy();
                $endTime   = $appointmentTime->copy()->addMinutes(rand(30, 60));

                TreatmentSession::create([
                    'treatment_id'       => $treatment->id,
                    'appointment_id'     => $appointment->id,
                    'dentist_id'         => $dentistId,
                    'session_number'     => $i,
                    'actual_start_time'  => $startTime,
                    'actual_end_time'    => $endTime,
                    'status'             => 'completed',
                    'notes'              => 'جلسة تم إنشاؤها تلقائياً للبيانات التجريبية',
                ]);

                $completedCount++;
            }

            // Keep treatment.sessions_completed in sync with what we actually created
            $treatment->update(['sessions_completed' => $completedCount]);

            // If all needed sessions are done, mark the treatment completed
            if ($completedCount >= $treatment->total_sessions_needed) {
                $treatment->update(['status' => 'completed']);
            }
        }
    }
}
