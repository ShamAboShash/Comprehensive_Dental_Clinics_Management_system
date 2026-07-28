<?php

namespace Database\Seeders;

use App\Models\AppointmentType;
use App\Models\Specialization;
use Illuminate\Database\Seeder;

class AppointmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $appointmentTypes = [
            [
                'name'    => 'كشف عام',
                'name_en' => 'General Checkup',
                'specializations' => [1, 2, 3, 4, 5, 6], // almost all
            ],
            [
                'name'    => 'تقويم أسنان',
                'name_en' => 'Orthodontic Consultation',
                'specializations' => [1], // تقويم
            ],
            [
                'name'    => 'زراعة أسنان',
                'name_en' => 'Dental Implant Consultation',
                'specializations' => [2, 3], // جراحة + تركيبات
            ],
            [
                'name'    => 'علاج أطفال',
                'name_en' => 'Pediatric Consultation',
                'specializations' => [4], // أطفال
            ],
            [
                'name'    => 'علاج جذور',
                'name_en' => 'Root Canal Consultation',
                'specializations' => [5], // علاج جذور
            ],
            [
                'name'    => 'تجميل وتبييض',
                'name_en' => 'Cosmetic Consultation',
                'specializations' => [6], // تجميل
            ],
            [
                'name'    => 'تركيبات أسنان',
                'name_en' => 'Prosthodontic Consultation',
                'specializations' => [3], // تركيبات
            ],
            [
                'name'    => 'جراحة فم',
                'name_en' => 'Oral Surgery Consultation',
                'specializations' => [2], // جراحة
            ],
        ];

        foreach ($appointmentTypes as $data) {
            $type = AppointmentType::updateOrCreate(
                ['name' => $data['name']],
                [
                    'name'    => $data['name'],
                    'name_en' => $data['name_en'],
                ]
            );

            // Attach specializations
            $type->specializations()->sync($data['specializations']);
        }

    }
}
