<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CenterOfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('center_offers')->insert([
            [
                'created_by'          => 3,
                'title'               => 'Summer Special Discount',
                'description'         => 'Enjoy a great discount on all center services during the summer season.',
                'start_date'          => $now->copy()->subDays(5)->toDateString(),
                'end_date'            => $now->copy()->addDays(30)->toDateString(),
                'conditions'          => 'Valid for new members only. Cannot be combined with other offers.',
                'discount_percentage' => 15.00,
                'photo_path'          => null,
                'is_active'           => true,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'created_by'          => 3,
                'title'               => 'Weekend Flash Offer',
                'description'         => 'Limited time weekend offer with exclusive benefits.',
                'start_date'          => $now->copy()->subDays(2)->toDateString(),
                'end_date'            => $now->copy()->addDays(5)->toDateString(),
                'conditions'          => 'Available only on weekends. One use per customer.',
                'discount_percentage' => 20.50,
                'photo_path'          => 'offers/weekend-flash.jpg',
                'is_active'           => true,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'created_by'          => 3,
                'title'               => 'Expired Membership Deal',
                'description'         => 'Old promotional offer that is no longer valid.',
                'start_date'          => $now->copy()->subMonths(3)->toDateString(),
                'end_date'            => $now->copy()->subMonth()->toDateString(),
                'conditions'          => null,
                'discount_percentage' => 10.00,
                'photo_path'          => null,
                'is_active'           => false,
                'created_at'          => $now->copy()->subMonths(3),
                'updated_at'          => $now->copy()->subMonths(3),
            ],
        ]);
    }
}
[]
