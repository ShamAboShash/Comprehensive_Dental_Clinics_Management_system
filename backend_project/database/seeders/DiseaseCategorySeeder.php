<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DiseaseCategory;

class DiseaseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'أمراض القلب',        'name_en' => 'Heart Diseases'],
            ['name' => 'أمراض الرئة',         'name_en' => 'Lung Diseases'],
            ['name' => 'أمراض السكري',        'name_en' => 'Diabetes'],
            ['name' => 'أمراض الكلى',         'name_en' => 'Kidney Diseases'],
            ['name' => 'أمراض الكبد',         'name_en' => 'Liver Diseases'],
            ['name' => 'أمراض الدم',          'name_en' => 'Blood Diseases'],
            ['name' => 'أمراض العظام',        'name_en' => 'Bone Diseases'],
            ['name' => 'أمراض الجهاز العصبي', 'name_en' => 'Nervous System Diseases'],
            ['name' => 'أمراض المناعة',       'name_en' => 'Immune System Diseases'],
            ['name' => 'أخرى',                'name_en' => 'Other'],
        ];

        foreach ($categories as $category) {
            DiseaseCategory::create($category);
        }
    }
}
