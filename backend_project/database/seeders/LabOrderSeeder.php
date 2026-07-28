<?php

// namespace Database\Seeders;

// use App\Models\Dentist;
// use App\Models\LabOrder;
// use App\Models\LabOrderItem;
// use App\Models\LabOrderType;
// use App\Models\TreatmentSession;
// use Illuminate\Database\Seeder;

// class LabOrderSeeder extends Seeder
// {
//     public function run(): void
//     {
//         $sessions      = TreatmentSession::all();
//         $labOrderTypes = LabOrderType::all();
//         $dentists      = Dentist::all();

//         if ($sessions->isEmpty() || $labOrderTypes->isEmpty()) {
//             $this->command->warn('Skipping LabOrderSeeder: No sessions or lab order types found.');
//             return;
//         }

//         foreach ($sessions->take(6) as $session) {
//             $dentist      = $dentists->random();
//             $labOrderType = $labOrderTypes->random();

//             $labOrder = LabOrder::create([
//                 'treatment_id'            => $session->treatment_id,
//                 'session_id'              => $session->id,
//                 'dentist_id'              => $dentist->id,
//                 'patient_id'              => $session->treatment->patient_id,
//                 'technician_id'           => null,
//                 'status'                  => 'new', // fixed: orders start as 'new' not 'pending'
//                 'delivery_date_requested' => now()->addDays(rand(5, 12)),
//                 'notes'                   => 'طلب طبعة وتركيب للسن رقم ' . rand(11, 48),
//             ]);

//             // Add 1 to 3 items
//             $itemCount = rand(1, 3);
//             for ($i = 0; $i < $itemCount; $i++) {
//                 LabOrderItem::create([
//                     'lab_order_id'      => $labOrder->id,
//                     'tooth_number'      => rand(11, 48),
//                     'lab_order_type_id' => $labOrderType->id,
//                     'price'             => rand(25000, 150000),
//                     'notes'             => 'تركيب دائم',
//                 ]);
//             }
//         }
//     }
// }

namespace Database\Seeders;

use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabOrderType;
use App\Models\Employee;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\TreatmentSession;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LabOrderSeeder extends Seeder
{
    public function run(): void
    {
        $dentists     = Dentist::all();
        $patients     = Patient::all();
        $technicians  = Employee::whereHas('user', fn($q) => $q->where('role', UserRole::TECHNICIAN))->get();
        $labOrderTypes = LabOrderType::all();
        $treatments   = Treatment::with('sessions')->get();

        if ($dentists->isEmpty() || $patients->isEmpty() || $labOrderTypes->isEmpty() || $treatments->isEmpty()) {
            $this->command->warn('Missing required data (dentists, patients, lab order types or treatments). Skipping LabOrderSeeder.');
            return;
        }

        $statuses = ['new', 'in_progress', 'completed', 'cancelled'];

        $startDate = Carbon::create(2026, 7, 1);
        $endDate   = Carbon::create(2026, 8, 31);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {

            // Skip some days randomly
            if (rand(1, 10) <= 3) {
                continue;
            }

            $ordersPerDay = rand(1, 4);

            for ($i = 0; $i < $ordersPerDay; $i++) {

                $createdAt = $date->copy()->setTime(rand(8, 17), rand(0, 59));

                // Prefer completed orders
                $status = (rand(1, 10) <= 6) ? 'completed' : $statuses[array_rand($statuses)];

                $technicianId = null;
                $updatedAt = $createdAt->copy();

                if (in_array($status, ['in_progress', 'completed']) && $technicians->isNotEmpty()) {
                    $technicianId = $technicians->random()->id;
                }

                // Delivery date = created + 3→9 days
                $deliveryDate = $createdAt->copy()->addDays(rand(3, 9));

                if ($status === 'completed') {
                    // 70% on time, 30% late
                    if (rand(1, 10) <= 7) {
                        $updatedAt = $deliveryDate->copy()->subHours(rand(2, 40));
                    } else {
                        $updatedAt = $deliveryDate->copy()->addHours(rand(5, 60));
                    }

                    if ($updatedAt->lt($createdAt)) {
                        $updatedAt = $createdAt->copy()->addHours(rand(6, 36));
                    }
                }

                if ($status === 'cancelled') {
                    $updatedAt = $createdAt->copy()->addHours(rand(1, 15));
                }

                // ========== Important: Always pick a real treatment ==========
                $treatment = $treatments->random();
                $session   = $treatment->sessions->isNotEmpty()
                    ? $treatment->sessions->random()
                    : null;

                $order = LabOrder::create([
                    'treatment_id'            => $treatment->id,          // ← required
                    'session_id'              => $session?->id,
                    'dentist_id'              => $treatment->dentist_id,
                    'patient_id'              => $treatment->patient_id,
                    'technician_id'           => $technicianId,
                    'status'                  => $status,
                    'delivery_date_requested' => $deliveryDate->toDateString(),
                    'notes'                   => null,
                    'created_at'              => $createdAt,
                    'updated_at'              => $updatedAt,
                ]);

                // Add 1–3 items
                $itemsCount = rand(1, 3);
                for ($j = 0; $j < $itemsCount; $j++) {
                    $type = $labOrderTypes->random();

                    LabOrderItem::create([
                        'lab_order_id'      => $order->id,
                        'lab_order_type_id' => $type->id,
                        'tooth_number'      => rand(11, 48),
                        'price'             => $type->price ?? rand(80, 350),
                        'notes'             => null,
                    ]);
                }
            }
        }

    }
}
