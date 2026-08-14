<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\Dentist;
use App\Models\Treatment;
use App\Models\TreatmentType;
use Illuminate\Database\Seeder;

class TreatmentSeeder extends Seeder
{
    public function run(): void
    {
        $patients       = Patient::orderBy('id')->get();
        $dentists       = Dentist::orderBy('id')->get();
        $treatmentTypes = TreatmentType::all();

        if ($patients->isEmpty() || $dentists->isEmpty() || $treatmentTypes->isEmpty()) {
            $this->command->warn('Skipping TreatmentSeeder: Missing patients, dentists or treatment types.');
            return;
        }

        // Main dentist (id order first) will own the majority of August treatments
        // so that later sessions give him >5 appointments across >2 patients
        $mainDentist   = $dentists->first();
        $otherDentists = $dentists->slice(1)->values();

        // Target >5 patients (use 7) so invoices can also cover more than 5 patients
        $targetPatients = $patients->take(7);

        $created = 0;

        foreach ($targetPatients as $index => $patient) {
            // First 4 patients → main dentist; remaining → rotate other dentists
            if ($index < 4) {
                $dentist = $mainDentist;
            } else {
                $dentist = $otherDentists->isNotEmpty()
                    ? $otherDentists[$index % $otherDentists->count()]
                    : $mainDentist;
            }

            $type = $treatmentTypes[$index % $treatmentTypes->count()];

            // Realistic session counts for a multi-session treatment
            $totalNeeded      = 2 + ($index % 3); // 2, 3 or 4
            $alreadyCompleted = 0;                 // sessions seeder will set the real value

            Treatment::updateOrCreate(
                [
                    'patient_id'        => $patient->id,
                    'dentist_id'        => $dentist->id,
                    'treatment_type_id' => $type->id,
                ],
                [
                    'total_sessions_needed' => $totalNeeded,
                    'sessions_completed'    => $alreadyCompleted,
                    'status'                => 'ongoing',
                    'notes'                 => 'علاج تجريبي لشهر آب 2026 - ' . ($type->name ?? 'نوع علاج'),
                ]
            );
            $created++;
        }

        // Extra treatments for main dentist on the first 3 patients
        // (guarantees he ends up with enough appointments across multiple patients)
        foreach ($targetPatients->take(3) as $i => $patient) {
            $type = $treatmentTypes->where('id', '!=', ($i + 1))->first()
                ?? $treatmentTypes->random();

            Treatment::firstOrCreate(
                [
                    'patient_id'        => $patient->id,
                    'dentist_id'        => $mainDentist->id,
                    'treatment_type_id' => $type->id,
                ],
                [
                    'total_sessions_needed' => 2 + ($i % 2),
                    'sessions_completed'    => 0,
                    'status'                => 'ongoing',
                    'notes'                 => 'علاج إضافي للطبيب الرئيسي - آب 2026',
                ]
            );
            $created++;
        }

        $this->command->info("✅ TreatmentSeeder: {$created} treatments created/updated (focused on ≥6 patients + main dentist heavy load for August data).");
    }
}
