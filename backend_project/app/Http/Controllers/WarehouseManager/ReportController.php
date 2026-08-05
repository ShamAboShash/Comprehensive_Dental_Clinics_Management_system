<?php

namespace App\Http\Controllers\WarehouseManager;

use App\Http\Controllers\Controller;
use App\Models\WarehouseStockLog;
use App\Models\PurchaseInvoice;
use App\Models\MaterialRequest;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-warehouse-manager');
    }

    // GET /warehouse/reports/stock-movement
    public function stockMovement()
    {
        $from = request('from') ? Carbon::parse(request('from'))->startOfDay() : Carbon::now()->startOfMonth();
        $to   = request('to')   ? Carbon::parse(request('to'))->endOfDay()     : Carbon::now()->endOfDay();

        $logs = WarehouseStockLog::with('warehouseStock.material')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->get();

        $incoming = $logs->where('quantity', '>', 0);
        $outgoing = $logs->where('quantity', '<', 0);

        return response()->json([
            'success' => true,
            'data'    => [
                'period' => [
                    'from' => $from->toDateString(),
                    'to'   => $to->toDateString(),
                ],
                'summary' => [
                    'total_incoming' => round($incoming->sum('quantity'), 2),
                    'total_outgoing' => round(abs($outgoing->sum('quantity')), 2),
                    'total_movements' => $logs->count(),
                ],
                'incoming' => $incoming->map(fn($log) => $this->formatLog($log))->values(),
                'outgoing' => $outgoing->map(fn($log) => $this->formatLog($log))->values(),
            ],
        ]);
    }

    // GET /warehouse/reports/purchase-invoices
    public function purchaseInvoices()
    {
        $from = request('from') ? Carbon::parse(request('from'))->startOfDay() : Carbon::now()->startOfMonth();
        $to   = request('to')   ? Carbon::parse(request('to'))->endOfDay()     : Carbon::now()->endOfDay();

        $invoices = PurchaseInvoice::with('items.material')
            ->whereBetween('invoice_date', [$from, $to])
            ->orderBy('invoice_date', 'desc')
            ->get();

        // Group spending by supplier
        $bySupplier = $invoices->groupBy('supplier_name')->map(fn($group, $supplier) => [
            'supplier'       => $supplier,
            'invoice_count'  => $group->count(),
            'total_spending' => round($group->sum('total_amount'), 2),
        ])->values();

        // Group spending by month
        $byMonth = $invoices->groupBy(fn($i) => Carbon::parse($i->invoice_date)->format('Y-m'))
            ->map(fn($group, $month) => [
                'month'          => $month,
                'invoice_count'  => $group->count(),
                'total_spending' => round($group->sum('total_amount'), 2),
            ])->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'period' => [
                    'from' => $from->toDateString(),
                    'to'   => $to->toDateString(),
                ],
                'summary' => [
                    'total_invoices' => $invoices->count(),
                    'total_spending' => round($invoices->sum('total_amount'), 2),
                ],
                'by_supplier' => $bySupplier,
                'by_month'    => $byMonth,
                'invoices'    => $invoices->map(fn($invoice) => [
                    'id'            => $invoice->id,
                    'supplier_name' => $invoice->supplier_name,
                    'invoice_date'  => $invoice->invoice_date,
                    'total_amount'  => $invoice->total_amount,
                    'items_count'   => $invoice->items->count(),
                ])->values(),
            ],
        ]);
    }

    // GET /warehouse/reports/material-requests
    public function materialRequests()
    {
        $from = request('from') ? Carbon::parse(request('from'))->startOfDay() : Carbon::now()->startOfMonth();
        $to   = request('to')   ? Carbon::parse(request('to'))->endOfDay()     : Carbon::now()->endOfDay();

        $requests = MaterialRequest::with(['items.material', 'requester'])
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->get();

        $fulfilled = $requests->where('status', 'fulfilled');
        $rejected  = $requests->where('status', 'rejected');
        $pending   = $requests->where('status', 'pending');

        // Group by requester
        $byRequester = $requests->groupBy('requester_id')->map(fn($group) => [
            'requester'      => $group->first()->requester->name ?? null,
            'total_requests' => $group->count(),
            'fulfilled'      => $group->where('status', 'fulfilled')->count(),
            'rejected'       => $group->where('status', 'rejected')->count(),
            'pending'        => $group->where('status', 'pending')->count(),
        ])->values();

        // Most requested materials
        $materialCounts = $requests->flatMap(fn($r) => $r->items)
            ->groupBy('material_id')
            ->map(fn($items) => [
                'material'       => $items->first()->material->name ?? null,
                'total_quantity' => round($items->sum('quantity'), 2),
                'request_count'  => $items->count(),
            ])
            ->sortByDesc('request_count')
            ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'period' => [
                    'from' => $from->toDateString(),
                    'to'   => $to->toDateString(),
                ],
                'summary' => [
                    'total_requests'     => $requests->count(),
                    'fulfilled_count'    => $fulfilled->count(),
                    'rejected_count'     => $rejected->count(),
                    'pending_count'      => $pending->count(),
                    'fulfillment_rate'   => $requests->count() > 0
                        ? round(($fulfilled->count() / $requests->count()) * 100, 1) . '%'
                        : '0%',
                ],
                'by_requester'        => $byRequester,
                'most_requested_materials' => $materialCounts,
                'requests'            => $requests->map(fn($r) => [
                    'id'          => $r->id,
                    'requester'   => $r->requester->name ?? null,
                    'status'      => $r->status,
                    'items_count' => $r->items->count(),
                    'created_at'  => $r->created_at->toDateTimeString(),
                ])->values(),
            ],
        ]);
    }

    // ─── Private Helper ───────────────────────────────────────────

    private function formatLog(WarehouseStockLog $log): array
    {
        return [
            'id'       => $log->id,
            'material' => $log->warehouseStock->material->name ?? null,
            'quantity' => abs($log->quantity),
            'reason'   => $log->reason,
            'date'     => $log->created_at->toDateTimeString(),
        ];
    }
}
