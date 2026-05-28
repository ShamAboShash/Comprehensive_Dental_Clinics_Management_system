<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\PatientChronicDisease;
use App\Models\DiseaseCategory;
use Illuminate\Database\Seeder;

class PatientChronicDiseaseSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::all();

        // Get all categories with their IDs
        $categories = DiseaseCategory::all()->keyBy('name');

        $commonDiseases = [
            [
                'category_name' => 'أمراض القلب',
                'problem_name'  => 'ضغط الدم المرتفع',
                'severity'      => 'moderate'
            ],
            [
                'category_name' => 'أمراض السكري',
                'problem_name'  => 'السكري من النوع 2',
                'severity'      => 'moderate'
            ],
            [
                'category_name' => 'أمراض الرئة',
                'problem_name'  => 'الربو',
                'severity'      => 'mild'
            ],
            [
                'category_name' => 'أمراض الأسنان',
                'problem_name'  => 'نزيف اللثة المزمن',
                'severity'      => 'moderate'
            ],
            [
                'category_name' => 'حساسية',
                'problem_name'  => 'حساسية من الليدوكايين',
                'severity'      => 'severe'
            ],
            [
                'category_name' => 'أمراض القلب',
                'problem_name'  => 'قصور في صمامات القلب',
                'severity'      => 'moderate'
            ],
            [
                'category_name' => 'أخرى',
                'problem_name'  => 'نقص فيتامين D',
                'severity'      => 'mild'
            ],
        ];

        foreach ($patients as $patient) {
            // Each patient gets 1 to 3 random diseases
            $diseasesToAdd = collect($commonDiseases)
                                ->random(rand(1, 3))
                                ->values()
                                ->all();

            foreach ($diseasesToAdd as $disease) {
                $category = $categories->get($disease['category_name']);

                if ($category) {
                    PatientChronicDisease::firstOrCreate(
                        [
                            'patient_id'   => $patient->id,
                            'category_id'  => $category->id,
                            'problem_name' => $disease['problem_name'],
                        ],
                        [
                            'severity' => $disease['severity'],
                            'details'  => rand(0, 1) ? 'يتابع العلاج بانتظام' : null,
                        ]
                    );
                }
            }
        }

        $this->command->info('✅ Patient Chronic Diseases seeded successfully!');
    }
}
