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
                'specializations' => ['عام'],
            ],
            [
                'name'    => 'تقويم أسنان',
                'name_en' => 'Orthodontics',
                'specializations' => ['تقويم أسنان'],
            ],
            [
                'name'    => 'تسوس أسنان',
                'name_en' => 'Dental Caries',
                'specializations' => ['عام', 'طب أسنان أطفال'],
            ],
            [
                'name'    => 'جراحة أسنان',
                'name_en' => 'Dental Surgery',
                'specializations' => ['جراحة فم وأسنان'],
            ],
            [
                'name'    => 'تبييض أسنان',
                'name_en' => 'Teeth Whitening',
                'specializations' => ['عام', 'تجميل أسنان'],
            ],
            [
                'name'    => 'زراعة أسنان',
                'name_en' => 'Dental Implants',
                'specializations' => ['زراعة أسنان', 'جراحة فم وأسنان'],
            ],
        ];

        foreach ($types as $typeData) {
            $type = AppointmentType::create([
                'name'    => $typeData['name'],
                'name_en' => $typeData['name_en'],
            ]);

            $specializationIds = Specialization::whereIn('name', $typeData['specializations'])
                ->pluck('id');

            if ($specializationIds->isEmpty()) {
                $this->command->warn("لم يتم العثور على اختصاصات لنوع الموعد: {$typeData['name']}");
            }

            $type->specializations()->attach($specializationIds);
        }
    }
}
