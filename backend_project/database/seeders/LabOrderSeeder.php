<?php

namespace Database\Seeders;

use App\Models\Dentist;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabOrderType;
use App\Models\TreatmentSession;
use Illuminate\Database\Seeder;

class LabOrderSeeder extends Seeder
{
    public function run(): void
    {
        $sessions      = TreatmentSession::all();
        $labOrderTypes = LabOrderType::all();
        $dentists      = Dentist::all();

        if ($sessions->isEmpty() || $labOrderTypes->isEmpty()) {
            $this->command->warn('Skipping LabOrderSeeder: No sessions or lab order types found.');
            return;
        }

        foreach ($sessions->take(6) as $session) {
            $dentist      = $dentists->random();
            $labOrderType = $labOrderTypes->random();

            $labOrder = LabOrder::create([
                'treatment_id'            => $session->treatment_id,
                'session_id'              => $session->id,
                'dentist_id'              => $dentist->id,
                'patient_id'              => $session->treatment->patient_id,
                'technician_id'           => null,
                'status'                  => 'new', // fixed: orders start as 'new' not 'pending'
                'delivery_date_requested' => now()->addDays(rand(5, 12)),
                'notes'                   => 'طلب طبعة وتركيب للسن رقم ' . rand(11, 48),
            ]);

            // Add 1 to 3 items
            $itemCount = rand(1, 3);
            for ($i = 0; $i < $itemCount; $i++) {
                LabOrderItem::create([
                    'lab_order_id'      => $labOrder->id,
                    'tooth_number'      => rand(11, 48),
                    'lab_order_type_id' => $labOrderType->id,
                    'price'             => rand(25000, 150000),
                    'notes'             => 'تركيب دائم',
                ]);
            }
        }
    }
}
