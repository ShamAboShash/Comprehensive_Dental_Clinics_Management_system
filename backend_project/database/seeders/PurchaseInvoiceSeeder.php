<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Material;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $warehouseManager = User::where('role', UserRole::WAREHOUSE_MANAGER)->first();
        $materials = Material::all();

        if (!$warehouseManager) {
            $this->command->warn('No WAREHOUSE_MANAGER user found. Run EmployeeSeeder first.');
            return;
        }

        if ($materials->isEmpty()) {
            $this->command->warn('No materials found. Run MaterialSeeder first.');
            return;
        }

        $invoicesData = [
            [
                'supplier_name' => 'شركة الأسنان الحديثة',
                'invoice_date'  => now()->subDays(20)->toDateString(),
                'notes'         => 'شحنة شهر يوليو',
                'items'         => [
                    ['material' => 'قفازات طبية',     'quantity' => 40, 'unit_price' => 500,  'months' => 18],
                    ['material' => 'كمامات طبية',     'quantity' => 30, 'unit_price' => 300,  'months' => 12],
                    ['material' => 'محلول تعقيم',     'quantity' => 15, 'unit_price' => 1500, 'months' => 24],
                ],
            ],
            [
                'supplier_name' => 'مؤسسة دنتال برو',
                'invoice_date'  => now()->subDays(12)->toDateString(),
                'notes'         => 'مواد مخبر',
                'items'         => [
                    ['material' => 'بودرة جبس',       'quantity' => 25, 'unit_price' => 3000,  'months' => 36],
                    ['material' => 'سيليكون طبعة',    'quantity' => 10, 'unit_price' => 12000, 'months' => 18],
                    ['material' => 'مادة طباعة زركون','quantity' => 5,  'unit_price' => 25000, 'months' => 24],
                ],
            ],
            [
                'supplier_name' => 'الشركة الطبية المتحدة',
                'invoice_date'  => now()->subDays(5)->toDateString(),
                'notes'         => null,
                'items'         => [
                    ['material' => 'إبر تخدير',       'quantity' => 50, 'unit_price' => 200,  'months' => 12],
                    ['material' => 'مادة حشو مركبة',  'quantity' => 20, 'unit_price' => 8000, 'months' => 24],
                    ['material' => 'مناديل طبية',     'quantity' => 60, 'unit_price' => 400,  'months' => 18],
                ],
            ],
            [
                'supplier_name' => 'شركة الأسنان الحديثة',
                'invoice_date'  => now()->subDay()->toDateString(),
                'notes'         => 'طلب إضافي عاجل',
                'items'         => [
                    ['material' => 'قفازات طبية',     'quantity' => 20, 'unit_price' => 520, 'months' => 15],
                    ['material' => 'أكواب بلاستيكية', 'quantity' => 40, 'unit_price' => 200, 'months' => 12],
                ],
            ],
        ];

        foreach ($invoicesData as $data) {
            DB::beginTransaction();

            try {
                // Resolve materials by name
                $resolvedItems = [];
                $totalAmount = 0;

                foreach ($data['items'] as $item) {
                    $material = $materials->firstWhere('name', $item['material']);

                    if (!$material) {
                        // try partial match / skip if not found
                        $material = $materials->first(
                            fn($m) => str_contains($m->name, $item['material'])
                        );
                    }

                    if (!$material) {
                        $this->command->warn("Material not found: {$item['material']} — skipped");
                        continue;
                    }

                    $lineTotal = $item['quantity'] * $item['unit_price'];
                    $totalAmount += $lineTotal;

                    $resolvedItems[] = [
                        'material'        => $material,
                        'quantity'        => $item['quantity'],
                        'unit_price'      => $item['unit_price'],
                        'total_price'     => $lineTotal,
                        'expiration_date' => now()->addMonths($item['months'])->toDateString(),
                    ];
                }

                if (empty($resolvedItems)) {
                    DB::rollBack();
                    continue;
                }

                // Create invoice
                $invoice = PurchaseInvoice::create([
                    'created_by'    => $warehouseManager->id,
                    'supplier_name' => $data['supplier_name'],
                    'invoice_date'  => $data['invoice_date'],
                    'total_amount'  => $totalAmount,
                    'notes'         => $data['notes'],
                ]);

                foreach ($resolvedItems as $item) {
                    // Invoice item
                    PurchaseInvoiceItem::create([
                        'purchase_invoice_id' => $invoice->id,
                        'material_id'         => $item['material']->id,
                        'quantity'            => $item['quantity'],
                        'unit_price'          => $item['unit_price'],
                        'total_price'         => $item['total_price'],
                    ]);

                    // New stock batch
                    $stock = WarehouseStock::create([
                        'material_id'     => $item['material']->id,
                        'quantity'        => $item['quantity'],
                        'expiration_date' => $item['expiration_date'],
                    ]);

                    // Stock log
                    WarehouseStockLog::create([
                        'material_id'        => $item['material']->id,
                        'warehouse_stock_id' => $stock->id,
                        'type'               => 'in',
                        'quantity'           => $item['quantity'],
                        'reason'             => 'purchase',
                        'notes'              => "فاتورة شراء #{$invoice->id} — {$data['supplier_name']}",
                        'done_by'            => $warehouseManager->id,
                    ]);
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error('Failed to seed invoice: ' . $e->getMessage());
            }
        }

    }
}
