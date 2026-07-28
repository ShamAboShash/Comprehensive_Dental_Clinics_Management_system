<?php

namespace Database\Seeders;

use App\Models\Specialization;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
        $specializations = [
            [
                'name'    => 'تقويم أسنان',
                'name_en' => 'Orthodontics',
            ],
            [
                'name'    => 'جراحة فم وأسنان',
                'name_en' => 'Oral Surgery',
            ],
            [
                'name'    => 'تركيبات أسنان',
                'name_en' => 'Prosthodontics',
            ],
            [
                'name'    => 'طب أسنان الأطفال',
                'name_en' => 'Pediatric Dentistry',
            ],
            [
                'name'    => 'علاج جذور',
                'name_en' => 'Endodontics',
            ],
            [
                'name'    => 'تجميل الأسنان',
                'name_en' => 'Cosmetic Dentistry',
            ],
            [
                'name'    => 'أمراض اللثة',
                'name_en' => 'Periodontics',
            ],
            [
                'name'    => 'أشعة الأسنان',
                'name_en' => 'Dental Radiology',
            ],
        ];

        foreach ($specializations as $item) {
            Specialization::updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }

    }
}
