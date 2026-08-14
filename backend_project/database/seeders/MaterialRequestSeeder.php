<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\NewMaterialRequest;
use App\Models\User;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MaterialRequestSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = Clinic::all();
        $materials = Material::all();
        $clinicMaterials = Material::whereIn('category', ['clinic', 'both'])->get();
        $labMaterials    = Material::whereIn('category', ['lab', 'both'])->get();

        if ($materials->isEmpty()) {
            $this->command->warn('Skipping MaterialRequestSeeder: no materials found.');
            return;
        }

        // Requesters
        $secretaries = User::where('role', UserRole::SECRETARY->value)
            ->where('is_active', true)
            ->get();
        $labManagers = User::where('role', UserRole::LAB_MANAGER->value)
            ->where('is_active', true)
            ->get();
        $admins = User::where('role', UserRole::ADMIN->value)
            ->where('is_active', true)
            ->get();

        $clinicRequester = $secretaries->first() ?? $admins->first() ?? User::where('is_active', true)->first();
        $labRequester    = $labManagers->first() ?? $admins->first() ?? $clinicRequester;

        if (!$clinicRequester) {
            $this->command->warn('Skipping MaterialRequestSeeder: no active user for requested_by.');
            return;
        }

        // material_requests.status → new, pending, completed, cancelled, rejected
        $statuses = ['new', 'pending', 'completed', 'cancelled', 'rejected'];

        // new_material_requests.status
        $newStatuses = ['new', 'approved', 'rejected', 'purchased'];

        $augustStart = Carbon::create(2026, 8, 1);
        $totalRequests = 0;
        $totalItems = 0;
        $totalNew = 0;

        // ---------------------------------------------------------------
        // 1) Clinic requests — more than 7 per clinic
        // ---------------------------------------------------------------
        $clinicRequestCount = 0;

        if ($clinics->isEmpty()) {
            $this->command->warn('No clinics found — creating clinic-type requests without clinic_id.');
            for ($i = 0; $i < 10; $i++) {
                $this->createOneRequest(
                    requesterType: 'clinic',
                    clinicId: null,
                    requestedBy: $clinicRequester->id,
                    materials: $clinicMaterials->isNotEmpty() ? $clinicMaterials : $materials,
                    baseDate: $augustStart->copy()->addDays($i % 28),
                    statuses: $statuses,
                    newStatuses: $newStatuses,
                    totalRequests: $totalRequests,
                    totalItems: $totalItems,
                    totalNew: $totalNew
                );
                $clinicRequestCount++;
            }
        } else {
            foreach ($clinics as $cIndex => $clinic) {
                // >7 per clinic → 8 or 9
                $countForClinic = 8 + ($cIndex % 2);

                for ($i = 0; $i < $countForClinic; $i++) {
                    $this->createOneRequest(
                        requesterType: 'clinic',
                        clinicId: $clinic->id,
                        requestedBy: $clinicRequester->id,
                        materials: $clinicMaterials->isNotEmpty() ? $clinicMaterials : $materials,
                        baseDate: $augustStart->copy()->addDays(($cIndex * 3 + $i) % 30),
                        statuses: $statuses,
                        newStatuses: $newStatuses,
                        totalRequests: $totalRequests,
                        totalItems: $totalItems,
                        totalNew: $totalNew
                    );
                    $clinicRequestCount++;
                }
            }
        }

        // ---------------------------------------------------------------
        // 2) Lab requests — more than 10
        // ---------------------------------------------------------------
        $labRequestCount = 12; // >10

        for ($i = 0; $i < $labRequestCount; $i++) {
            $this->createOneRequest(
                requesterType: 'lab',
                clinicId: null,
                requestedBy: $labRequester->id,
                materials: $labMaterials->isNotEmpty() ? $labMaterials : $materials,
                baseDate: $augustStart->copy()->addDays(($i * 2) % 28),
                statuses: $statuses,
                newStatuses: $newStatuses,
                totalRequests: $totalRequests,
                totalItems: $totalItems,
                totalNew: $totalNew
            );
        }

        $this->command->info("✅ MaterialRequestSeeder:");
        $this->command->info("   • {$totalRequests} material requests total");
        $this->command->info("   • Clinic requests: {$clinicRequestCount} (each clinic >7 when clinics exist)");
        $this->command->info("   • Lab requests: {$labRequestCount}");
        $this->command->info("   • {$totalItems} request items (no per-item status)");
        $this->command->info("   • {$totalNew} new material requests");
    }

    private function createOneRequest(
        string $requesterType,
        ?int $clinicId,
        int $requestedBy,
        $materials,
        Carbon $baseDate,
        array $statuses,
        array $newStatuses,
        int &$totalRequests,
        int &$totalItems,
        int &$totalNew
    ): void {
        $seed = crc32($requesterType . ($clinicId ?? 0) . $baseDate->toDateString() . $totalRequests);

        $status = $statuses[$seed % count($statuses)];

        $request = MaterialRequest::create([
            'requested_by'    => $requestedBy,
            'requester_type'  => $requesterType,
            'clinic_id'       => $clinicId,
            'status'          => $status,
            'notes'           => $requesterType === 'clinic'
                ? 'طلب مواد للعيادة - آب 2026'
                : 'طلب مواد للمخبر - آب 2026',
            'warehouse_notes' => in_array($status, ['completed', 'rejected', 'cancelled'])
                ? 'تمت المراجعة من المستودع'
                : null,
            'created_at'      => $baseDate->copy()->setTime(9 + ($seed % 8), ($seed % 50)),
            'updated_at'      => $baseDate->copy()->addHours(1 + ($seed % 40)),
        ]);

        $totalRequests++;

        // ---------- Material Request Items (1–4) ----------
        // No status on items — warehouse completes the whole request
        $itemCount = 1 + ($seed % 4);
        $picked = $materials->shuffle()->take(min($itemCount, $materials->count()));

        foreach ($picked as $idx => $mat) {
            MaterialRequestItem::create([
                'material_request_id' => $request->id,
                'material_id'         => $mat->id,
                'quantity_requested'  => 1 + (($seed + $idx) % 20),
                'notes'               => null,
            ]);
            $totalItems++;
        }

        // ---------- New Material Requests (~40% of requests) ----------
        if (($seed % 10) < 4) {
            $newCount = 1 + ($seed % 2);

            $sampleNew = [
                [
                    'material_name' => 'مادة طباعة سيليكون عالية الدقة',
                    'company_name'  => '3M ESPE',
                    'unit'          => 'علبة',
                    'dosage'        => null,
                ],
                [
                    'material_name' => 'أقراص زركون شفافة',
                    'company_name'  => 'Ivoclar',
                    'unit'          => 'قطعة',
                    'dosage'        => null,
                ],
                [
                    'material_name' => 'محلول تنظيف أدوات دقيق',
                    'company_name'  => 'Dürr Dental',
                    'unit'          => 'لتر',
                    'dosage'        => null,
                ],
                [
                    'material_name' => 'قفازات نيتريل خالية من البودرة',
                    'company_name'  => 'Ansell',
                    'unit'          => 'علبة',
                    'dosage'        => null,
                ],
                [
                    'material_name' => 'راتنج طباعة ثلاثية الأبعاد',
                    'company_name'  => 'Formlabs',
                    'unit'          => 'لتر',
                    'dosage'        => null,
                ],
            ];

            for ($n = 0; $n < $newCount; $n++) {
                $sample = $sampleNew[($seed + $n) % count($sampleNew)];

                NewMaterialRequest::create([
                    'material_request_id' => $request->id,
                    'material_name'       => $sample['material_name'],
                    'company_name'        => $sample['company_name'],
                    'unit'                => $sample['unit'],
                    'quantity'            => 2 + (($seed + $n) % 15),
                    'dosage'              => $sample['dosage'],
                    'status'              => $newStatuses[($seed + $n) % count($newStatuses)],
                ]);
                $totalNew++;
            }
        }
    }
}
