<?php

namespace Database\Seeders;

use App\Models\AppointmentType;
use App\Models\Specialization;
use Illuminate\Database\Seeder;

class AppointmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name'    => 'استشارة',
                'name_en' => 'Consultation',
                'specializations' => ['عام'], // general dentist
            ],
            [
                'name'    => 'تقويم أسنان',
                'name_en' => 'Orthodontics',
                'specializations' => ['تقويم الأسنان'],
            ],
            [
                'name'    => 'تسوس أسنان',
                'name_en' => 'Dental Caries',
                'specializations' => ['عام', 'طب أسنان الأطفال'],
            ],
            [
                'name'    => 'جراحة أسنان',
                'name_en' => 'Dental Surgery',
                'specializations' => ['جراحة الفم والأسنان'],
            ],
            [
                'name'    => 'تبييض أسنان',
                'name_en' => 'Teeth Whitening',
                'specializations' => ['عام'],
            ],
            [
                'name'    => 'زراعة أسنان',
                'name_en' => 'Dental Implants',
                'specializations' => ['زراعة الأسنان', 'جراحة الفم والأسنان'],
            ],
        ];

        foreach ($types as $typeData) {
            $type = AppointmentType::create([
                'name'    => $typeData['name'],
                'name_en' => $typeData['name_en'],
            ]);

            // link to specializations by name
            $specializationIds = Specialization::whereIn('name', $typeData['specializations'])
                ->pluck('id');

            $type->specializations()->attach($specializationIds);
        }
    }
}
