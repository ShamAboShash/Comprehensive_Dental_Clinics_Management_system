<?php

namespace App\Http\Controllers\WarehouseManager;

use App\Http\Controllers\Controller;
use App\Models\WarehouseStockLog;
use App\Models\PurchaseInvoice;
use App\Models\MaterialRequest;
use App\Models\WarehouseMonthlyReport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
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


    // GET /warehouseManager/reports/dashboard?period=month|week&date=2026-08-01

    public function dashboard()
    {
        $period = request('period', 'month'); // week | month
        if (!in_array($period, ['week', 'month'])) {
            return response()->json([
                'success' => false,
                'message' => 'period يجب أن يكون week أو month',
            ], 422);
        }

        $base = request('date') ? Carbon::parse(request('date')) : now();

        if ($period === 'week') {
            $start = $base->copy()->startOfWeek(Carbon::SATURDAY);
            $end   = $base->copy()->endOfWeek(Carbon::FRIDAY);

            // Weeks are always live
            return response()->json([
                'success' => true,
                'data'    => $this->buildReport('week', $start, $end, false),
            ]);
        }

        // Month
        $start = $base->copy()->startOfMonth();
        $end   = $base->copy()->endOfMonth();
        $monthKey = $start->format('Y-m');
        $isCurrentMonth = $monthKey === now()->format('Y-m');

        // Past month → try saved snapshot
        if (!$isCurrentMonth) {
            $saved = WarehouseMonthlyReport::where('month', $monthKey)->first();

            if ($saved) {
                return response()->json([
                    'success' => true,
                    'data'    => array_merge($saved->data, [
                        'saved'        => true,
                        'generated_at' => $saved->updated_at?->toDateTimeString(),
                    ]),
                ]);
            }

            // First request for this past month → compute + save
            $payload = $this->buildReport('month', $start, $end, true);

            WarehouseMonthlyReport::create([
                'month'        => $monthKey,
                'from_date'    => $start->toDateString(),
                'to_date'      => $end->toDateString(),
                'data'         => $payload,
                'generated_by' => request()->user()?->id,
            ]);

            return response()->json([
                'success' => true,
                'data'    => array_merge($payload, [
                    'saved'        => true,
                    'generated_at' => now()->toDateTimeString(),
                ]),
            ]);
        }

        // Current month → live only
        return response()->json([
            'success' => true,
            'data'    => $this->buildReport('month', $start, $end, false),
        ]);
    }

    private function buildReport(string $type, Carbon $start, Carbon $end, bool $saved): array
    {
        return [
            'period' => [
                'type'  => $type,
                'from'  => $start->toDateString(),
                'to'    => $end->toDateString(),
                'month' => $start->format('Y-m'),
            ],
            'saved'                   => $saved,
            'consumption_by_category' => $this->consumptionByCategory($start, $end),
            'calendar'                => $this->calendarHeatmap($start, $end),
            'companies'               => $this->companyPerformance($start, $end),
            'most_consumed'           => $this->mostConsumed($start, $end),
            'summary_bars'            => $this->summaryBars($start, $end),
        ];
    }

    // ─── Donut: clinic / lab / both ───────────────────────────────
    private function consumptionByCategory(Carbon $start, Carbon $end): array
    {
        $rows = WarehouseStockLog::query()
            ->where('warehouse_stock_logs.type', 'out')
->whereBetween('warehouse_stock_logs.created_at', [$start, $end])
->join('materials', 'materials.id', '=', 'warehouse_stock_logs.material_id')
->whereIn('materials.category', ['clinic', 'lab'])
            ->select(
                'materials.category',
                DB::raw('SUM(warehouse_stock_logs.quantity) as total')
            )
            ->groupBy('materials.category')
            ->get();

        $grandTotal = max((float) $rows->sum('total'), 1);

        $labelMap = [
            'clinic' => 'عيادات',
            'lab'    => 'مخبر',
        ];

        $items = $rows->map(function ($row) use ($grandTotal, $labelMap) {
            return [
                'category'   => $row->category,
                'label'      => $labelMap[$row->category] ?? $row->category,
                'quantity'   => (int) $row->total,
                'percentage' => round(($row->total / $grandTotal) * 100, 1),
            ];
        })->values();

        return [
            'total_percentage' => 100,
            'items'            => $items,
        ];
    }

    // ─── Calendar heatmap ─────────────────────────────────────────
    private function calendarHeatmap(Carbon $start, Carbon $end): array
    {
        $rows = WarehouseStockLog::query()
            ->where('type', 'out')
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('SUM(quantity) as total')
            )
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = [];
        $cursor = $start->copy();

        while ($cursor <= $end) {
            $key = $cursor->toDateString();
            $days[] = [
                'date'  => $key,
                'day'   => $cursor->day,
                'value' => (int) ($rows[$key] ?? 0),
            ];
            $cursor->addDay();
        }

        return [
            'max'  => $days ? max(array_column($days, 'value')) : 0,
            'days' => $days,
        ];
    }

    // ─── Companies from materials (not suppliers) ─────────────────
    private function companyPerformance(Carbon $start, Carbon $end): array
    {
        // Invoices that contain items whose material.company_name = X
        $rows = DB::table('purchase_invoices as pi')
            ->join('purchase_invoice_items as pii', 'pii.purchase_invoice_id', '=', 'pi.id')
            ->join('materials as m', 'm.id', '=', 'pii.material_id')
            ->whereBetween('pi.invoice_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('m.company_name')
            ->select(
                'm.company_name',
                DB::raw('COUNT(DISTINCT pi.id) as invoice_count'),
                DB::raw('SUM(pii.total_price) as total_spending')
            )
            ->groupBy('m.company_name')
            ->orderByDesc('invoice_count')
            ->orderByDesc('total_spending')
            ->get();

        return $rows->values()->map(function ($row, $index) {
            return [
                'rank'           => $index + 1,
                'company_name'   => $row->company_name,
                'invoice_count'  => (int) $row->invoice_count,
                'total_spending' => round((float) $row->total_spending, 2),
            ];
        })->toArray();
    }

    // ─── Most consumed materials ──────────────────────────────────
    private function mostConsumed(Carbon $start, Carbon $end, int $limit = 5): array
    {
        $labelMap = [
            'clinic' => 'عيادات',
            'lab'    => 'مخبر',
            'both'   => 'مشترك',
        ];

        return WarehouseStockLog::query()
            ->where('warehouse_stock_logs.type', 'out')
->whereBetween('warehouse_stock_logs.created_at', [$start, $end])
->join('materials', 'materials.id', '=', 'warehouse_stock_logs.material_id')
            ->select(
                'materials.id as material_id',
                'materials.name',
                'materials.company_name',
                'materials.category',
                'materials.unit',
DB::raw('SUM(warehouse_stock_logs.quantity) as total_consumed')            )
            ->groupBy(
                'materials.id',
                'materials.name',
                'materials.company_name',
                'materials.category',
                'materials.unit'
            )
            ->orderByDesc('total_consumed')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($labelMap) {
                return [
                    'material_id'    => $row->material_id,
                    'name'           => $row->name,
                    'company_name'   => $row->company_name,
                    'category'       => $row->category,
                    'category_label' => $labelMap[$row->category] ?? $row->category,
                    'unit'           => $row->unit,
                    'total_consumed' => (int) $row->total_consumed,
                ];
            })
            ->values()
            ->toArray();
    }

    // ─── Summary bars ─────────────────────────────────────────────
    private function summaryBars(Carbon $start, Carbon $end): array
    {
        return WarehouseStockLog::query()
            ->where('type', 'out')
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('SUM(quantity) as total')
            )
            ->groupBy('day')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'label' => $row->day,
                'value' => (int) $row->total,
            ])
            ->values()
            ->toArray();
    }
}

