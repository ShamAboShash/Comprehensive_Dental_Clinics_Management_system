<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Specialization;

class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
        $specializations = [
            ['name' => 'عام', 'name_en' => 'General Dentistry'],
            ['name' => 'تقويم أسنان', 'name_en' => 'Orthodontics'],
            ['name' => 'جراحة فم وأسنان', 'name_en' => 'Oral Surgery'],
            ['name' => 'تركيبات أسنان', 'name_en' => 'Prosthodontics'],
            ['name' => 'طب أسنان أطفال', 'name_en' => 'Pediatric Dentistry'],
            ['name' => 'أمراض اللثة', 'name_en' => 'Periodontics'],
            ['name' => 'علاج جذور', 'name_en' => 'Endodontics'],
            ['name' => 'تجميل أسنان', 'name_en' => 'Cosmetic Dentistry'],
            ['name' => 'أشعة أسنان', 'name_en' => 'Oral Radiology'],
            ['name' => 'زراعة أسنان', 'name_en' => 'Dental Implants'],
        ];

        foreach ($specializations as $spec) {
            Specialization::create($spec);
        }
    }
}
