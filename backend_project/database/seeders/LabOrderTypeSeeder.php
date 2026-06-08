<?php

namespace Database\Seeders;

use App\Models\LabOrderType;
use Illuminate\Database\Seeder;

class LabOrderTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'تركيب زركون',       'name_en' => 'Zirconia Crown'],
            ['name' => 'تركيب معدن',        'name_en' => 'Metal Crown'],
            ['name' => 'تقويم شفاف',        'name_en' => 'Clear Aligner'],
            ['name' => 'جسر ثابت',          'name_en' => 'Fixed Bridge'],
            ['name' => 'طبعة أسنان',        'name_en' => 'Dental Impression'],
            ['name' => 'موديل دراسي',       'name_en' => 'Study Model'],
            ['name' => 'تاج مؤقت',          'name_en' => 'Temporary Crown'],
            ['name' => 'جهاز تقويم',        'name_en' => 'Orthodontic Appliance'],
            ['name' => 'أخرى',              'name_en' => 'Others'],
        ];

        foreach ($types as $type) {
            LabOrderType::firstOrCreate($type);
        }

    }
}
