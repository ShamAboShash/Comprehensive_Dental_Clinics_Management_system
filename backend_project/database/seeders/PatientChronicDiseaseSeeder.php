<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\PatientChronicDisease;
use Illuminate\Database\Seeder;

class PatientChronicDiseaseSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::all();

        $commonDiseases = [
            ['category' => 'chronic', 'problem_name' => 'ضغط الدم المرتفع', 'severity' => 'moderate'],
            ['category' => 'chronic', 'problem_name' => 'السكري', 'severity' => 'moderate'],
            ['category' => 'chronic', 'problem_name' => 'الربو', 'severity' => 'mild'],
            ['category' => 'dental', 'problem_name' => 'نزيف اللثة المزمن', 'severity' => 'moderate'],
            ['category' => 'anesthesia', 'problem_name' => 'حساسية من المخدر الموضعي', 'severity' => 'severe'],
            ['category' => 'chronic', 'problem_name' => 'أمراض القلب', 'severity' => 'moderate'],
        ];

        foreach ($patients as $patient) {
            // Each patient gets 1 to 3 different diseases
            $diseasesToAdd = collect($commonDiseases)
                                ->random(rand(1, 3))
                                ->values()
                                ->all();

            foreach ($diseasesToAdd as $disease) {
                PatientChronicDisease::firstOrCreate(
                    [
                        'patient_id'   => $patient->id,
                        'category'     => $disease['category'],
                        'problem_name' => $disease['problem_name'],
                    ],
                    [
                        'severity' => $disease['severity'],
                        'details'  => rand(0, 1) ? 'يأخذ دواء بانتظام' : null,
                    ]
                );
            }
        }

        // $this->command->info('✅ Patient Chronic Diseases seeded successfully!');
    }
}
