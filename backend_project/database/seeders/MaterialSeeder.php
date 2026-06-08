<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            // clinic materials
            [
                'name'          => 'قفازات طبية',
                'name_en'       => 'Medical Gloves',
                'company_name'  => 'Ansell',
                'price_per_unit'=> 500,
                'dosage'        => null,
                'unit'          => 'علبة',
                'category'      => 'clinic',
            ],
            [
                'name'          => 'كمامات طبية',
                'name_en'       => 'Medical Masks',
                'company_name'  => '3M',
                'price_per_unit'=> 300,
                'dosage'        => null,
                'unit'          => 'علبة',
                'category'      => 'clinic',
            ],
            [
                'name'          => 'محلول تعقيم',
                'name_en'       => 'Sterilization Solution',
                'company_name'  => 'Dettol',
                'price_per_unit'=> 1500,
                'dosage'        => null,
                'unit'          => 'لتر',
                'category'      => 'clinic',
            ],
            [
                'name'          => 'إبر تخدير',
                'name_en'       => 'Anesthesia Needles',
                'company_name'  => 'BD',
                'price_per_unit'=> 200,
                'dosage'        => null,
                'unit'          => 'علبة',
                'category'      => 'clinic',
            ],
            [
                'name'          => 'مادة حشو مركبة',
                'name_en'       => 'Composite Filling',
                'company_name'  => '3M',
                'price_per_unit'=> 8000,
                'dosage'        => null,
                'unit'          => 'قطعة',
                'category'      => 'clinic',
            ],

            // lab materials
            [
                'name'          => 'بودرة جبس',
                'name_en'       => 'Plaster Powder',
                'company_name'  => 'Dentona',
                'price_per_unit'=> 3000,
                'dosage'        => null,
                'unit'          => 'كغ',
                'category'      => 'lab',
            ],
            [
                'name'          => 'مادة طباعة زركون',
                'name_en'       => 'Zirconia Block',
                'company_name'  => 'Ivoclar',
                'price_per_unit'=> 25000,
                'dosage'        => null,
                'unit'          => 'قطعة',
                'category'      => 'lab',
            ],
            [
                'name'          => 'سيليكون طبعة',
                'name_en'       => 'Impression Silicone',
                'company_name'  => 'Zhermack',
                'price_per_unit'=> 12000,
                'dosage'        => null,
                'unit'          => 'علبة',
                'category'      => 'lab',
            ],

            // both
            [
                'name'          => 'مناديل طبية',
                'name_en'       => 'Medical Wipes',
                'company_name'  => 'Kimberley',
                'price_per_unit'=> 400,
                'dosage'        => null,
                'unit'          => 'علبة',
                'category'      => 'both',
            ],
            [
                'name'          => 'أكواب بلاستيكية',
                'name_en'       => 'Plastic Cups',
                'company_name'  => 'Local',
                'price_per_unit'=> 200,
                'dosage'        => null,
                'unit'          => 'علبة',
                'category'      => 'both',
            ],
        ];

        foreach ($materials as $material) {
            Material::create($material);
        }

    }
}
