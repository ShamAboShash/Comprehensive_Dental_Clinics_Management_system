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
        $patients       = Patient::all();
        $dentists       = Dentist::all();
        $treatmentTypes = TreatmentType::all();

        if ($patients->isEmpty() || $dentists->isEmpty() || $treatmentTypes->isEmpty()) {
            $this->command->warn('Skipping TreatmentSeeder: Missing patients, dentists or treatment types.');
            return;
        }

        foreach ($patients->take(8) as $patient) {
            $dentist = $dentists->random();
            $type    = $treatmentTypes->random();

            Treatment::create([
                'patient_id'             => $patient->id,
                'dentist_id'             => $dentist->id,
                'treatment_type_id'      => $type->id,
                'total_sessions_needed'  => rand(2, 6),
                'sessions_completed'     => 0, // session seeder will increment this correctly
                'status'                 => 'ongoing',
                'notes'                  => 'تم بدء العلاج بتاريخ ' . now()->format('Y-m-d'),
            ]);
        }
    }
}
