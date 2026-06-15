<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StorePurchaseInvoiceRequest;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockLog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-warehouse-manager');
    }

    // GET /warehouse/purchase-invoices
    public function index()
    {
        $invoices = PurchaseInvoice::with('items.material')
            ->orderBy('invoice_date', 'desc')
            ->get()
            ->map(fn($invoice) => $this->formatInvoice($invoice));

        return response()->json([
            'success' => true,
            'data'    => $invoices,
        ]);
    }

    // GET /warehouse/purchase-invoices/{id}
    public function show($id)
    {
        $invoice = PurchaseInvoice::with('items.material')->find($id);

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

    // POST /warehouse/purchase-invoices
    public function store(StorePurchaseInvoiceRequest $request)
    {
        DB::beginTransaction();

        try {
            // Calculate total amount
            $totalAmount = collect($request->items)->sum(
                fn($item) => $item['quantity'] * $item['unit_price']
            );

            // Create invoice header
            $invoice = PurchaseInvoice::create([
                'supplier_name' => $request->supplier_name,
                'invoice_date'  => $request->invoice_date,
                'total_amount'  => $totalAmount,
                'notes'         => $request->notes,
                'created_by'    => $request->user()->id,
            ]);

            // Create items + update stock
            foreach ($request->items as $item) {
    PurchaseInvoiceItem::create([
        'purchase_invoice_id' => $invoice->id,
        'material_id'         => $item['material_id'],
        'quantity'            => $item['quantity'],
        'unit_price'          => $item['unit_price'],
        'total_price'         => $item['quantity'] * $item['unit_price'],
    ]);

    // Always create a new batch row with its own expiration date
    $stock = WarehouseStock::create([
        'material_id'     => $item['material_id'],
        'quantity'        => $item['quantity'],
        'expiration_date' => $item['expiration_date'] ?? null,
    ]);

    // Log the movement
    WarehouseStockLog::create([
        'warehouse_stock_id' => $stock->id,
        'quantity'           => $item['quantity'],
        'reason'             => "فاتورة شراء #{$invoice->id} — {$request->supplier_name}",
    ]);
}
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء فاتورة الشراء وتحديث المخزون بنجاح',
                'data'    => $this->formatInvoice($invoice->fresh('items.material')),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الفاتورة',
            ], 500);
        }
    }

    // ─── Private Helper ───────────────────────────────────────────

    private function formatInvoice(PurchaseInvoice $invoice): array
    {
        return [
            'id'            => $invoice->id,
            'supplier_name' => $invoice->supplier_name,
            'invoice_date'  => $invoice->invoice_date,
            'total_amount'  => $invoice->total_amount,
            'notes'         => $invoice->notes,
            'created_by'    => $invoice->createdBy->name ?? null,
            'items'         => $invoice->items->map(fn($item) => [
                'id'          => $item->id,
                'material'    => $item->material->name ?? null,
                'unit'        => $item->material->unit ?? null,
                'quantity'    => $item->quantity,
                'unit_price'  => $item->unit_price,
                'total_price' => $item->total_price,
            ]),
            'created_at' => $invoice->created_at->toDateTimeString(),
        ];
    }
}
