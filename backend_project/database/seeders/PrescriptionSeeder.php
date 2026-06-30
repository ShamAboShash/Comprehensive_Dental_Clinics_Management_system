<?php

namespace Database\Seeders;

use App\Models\TreatmentSession;
use App\Models\Prescription;
use App\Models\Medication;
use Illuminate\Database\Seeder;

class PrescriptionSeeder extends Seeder
{
    public function run(): void
    {
        $sessions    = TreatmentSession::all();
        $medications = Medication::all();

        if ($sessions->isEmpty() || $medications->isEmpty()) {
            $this->command->warn('Skipping PrescriptionSeeder: No sessions or medications found.');
            return;
        }

        foreach ($sessions->take(10) as $session) {
            $prescription = Prescription::create([
                'session_id' => $session->id,
                'dentist_id' => $session->dentist_id,
                'patient_id' => $session->treatment->patient_id,
                'notes'      => 'علاج بعد الجلسة',
            ]);

            // Add 1 to 3 medications via the pivot table
            $randomMeds = $medications->random(rand(1, 3));

            $pivotData = collect($randomMeds)->mapWithKeys(fn($med) => [
                $med->id => [
                    'dosage'    => '1 قرص',
                    'frequency' => '3 مرات يومياً',
                    'duration'  => '7 أيام',
                ],
            ]);

            $prescription->medications()->attach($pivotData);
        }
    }
}
