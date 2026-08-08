<?php

namespace App\Http\Controllers\WarehouseManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseManager\StorePurchaseInvoiceRequest;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockLog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PurchaseInvoiceController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-warehouse-manager');
    }

    // GET /warehouseManager/purchase-invoices
    public function index()
    {
        $invoices = PurchaseInvoice::with(['createdBy'])
            ->orderBy('invoice_date', 'desc')
            ->get()
            ->map(fn($invoice) => $this->formatInvoice($invoice));

        return response()->json([
            'success' => true,
            'data'    => $invoices,
        ]);
    }

    // GET /warehouseManager/purchase-invoices/{id}
    public function show($id)
    {
        $invoice = PurchaseInvoice::with(['items.material', 'createdBy'])->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'الفاتورة غير موجودة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatInvoice($invoice),
        ]);
    }

    // POST /warehouseManager/purchase-invoices
    public function store(StorePurchaseInvoiceRequest $request)
    {
        DB::beginTransaction();

        try {
            $totalAmount = collect($request->items)->sum(
                fn($item) => $item['quantity'] * $item['unit_price']
            );

            $invoice = PurchaseInvoice::create([
                'supplier_name' => $request->supplier_name,
                'invoice_date'  => $request->invoice_date,
                'total_amount'  => $totalAmount,
                'notes'         => $request->notes,
                'created_by'    => $request->user()->id,
            ]);

            foreach ($request->items as $item) {
                // 1) Invoice item
                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'material_id'         => $item['material_id'],
                    'quantity'            => $item['quantity'],
                    'unit_price'          => $item['unit_price'],
                    'total_price'         => $item['quantity'] * $item['unit_price'],
                ]);

                // 2) New stock batch
                $stock = WarehouseStock::create([
                    'material_id'     => $item['material_id'],
                    'quantity'        => $item['quantity'],
                    'expiration_date' => $item['expiration_date'] ?? null,
                ]);

                // 3) Stock log
                WarehouseStockLog::create([
                    'material_id'        => $item['material_id'],
                    'warehouse_stock_id' => $stock->id,
                    'type'               => 'in',
                    'quantity'           => $item['quantity'],
                    'reason'             => 'purchase',
                    'notes'              => "فاتورة شراء #{$invoice->id} — {$request->supplier_name}",
                    'done_by'            => $request->user()->id,
                ]);
            }

            DB::commit();

            $invoice->load(['items.material', 'createdBy']);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء فاتورة الشراء وتحديث المخزون بنجاح',
                'data'    => $this->formatInvoice($invoice),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الفاتورة',
                // 'error'   => $e->getMessage(), // enable while debugging
            ], 500);
        }
    }

    // PATCH /warehouseManager/purchase-invoices/{id}
public function update(Request $request, $id)
{
    $request->validate([
        'supplier_name' => 'sometimes|string|max:255',
        'invoice_date'  => 'sometimes|date',
        'notes'         => 'nullable|string|max:500',
    ], [
        'supplier_name.string' => 'اسم المورد غير صالح',
        'invoice_date.date'    => 'تاريخ الفاتورة غير صالح',
    ]);

    $invoice = PurchaseInvoice::find($id);

    if (!$invoice) {
        return response()->json([
            'success' => false,
            'message' => 'الفاتورة غير موجودة',
        ], 404);
    }

    $invoice->update($request->only(['supplier_name', 'invoice_date', 'notes']));

    $invoice->load(['items.material', 'createdBy']);

    return response()->json([
        'success' => true,
        'message' => 'تم تحديث الفاتورة بنجاح',
        'data'    => $this->formatInvoice($invoice),
    ]);
}

    // ─── Private Helper ───────────────────────────────────────────

    private function formatInvoice(PurchaseInvoice $invoice): array
    {
        return [
            'id'            => $invoice->id,
            'supplier_name' => $invoice->supplier_name,
            'invoice_date'  => $invoice->invoice_date
                ? $invoice->invoice_date->format('Y-m-d')
                : null,
            'total_amount'  => $invoice->total_amount,
            'notes'         => $invoice->notes,
            'created_by'    => $invoice->createdBy->name ?? null,
            'items'         => $invoice->items->map(fn($item) => [
                'id'          => $item->id,
                'material_id' => $item->material_id,
                'material'    => $item->material->name ?? null,
                'unit'        => $item->material->unit ?? null,
                'quantity'    => $item->quantity,
                'unit_price'  => $item->unit_price,
                'total_price' => $item->total_price,
            ])->values(),
            'created_at'    => $invoice->created_at?->toDateTimeString(),
        ];
    }
}
