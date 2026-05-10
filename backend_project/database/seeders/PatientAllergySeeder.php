<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\PatientAllergy;
use Illuminate\Database\Seeder;

class PatientAllergySeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::all();
        $commonAllergies = [
                    'البنسلين', 'الأسبرين', 'اللاتكس', 'الفول السوداني',
                    'السمك', 'البيض', 'الحليب', 'الليدوكايين',
                    'مضادات الالتهاب', 'السلفا', 'الأدوية المخدرة'
                ];
        foreach ($patients as $patient) {
            // Give each patient 2 to 4 different allergies
            $allergiesToAdd = collect($commonAllergies)
                                ->random(rand(2, 4))
                                ->values()
                                ->all();

            foreach ($allergiesToAdd as $allergy) {
                PatientAllergy::firstOrCreate(
                    [
                        'patient_id'   => $patient->id,
                        'allergy_name' => $allergy,
                    ],
                    [
                        // You can add more fields here if needed later
                    ]
                );
            }
        }

        // $this->command->info('✅ Patient Allergies seeded successfully!');
    }
}
