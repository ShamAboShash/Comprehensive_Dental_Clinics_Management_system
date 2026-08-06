<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\NewMaterialRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaterialRequestSeeder extends Seeder
{
    public function run(): void
    {
        $labManagers = User::where('role', UserRole::LAB_MANAGER)->get();
        $secretaries = User::where('role', UserRole::SECRETARY)->get();
        $materials   = Material::all();
        $clinics     = Clinic::all();

        if ($labManagers->isEmpty()) {
            $this->command->warn('No LAB_MANAGER users found. Run EmployeeSeeder first.');
            return;
        }

        if ($materials->isEmpty()) {
            $this->command->warn('No materials found. Run MaterialSeeder first.');
            return;
        }

        $labMaterials    = $materials->whereIn('category', ['lab', 'both'])->values();
        $clinicMaterials = $materials->whereIn('category', ['clinic', 'both'])->values();

        // =========================================================
        // 1) NEW — Lab request (existing materials only)
        // =========================================================
        $req1 = MaterialRequest::create([
            'requested_by'   => $labManagers->first()->id,
            'requester_type' => 'lab',
            'clinic_id'      => null,
            'status'         => 'new',
            'notes'          => 'نحتاج مواد للمخبر بشكل عاجل',
            'warehouse_notes'=> null,
            'created_at'     => now()->subDays(2),
            'updated_at'     => now()->subDays(2),
        ]);

        foreach ($labMaterials->take(2) as $material) {
            MaterialRequestItem::create([
                'material_request_id' => $req1->id,
                'material_id'         => $material->id,
                'quantity_requested'  => rand(5, 20),
                'status'              => 'pending',
                'notes'               => null,
            ]);
        }

        // =========================================================
        // 2) NEW — Lab request (existing + new materials)
        // =========================================================
        $req2 = MaterialRequest::create([
            'requested_by'   => $labManagers->first()->id,
            'requester_type' => 'lab',
            'clinic_id'      => null,
            'status'         => 'new',
            'notes'          => 'طلب مواد موجودة + مواد جديدة غير متوفرة في النظام',
            'warehouse_notes'=> null,
            'created_at'     => now()->subDay(),
            'updated_at'     => now()->subDay(),
        ]);

        if ($labMaterials->isNotEmpty()) {
            MaterialRequestItem::create([
                'material_request_id' => $req2->id,
                'material_id'         => $labMaterials->first()->id,
                'quantity_requested'  => 10,
                'status'              => 'pending',
            ]);
        }

        NewMaterialRequest::create([
            'material_request_id' => $req2->id,
            'material_name'       => 'شمع نحت الأسنان',
            'company_name'        => 'Renfert',
            'unit'                => 'علبة',
            'quantity'            => 5,
            'dosage'              => null,
            'status'              => 'pending',
        ]);

        NewMaterialRequest::create([
            'material_request_id' => $req2->id,
            'material_name'       => 'قرص زركون شفاف',
            'company_name'        => 'Ivoclar',
            'unit'                => 'قطعة',
            'quantity'            => 3,
            'dosage'              => null,
            'status'              => 'pending',
        ]);

        // =========================================================
        // 3) IN_PROGRESS — warehouse started working
        // =========================================================
        $req3 = MaterialRequest::create([
            'requested_by'   => $labManagers->first()->id,
            'requester_type' => 'lab',
            'clinic_id'      => null,
            'status'         => 'in_progress',
            'notes'          => 'جاري تجهيز الطلب من المستودع',
            'warehouse_notes'=> 'يتم تجهيز الكميات حالياً',
            'created_at'     => now()->subDays(3),
            'updated_at'     => now()->subHours(5),
        ]);

        foreach ($labMaterials->take(2) as $material) {
            MaterialRequestItem::create([
                'material_request_id' => $req3->id,
                'material_id'         => $material->id,
                'quantity_requested'  => rand(8, 15),
                'status'              => 'pending',
            ]);
        }

        // =========================================================
        // 4) COMPLETED — fully fulfilled
        // =========================================================
        $req4 = MaterialRequest::create([
            'requested_by'   => $labManagers->count() > 1
                ? $labManagers->get(1)->id
                : $labManagers->first()->id,
            'requester_type' => 'lab',
            'clinic_id'      => null,
            'status'         => 'completed',
            'notes'          => 'تم استلام كل المواد بنجاح',
            'warehouse_notes'=> 'تم التسليم بالكامل',
            'created_at'     => now()->subDays(10),
            'updated_at'     => now()->subDays(8),
        ]);

        foreach ($labMaterials->take(3) as $material) {
            MaterialRequestItem::create([
                'material_request_id' => $req4->id,
                'material_id'         => $material->id,
                'quantity_requested'  => rand(5, 12),
                'status'              => 'fulfilled',
            ]);
        }

        // =========================================================
        // 5) IN_PROGRESS — partial (some items fulfilled, some not)
        // =========================================================
        $req5 = MaterialRequest::create([
            'requested_by'   => $labManagers->first()->id,
            'requester_type' => 'lab',
            'clinic_id'      => null,
            'status'         => 'in_progress',
            'notes'          => 'تم تلبية جزء من الطلب فقط',
            'warehouse_notes'=> 'بعض المواد غير متوفرة حالياً',
            'created_at'     => now()->subDays(6),
            'updated_at'     => now()->subDays(5),
        ]);

        $partialMaterials = $labMaterials->take(3);
        foreach ($partialMaterials as $index => $material) {
            MaterialRequestItem::create([
                'material_request_id' => $req5->id,
                'material_id'         => $material->id,
                'quantity_requested'  => rand(10, 20),
                'status'              => $index === 0 ? 'fulfilled' : 'not_available',
            ]);
        }

        // =========================================================
        // 6) CANCELLED
        // =========================================================
        $req6 = MaterialRequest::create([
            'requested_by'   => $labManagers->first()->id,
            'requester_type' => 'lab',
            'clinic_id'      => null,
            'status'         => 'cancelled',
            'notes'          => 'تم إلغاء الطلب من قبل المخبر',
            'warehouse_notes'=> null,
            'created_at'     => now()->subDays(12),
            'updated_at'     => now()->subDays(11),
        ]);

        if ($labMaterials->isNotEmpty()) {
            MaterialRequestItem::create([
                'material_request_id' => $req6->id,
                'material_id'         => $labMaterials->first()->id,
                'quantity_requested'  => 7,
                'status'              => 'pending',
            ]);
        }

        // =========================================================
        // 7) NEW — from Clinic (secretary)
        // =========================================================
        if ($secretaries->isNotEmpty() && $clinics->isNotEmpty() && $clinicMaterials->isNotEmpty()) {
            $req7 = MaterialRequest::create([
                'requested_by'   => $secretaries->first()->id,
                'requester_type' => 'clinic',
                'clinic_id'      => $clinics->first()->id,
                'status'         => 'new',
                'notes'          => 'طلب مواد للعيادة',
                'warehouse_notes'=> null,
                'created_at'     => now()->subHours(8),
                'updated_at'     => now()->subHours(8),
            ]);

            foreach ($clinicMaterials->take(2) as $material) {
                MaterialRequestItem::create([
                    'material_request_id' => $req7->id,
                    'material_id'         => $material->id,
                    'quantity_requested'  => rand(10, 30),
                    'status'              => 'pending',
                ]);
            }
        }

        // =========================================================
        // 8) REJECTED — warehouse rejected the request
        // =========================================================
        $req8 = MaterialRequest::create([
            'requested_by'   => $labManagers->first()->id,
            'requester_type' => 'lab',
            'clinic_id'      => null,
            'status'         => 'rejected',
            'notes'          => 'المواد المطلوبة غير متوفرة',
            'warehouse_notes'=> 'سيتم توفيرها عند وصول فاتورة الشراء القادمة',
            'created_at'     => now()->subDays(4),
            'updated_at'     => now()->subDays(3),
        ]);

        if ($labMaterials->isNotEmpty()) {
            MaterialRequestItem::create([
                'material_request_id' => $req8->id,
                'material_id'         => $labMaterials->last()->id,
                'quantity_requested'  => 15,
                'status'              => 'not_available',
            ]);
        }

    }
}
