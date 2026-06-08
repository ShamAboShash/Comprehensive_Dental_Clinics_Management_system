<?php

namespace Database\Seeders;

use App\Models\Procedure;
use Illuminate\Database\Seeder;

class ProcedureSeeder extends Seeder
{
    public function run(): void
    {
        $procedures = [
            // حشوات
            ['name' => 'حشو أبيض صغير',       'name_en' => 'Small Composite Filling',  'price' => 15000],
            ['name' => 'حشو أبيض متوسط',      'name_en' => 'Medium Composite Filling', 'price' => 20000],
            ['name' => 'حشو أبيض كبير',       'name_en' => 'Large Composite Filling',  'price' => 25000],

            // علاج عصب
            ['name' => 'علاج عصب أمامي',      'name_en' => 'Anterior Root Canal',      'price' => 50000],
            ['name' => 'علاج عصب خلفي',       'name_en' => 'Posterior Root Canal',     'price' => 70000],

            // تيجان وجسور
            ['name' => 'تاج زركون',            'name_en' => 'Zirconia Crown',           'price' => 120000],
            ['name' => 'تاج معدني',            'name_en' => 'Metal Crown',              'price' => 60000],
            ['name' => 'جسر ثلاثي زركون',     'name_en' => 'Zirconia Bridge',          'price' => 300000],

            // قلع
            ['name' => 'قلع بسيط',            'name_en' => 'Simple Extraction',        'price' => 15000],
            ['name' => 'قلع جراحي',           'name_en' => 'Surgical Extraction',      'price' => 40000],
            ['name' => 'قلع ضرس عقل',         'name_en' => 'Wisdom Tooth Extraction',  'price' => 60000],

            // تنظيف
            ['name' => 'تنظيف أسنان',         'name_en' => 'Scaling',                  'price' => 20000],
            ['name' => 'تلميع أسنان',         'name_en' => 'Polishing',                'price' => 10000],

            // تقويم
            ['name' => 'تركيب براكيت',        'name_en' => 'Bracket Placement',        'price' => 5000],
            ['name' => 'جلسة تقويم',          'name_en' => 'Orthodontic Session',      'price' => 15000],
            ['name' => 'خلع براكيت',          'name_en' => 'Bracket Removal',          'price' => 20000],

            // تبييض
            ['name' => 'تبييض بالليزر',       'name_en' => 'Laser Whitening',          'price' => 80000],

            // لثة
            ['name' => 'كحت لثة',             'name_en' => 'Curettage',                'price' => 30000],
            ['name' => 'جراحة لثة',           'name_en' => 'Gum Surgery',              'price' => 80000],

            // زراعة
            ['name' => 'زراعة سن',            'name_en' => 'Implant Placement',        'price' => 500000],

            // استشارة
            ['name' => 'استشارة',             'name_en' => 'Consultation',             'price' => 10000],
        ];

        foreach ($procedures as $procedure) {
            Procedure::create($procedure);
        }
    }
}
