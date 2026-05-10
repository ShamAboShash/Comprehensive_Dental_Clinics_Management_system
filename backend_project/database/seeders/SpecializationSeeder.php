<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Specialization;

class SpecializationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specializations = [
            ['name' => 'تقويم أسنان', 'name_en' => 'Orthodontics'],
            ['name' => 'جراحة فم وأسنان', 'name_en' => 'Oral Surgery'],
            ['name' => 'تركيبات أسنان', 'name_en' => 'Prosthodontics'],
            ['name' => 'طب أسنان أطفال', 'name_en' => 'Pediatric Dentistry'],
            ['name' => ' periodontics', 'name_en' => 'Periodontics'],
            ['name' => 'علاج جذور', 'name_en' => 'Endodontics'],
            ['name' => 'تجميل أسنان', 'name_en' => 'Cosmetic Dentistry'],
            ['name' => 'أشعة أسنان', 'name_en' => 'Oral Radiology'],
        ];

        foreach ($specializations as $spec) {
            Specialization::create($spec);
        }
    }
}
