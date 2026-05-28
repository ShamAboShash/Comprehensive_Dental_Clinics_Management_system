<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            ['name' => 'أمراض القلب',   ],
            ['name' => 'أمراض الرئة',      ],
            ['name' => 'أمراض السكري',      ],
            ['name' => 'أمراض الكلى',       ],
            ['name' => 'أمراض الكبد',        ],
            ['name' => 'أمراض الدم',         ],
            ['name' => 'أمراض العظام',       ],
            ['name' => 'أمراض الجهاز العصبي',],
            ['name' => 'أمراض المناعة',     ],
            ['name' => 'أخرى',             ],
        ];

        foreach ($categories as $category) {
            DiseaseCategory::create($category);
        }
    }
}
