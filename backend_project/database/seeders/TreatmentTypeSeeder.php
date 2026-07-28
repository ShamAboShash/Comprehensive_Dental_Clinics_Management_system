<?php

namespace Database\Seeders;

use App\Models\TreatmentType;
use Illuminate\Database\Seeder;

class TreatmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name'    => 'تقويم أسنان',
                'name_en' => 'Orthodontics',
            ],
            [
                'name'    => 'زراعة أسنان',
                'name_en' => 'Dental Implants',
            ],
            [
                'name'    => 'تركيبات ثابتة',
                'name_en' => 'Fixed Prosthodontics',
            ],
            [
                'name'    => 'تركيبات متحركة',
                'name_en' => 'Removable Prosthodontics',
            ],
            [
                'name'    => 'علاج جذور',
                'name_en' => 'Root Canal Treatment',
            ],
            [
                'name'    => 'تبييض أسنان',
                'name_en' => 'Teeth Whitening',
            ],
            [
                'name'    => 'تنظيف أسنان',
                'name_en' => 'Teeth Cleaning',
            ],
            [
                'name'    => 'حشوات تجميلية',
                'name_en' => 'Cosmetic Fillings',
            ],
            [
                'name'    => 'علاج أطفال',
                'name_en' => 'Pediatric Treatment',
            ],
            [
                'name'    => 'جراحة فم',
                'name_en' => 'Oral Surgery',
            ],
            [
                'name'    => 'علاج لثة',
                'name_en' => 'Gum Treatment',
            ],
        ];

        foreach ($types as $item) {
            TreatmentType::updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }

    }
}
