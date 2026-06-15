<?php

namespace Database\Seeders;

use App\Models\LabOrderTypeCategory;
use Illuminate\Database\Seeder;

class LabOrderTypeCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'تيجان',
            'جسور',
            'طواقم',
            'تقويم',
            'أخرى',
        ];

        foreach ($categories as $name) {
            LabOrderTypeCategory::firstOrCreate(['name' => $name]);
        }
    }
}
