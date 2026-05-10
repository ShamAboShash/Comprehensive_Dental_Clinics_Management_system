<?php

namespace Database\Seeders;

use App\Models\Medication;
use App\Models\Patient;
use App\Models\PatientMedication;
use Illuminate\Database\Seeder;

class PatientMedicationSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::all();
        $medications = Medication::all();

        if ($medications->isEmpty()) {
            $this->command->warn('No medications found. Please run MedicationSeeder first.');
            return;
        }

        foreach ($patients as $patient) {
            $count = rand(1, 4); // Each patient takes 1 to 4 medications
            $used = [];

            for ($i = 0; $i < $count; $i++) {
                $med = $medications->random();

                // Avoid duplicate medication for same patient
                if (in_array($med->id, $used)) continue;

                PatientMedication::create([
                    'patient_id'    => $patient->id,
                    'medication_id' => $med->id,
                    'dosage'        => rand(0,1) ? '500mg' : '250mg',
                    'frequency'     => ['مرة يومياً', 'مرتين يومياً', 'ثلاث مرات يومياً'][rand(0,2)],
                    'notes'         => rand(0,1) ? 'بعد الأكل' : null,
                ]);

                $used[] = $med->id;
            }
        }

        // $this->command->info('✅ Patient Medications seeded successfully!');
    }
}
