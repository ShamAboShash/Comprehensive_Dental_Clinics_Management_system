<?php

namespace Database\Seeders;

use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabOrderMaterial;
use App\Models\LabOrderModification;
use App\Models\LabOrderType;
use App\Models\Material;
use App\Models\Employee;
use App\Models\Treatment;
use App\Models\TreatmentSession;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LabOrderSeeder extends Seeder
{
    public function run(): void
    {
        $labOrderTypes = LabOrderType::where('is_active', true)->get();
        if ($labOrderTypes->isEmpty()) {
            $labOrderTypes = LabOrderType::all();
        }

        $technicians = Employee::whereHas('user', fn ($q) => $q->where('role', UserRole::TECHNICIAN->value))->get();
        if ($technicians->isEmpty()) {
            // Fallback: any non-dentist employee
            $technicians = Employee::whereDoesntHave('dentist')->get();
        }

        // Prefer treatments that already have sessions (the ones we seeded for August)
        $treatments = Treatment::with(['sessions', 'patient', 'dentist'])
            ->whereHas('sessions')
            ->orderBy('id')
            ->get();

        if ($treatments->isEmpty()) {
            $treatments = Treatment::with(['sessions', 'patient', 'dentist'])->get();
        }

        if ($treatments->isEmpty() || $labOrderTypes->isEmpty()) {
            $this->command->warn('Skipping LabOrderSeeder: missing treatments or lab order types.');
            return;
        }

        // Lab materials (for LabOrderMaterial)
        $labMaterials = Material::whereIn('category', ['lab', 'both'])->get();
        if ($labMaterials->isEmpty()) {
            $labMaterials = Material::all();
        }

        $statuses = ['new', 'in_progress', 'completed', 'cancelled'];
        $modFaults = ['dentist', 'lab'];
        $modStatuses = ['pending', 'accepted', 'rejected'];

        $createdOrders = 0;
        $createdItems  = 0;
        $createdMats   = 0;
        $createdMods   = 0;

        // ---------------------------------------------------------------
        // Strategy: create 2–4 lab orders per treatment that has sessions
        // so they clearly complement the clinical data we already seeded.
        // Extra bulk orders on a few treatments for volume.
        // ---------------------------------------------------------------
        foreach ($treatments as $tIndex => $treatment) {
            $sessions = $treatment->sessions;
            $orderCount = $sessions->isNotEmpty() ? rand(2, 4) : 1;

            // Give the first few treatments extra orders (volume)
            if ($tIndex < 3) {
                $orderCount += 2;
            }

            for ($o = 0; $o < $orderCount; $o++) {
                $session = $sessions->isNotEmpty()
                    ? $sessions[$o % $sessions->count()]
                    : null;

                // Base date around the session (or treatment creation) → keep in July/August 2026
                if ($session && $session->actual_start_time) {
                    $base = Carbon::parse($session->actual_start_time)->copy();
                } elseif ($session && $session->created_at) {
                    $base = Carbon::parse($session->created_at)->copy();
                } else {
                    $base = Carbon::create(2026, 8, 1 + ($tIndex % 25))->setTime(9 + ($o % 8), 0);
                }

                // Lab order usually created same day or 1–2 days after the session
                $createdAt = $base->copy()->addHours(rand(0, 36))->addMinutes(rand(0, 50));
                if ($createdAt->month !== 7 && $createdAt->month !== 8) {
                    $createdAt = Carbon::create(2026, 8, min(28, 5 + $tIndex + $o))->setTime(10, 0);
                }

                // Status distribution: more completed / in_progress for useful data
                $roll = ($treatment->id + $o) % 10;
                $status = match (true) {
                    $roll <= 4 => 'completed',
                    $roll <= 6 => 'in_progress',
                    $roll <= 8 => 'new',
                    default    => 'cancelled',
                };

                $technicianId = null;
                $updatedAt    = $createdAt->copy();

                if (in_array($status, ['in_progress', 'completed']) && $technicians->isNotEmpty()) {
                    $technicianId = $technicians[($treatment->id + $o) % $technicians->count()]->id;
                }

                $deliveryDate = $createdAt->copy()->addDays(3 + (($treatment->id + $o) % 7));

                if ($status === 'completed') {
                    // ~70% on-time
                    if ((($treatment->id + $o) % 10) < 7) {
                        $updatedAt = $deliveryDate->copy()->subHours(2 + (($o * 5) % 30));
                    } else {
                        $updatedAt = $deliveryDate->copy()->addHours(5 + (($o * 7) % 40));
                    }
                    if ($updatedAt->lt($createdAt)) {
                        $updatedAt = $createdAt->copy()->addHours(8);
                    }
                } elseif ($status === 'cancelled') {
                    $updatedAt = $createdAt->copy()->addHours(1 + ($o % 12));
                } elseif ($status === 'in_progress') {
                    $updatedAt = $createdAt->copy()->addHours(4 + ($o % 20));
                }

                $order = LabOrder::create([
                    'treatment_id'            => $treatment->id,
                    'session_id'              => $session?->id,
                    'dentist_id'              => $treatment->dentist_id,
                    'patient_id'              => $treatment->patient_id,
                    'technician_id'           => $technicianId,
                    'status'                  => $status,
                    'price'                   => 0, // will update after items
                    'delivery_date_requested' => $deliveryDate->toDateString(),
                    'rejection_reason'        => $status === 'cancelled'
                        ? ['طلب ملغى من الطبيب', 'المريض لم يعد يحتاج', 'خطأ في الطلب'][($o % 3)]
                        : null,
                    'notes'                   => 'طلب مخبري مرتبط بعلاج المريض #' . $treatment->patient_id . ' - آب 2026',
                    'created_at'              => $createdAt,
                    'updated_at'              => $updatedAt,
                ]);

                // ---------- Lab Order Items (1–3) ----------
                $itemsCount = 1 + (($treatment->id + $o) % 3);
                $orderTotal = 0;

                for ($j = 0; $j < $itemsCount; $j++) {
                    $type = $labOrderTypes[($treatment->id + $o + $j) % $labOrderTypes->count()];
                    $price = (float) ($type->price ?? (50000 + ($j * 15000)));

                    // Prefer tooth numbers that already exist on the session's tooth treatments
                    $tooth = 11 + (($treatment->id + $j) % 38);
                    if ($session) {
                        $session->loadMissing('toothTreatments');
                        if ($session->toothTreatments->isNotEmpty()) {
                            $tooth = $session->toothTreatments[($j % $session->toothTreatments->count())]->tooth_number;
                        }
                    }

                    LabOrderItem::create([
                        'lab_order_id'      => $order->id,
                        'lab_order_type_id' => $type->id,
                        'tooth_number'      => $tooth,
                        'price'             => $price,
                        'notes'             => $type->name ?? null,
                    ]);

                    $orderTotal += $price;
                    $createdItems++;
                }

                $order->update(['price' => $orderTotal]);

                // ---------- Lab Order Materials (for in_progress / completed) ----------
                if (in_array($status, ['in_progress', 'completed']) && $labMaterials->isNotEmpty()) {
                    $matCount = 1 + (($o + $treatment->id) % 3);
                    $usedMats = $labMaterials->shuffle()->take($matCount);

                    foreach ($usedMats as $mat) {
                        LabOrderMaterial::create([
                            'lab_order_id'  => $order->id,
                            'material_id'   => $mat->id,
                            'quantity_used' => round(0.5 + (($treatment->id + $mat->id) % 5) * 0.5, 2),
                        ]);
                        $createdMats++;
                    }
                }

                // ---------- Lab Order Modifications (some completed/in_progress orders) ----------
                if (in_array($status, ['completed', 'in_progress']) && (($treatment->id + $o) % 4 === 0)) {
                    LabOrderModification::create([
                        'lab_order_id'         => $order->id,
                        'modification_details' => [
                            'تعديل لون التركيبة',
                            'إعادة ضبط الإطباق',
                            'تقليل ارتفاع التاج',
                            'تغيير نوع المادة إلى زركون',
                        ][($o + $treatment->id) % 4],
                        'fault'                => $modFaults[($o) % 2],
                        'status'               => $modStatuses[($treatment->id + $o) % 3],
                        'response_notes'       => (($treatment->id + $o) % 3) === 0
                            ? null
                            : 'تم الرد من المخبر - آب 2026',
                    ]);
                    $createdMods++;
                }

                $createdOrders++;
            }
        }

        $this->command->info("✅ LabOrderSeeder (complements treatments/sessions):");
        $this->command->info("   • {$createdOrders} lab orders");
        $this->command->info("   • {$createdItems} lab order items");
        $this->command->info("   • {$createdMats} lab order materials");
        $this->command->info("   • {$createdMods} lab order modifications");
    }
}
