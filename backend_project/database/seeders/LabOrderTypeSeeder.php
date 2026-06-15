<?php

namespace Database\Seeders;

use App\Models\LabOrderType;
use App\Models\LabOrderTypeCategory;
use Illuminate\Database\Seeder;

class LabOrderTypeSeeder extends Seeder
{
    public function run(): void
    {
        $crowns     = LabOrderTypeCategory::where('name', 'تيجان')->first();
        $bridges    = LabOrderTypeCategory::where('name', 'جسور')->first();
        $dentures   = LabOrderTypeCategory::where('name', 'طواقم')->first();
        $ortho      = LabOrderTypeCategory::where('name', 'تقويم')->first();
        $other      = LabOrderTypeCategory::where('name', 'أخرى')->first();

        $types = [
            ['name' => 'تركيب زركون',  'name_en' => 'Zirconia Crown',         'category_id' => $crowns?->id,   'type' => 'تليسة',  'material' => 'Zirconia', 'price' => 150000, 'duration' => 5],
            ['name' => 'تركيب معدن',   'name_en' => 'Metal Crown',             'category_id' => $crowns?->id,   'type' => 'تليسة',  'material' => 'Metal',    'price' => 80000,  'duration' => 4],
            ['name' => 'تاج مؤقت',     'name_en' => 'Temporary Crown',         'category_id' => $crowns?->id,   'type' => 'تليسة',  'material' => 'Acrylic',  'price' => 30000,  'duration' => 2],
            ['name' => 'جسر ثابت',     'name_en' => 'Fixed Bridge',            'category_id' => $bridges?->id,  'type' => 'جسر',    'material' => 'Zirconia', 'price' => 300000, 'duration' => 7],
            ['name' => 'طقم أسنان',    'name_en' => 'Full Denture',            'category_id' => $dentures?->id, 'type' => 'طقم',    'material' => 'Acrylic',  'price' => 200000, 'duration' => 7],
            ['name' => 'تقويم شفاف',   'name_en' => 'Clear Aligner',           'category_id' => $ortho?->id,    'type' => 'تقويم',  'material' => 'PVC',      'price' => 500000, 'duration' => 14],
            ['name' => 'جهاز تقويم',   'name_en' => 'Orthodontic Appliance',   'category_id' => $ortho?->id,    'type' => 'تقويم',  'material' => 'Metal',    'price' => 120000, 'duration' => 10],
            ['name' => 'طبعة أسنان',   'name_en' => 'Dental Impression',       'category_id' => $other?->id,    'type' => 'طبعة',   'material' => 'Alginate', 'price' => 20000,  'duration' => 1],
            ['name' => 'موديل دراسي',  'name_en' => 'Study Model',             'category_id' => $other?->id,    'type' => 'موديل',  'material' => 'Plaster',  'price' => 15000,  'duration' => 1],
            ['name' => 'أخرى',         'name_en' => 'Others',                  'category_id' => $other?->id,    'type' => 'أخرى',   'material' => 'Other',    'price' => 50000,  'duration' => 3],
        ];

        foreach ($types as $type) {
            LabOrderType::firstOrCreate(
                ['name' => $type['name']],
                array_merge($type, ['is_active' => true])
            );
        }
    }
}
