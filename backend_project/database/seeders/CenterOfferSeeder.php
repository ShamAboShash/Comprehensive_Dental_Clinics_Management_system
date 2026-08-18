<?php

namespace Database\Seeders;

use App\Models\CenterOffer;
use App\Models\TreatmentType;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CenterOfferSeeder extends Seeder
{
    public function run(): void
    {
        $secretary = User::where('role', UserRole::SECRETARY)->first();

        if (!$secretary) {
            $this->command->warn('No secretary user found. Skipping CenterOfferSeeder.');
            return;
        }

        $treatmentTypes = TreatmentType::all();

        if ($treatmentTypes->isEmpty()) {
            $this->command->warn('No treatment types found. Skipping CenterOfferSeeder.');
            return;
        }

        // ── Default offer logo ──────────────────────────────────────
        // Source: storage/app/public/offer.jpg
        // Stored path in DB: offers/offer.jpg
        $photoPath = 'offers/offer.jpg';
        $source    = storage_path('app/public/offer.jpg');

        if (File::exists($source)) {
            Storage::disk('public')->makeDirectory('offers');

            if (!Storage::disk('public')->exists($photoPath)) {
                File::copy($source, storage_path('app/public/' . $photoPath));
            }
        } else {
            $photoPath = null;
            $this->command->warn('offer.jpg not found at storage/app/public/offer.jpg — offers will have no photo.');
        }

        // Helper to find treatment type by Arabic / English keyword
        $findType = function (string $keyword) use ($treatmentTypes) {
            return $treatmentTypes->first(function ($type) use ($keyword) {
                return str_contains($type->name, $keyword)
                    || str_contains($type->name_en ?? '', $keyword);
            });
        };

        $offers = [
            [
                'title'               => 'عرض تنظيف الأسنان',
                'description'         => 'احصل على تنظيف أسنان احترافي مع خصم مميز لفترة محدودة.',
                'start_date'          => Carbon::create(2026, 7, 1),
                'end_date'            => Carbon::create(2026, 8, 31),
                'conditions'          => 'العرض ساري على جلسة تنظيف واحدة فقط لكل مريض.',
                'discount_percentage' => 25.00,
                'is_active'           => true,
                'types'               => ['تنظيف', 'Cleaning'],
            ],
            [
                'title'               => 'عرض تبييض الأسنان',
                'description'         => 'ابتسامة أكثر إشراقاً مع خصم خاص على جلسة تبييض الأسنان.',
                'start_date'          => Carbon::create(2026, 7, 15),
                'end_date'            => Carbon::create(2026, 9, 15),
                'conditions'          => 'يشترط إجراء كشف أولي قبل التبييض.',
                'discount_percentage' => 30.00,
                'is_active'           => true,
                'types'               => ['تبييض', 'Whitening'],
            ],
            [
                'title'               => 'عرض تقويم الأسنان',
                'description'         => 'خصم مميز على جلسات التقويم للأسنان.',
                'start_date'          => Carbon::create(2026, 6, 1),
                'end_date'            => Carbon::create(2026, 8, 31),
                'conditions'          => 'العرض يشمل التقويم الشفاف والتقليدي.',
                'discount_percentage' => 15.00,
                'is_active'           => true,
                'types'               => ['تقويم', 'Orthodont'],
            ],
            [
                'title'               => 'عرض حشوات التجميل',
                'description'         => 'حشوات تجميلية عالية الجودة بأسعار مخفضة.',
                'start_date'          => Carbon::create(2026, 8, 1),
                'end_date'            => Carbon::create(2026, 9, 30),
                'conditions'          => 'يسري العرض على الحشوات التجميلية فقط.',
                'discount_percentage' => 20.00,
                'is_active'           => true,
                'types'               => ['حشو', 'Fill'],
            ],
            [
                'title'               => 'عرض زراعة الأسنان',
                'description'         => 'خصم خاص على زراعة الأسنان مع استشارة مجانية.',
                'start_date'          => Carbon::create(2026, 7, 1),
                'end_date'            => Carbon::create(2026, 10, 31),
                'conditions'          => 'يشمل العرض استشارة أولية مجانية.',
                'discount_percentage' => 18.00,
                'is_active'           => true,
                'types'               => ['زراعة', 'Implant'],
            ],
            [
                'title'               => 'عرض شامل (تنظيف + تبييض)',
                'description'         => 'عرض مميز يجمع بين تنظيف وتبييض الأسنان.',
                'start_date'          => Carbon::create(2026, 7, 1),
                'end_date'            => Carbon::create(2026, 8, 31),
                'conditions'          => 'يمكن استخدام العرض على نوعي العلاج.',
                'discount_percentage' => 22.00,
                'is_active'           => true,
                'types'               => ['تنظيف', 'تبييض', 'Cleaning', 'Whitening'],
            ],
        ];

        foreach ($offers as $data) {
            $offer = CenterOffer::create([
                'created_by'          => $secretary->id,
                'title'               => $data['title'],
                'description'         => $data['description'],
                'start_date'          => $data['start_date'],
                'end_date'            => $data['end_date'],
                'conditions'          => $data['conditions'],
                'discount_percentage' => $data['discount_percentage'],
                'photo_path'          => $photoPath, // offers/offer.jpg
                'is_active'           => $data['is_active'],
            ]);

            $typeIds = [];

            foreach ($data['types'] as $keyword) {
                $type = $findType($keyword);
                if ($type) {
                    $typeIds[] = $type->id;
                }
            }

            $typeIds = array_unique($typeIds);

            if (!empty($typeIds)) {
                $offer->treatmentTypes()->attach($typeIds);
            }
        }
    }
}