<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Treatment;
use App\Models\TreatmentSession;
use App\Models\User;
use Illuminate\Database\Seeder;


class TreatmentSessionSeeder extends Seeder
{
    public function run(): void
    {
        $treatments = Treatment::all();
        $dentists = Dentist::all();

        if ($treatments->isEmpty() || $dentists->isEmpty()) {
            $this->command->warn('Skipping TreatmentSessionSeeder: Missing treatments or dentists.');
            return;
        }

        $createdByUserId = User::query()->value('id');

        if (!$createdByUserId) {
            $this->command->warn('Skipping TreatmentSessionSeeder: No users found for appointments.created_by.');
            return;
        }

        foreach ($treatments as $treatment) {
            $dentistId = $treatment->dentist_id;


            // create between 1 and 3 sessions for each treatment
            $sessionCount = min(max((int)($treatment->total_sessions_needed ?? 2), 1), 3);

            for ($i = 1; $i <= $sessionCount; $i++) {
                // Appointment is required by migration (unique appointment_id in treatment_sessions)
                // If your appointments table has required fields/constraints, adjust here.
                $appointmentTypeId = null;
                $patientId = $treatment->patient_id;

                // Appointment migration doesn't use `date`/`time` columns; it uses `appointment_time`.
                // Also it requires `created_by` (FK -> users).
                $appointment = Appointment::create([
                    'patient_id' => $patientId,
                    'dentist_id' => $dentistId,
                    'appointment_type_id' => $appointmentTypeId,
                    'treatment_id' => $treatment->id,
                    'type' => 'continue_treatment',

                    'status' => 'completed',
                    'appointment_time' => now()->addDays(rand(1, 30)),
                    'notes' => 'Generated appointment for treatment session seeding',
                ]);


                TreatmentSession::create([
                    'treatment_id' => $treatment->id,
                    'appointment_id' => $appointment->id,
                    'dentist_id' => $dentistId,
                    'session_number' => $i,
                    'actual_start_time' => now()->subDays(rand(1, 60)),
                    'actual_end_time' => now()->subDays(rand(0, 59)),
                    'status' => 'completed',
                    'notes' => 'Generated session for seeding',
                ]);
            }
        }

    }
}

