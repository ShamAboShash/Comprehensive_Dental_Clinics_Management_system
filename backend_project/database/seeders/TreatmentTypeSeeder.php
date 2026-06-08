<?php

namespace Database\Seeders;

use App\Models\TreatmentType;
use Illuminate\Database\Seeder;

class TreatmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'تقويم معدني',          'name_en' => 'Metal Braces'],
            ['name' => 'تقويم شفاف',            'name_en' => 'Clear Aligners'],
            ['name' => 'حشو عصب',               'name_en' => 'Root Canal'],
            ['name' => 'تركيب تاج',             'name_en' => 'Crown'],
            ['name' => 'تركيب جسر',             'name_en' => 'Bridge'],
            ['name' => 'زراعة سن',              'name_en' => 'Implant'],
            ['name' => 'قلع سن',                'name_en' => 'Extraction'],
            ['name' => 'تنظيف أسنان',           'name_en' => 'Cleaning'],
            ['name' => 'تبييض أسنان',           'name_en' => 'Whitening'],
            ['name' => 'حشو تجميلي',            'name_en' => 'Composite Filling'],
            ['name' => 'علاج لثة',              'name_en' => 'Gum Treatment'],
            ['name' => 'استشارة',               'name_en' => 'Consultation'],
        ];

        foreach ($types as $type) {
            TreatmentType::create($type);
        }
    }
}
