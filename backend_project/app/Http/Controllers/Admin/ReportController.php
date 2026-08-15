<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Complaint;
use App\Models\Dentist;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\MonthlyReport;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Treatment;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    const LOW_STOCK_THRESHOLD = 10;

    public function __construct()
    {
        Gate::authorize('is-admin');
    }

    /**
     * GET /admin/reports?year=2026&month=7
     *
     * One API only:
     * 1) If row exists in monthly_reports → return it
     * 2) Else calculate
     * 3) If requested month == current month → return live (do NOT save)
     * 4) If requested month != current month (past) → save then return
     */
    public function show(Request $request): JsonResponse
    {
        $year  = (int) ($request->year  ?? now()->year);
        $month = (int) ($request->month ?? now()->month);

        if ($month < 1 || $month > 12 || $year < 2020 || $year > 2100) {
            return response()->json([
                'success' => false,
                'message' => 'الشهر يجب أن يكون بين 1 و 12 والسنة صحيحة',
            ], 422);
        }

        // 1) Already in DB?
        $saved = MonthlyReport::forPeriod($year, $month)->first();
        if ($saved) {
            return response()->json([
                'success' => true,
                'source'  => 'database',
                'data'    => $this->formatForFrontend($this->formatMonthlyReport($saved)),
            ]);
        }

        // 2) Calculate
        $computed = $this->computeMonthlyData($year, $month);

        // 3) Same month as now → do NOT save
        if ($this->isCurrentMonth($year, $month)) {
            return response()->json([
                'success' => true,
                'source'  => 'live',
                'message' => 'الشهر الحالي — البيانات مباشرة وغير محفوظة (جزئية حتى ينتهي الشهر)',
                'data'    => $this->formatForFrontend($computed),
            ]);
        }

        // 4) Future month → do NOT save
        if ($this->isFutureMonth($year, $month)) {
            return response()->json([
                'success' => true,
                'source'  => 'live',
                'message' => 'شهر مستقبلي — لا توجد بيانات للحفظ',
                'data'    => $this->formatForFrontend($computed),
            ]);
        }

        // 5) Past month → save then return
        $report = $this->persistMonthlyReport($year, $month, $computed);

        return response()->json([
            'success' => true,
            'source'  => 'database',
            'message' => 'تم حساب التقرير وحفظه تلقائياً',
            'data'    => $this->formatForFrontend($this->formatMonthlyReport($report)),
        ]);
    }

    // ── Helpers: date checks ─────────────────────────────────────

    private function isCurrentMonth(int $year, int $month): bool
    {
        return $year === (int) now()->year && $month === (int) now()->month;
    }

    private function isFutureMonth(int $year, int $month): bool
    {
        $requested = Carbon::create($year, $month, 1)->startOfMonth();
        $current   = now()->startOfMonth();

        return $requested->gt($current);
    }

    // ── Persist ──────────────────────────────────────────────────

    private function persistMonthlyReport(int $year, int $month, array $data): MonthlyReport
    {
        return MonthlyReport::updateOrCreate(
            ['year' => $year, 'month' => $month],
            [
                'total_revenue'                => $data['financial']['total_revenue'],
                'dentist_commissions'          => $data['financial']['dentist_commissions'],
                'treatment_net'                => $data['financial']['treatment_net'],
                'employee_salaries'            => $data['financial']['employee_salaries'],
                'purchase_expenses'            => $data['financial']['purchase_expenses'],
                'total_expenses'               => $data['financial']['total_expenses'],
                'net_profit'                   => $data['financial']['net_profit'],
                'salaries_paid'                => $data['financial']['employee_salaries'],
                'patients_total'               => $data['counts']['patients_total'],
                'new_patients'                 => $data['counts']['new_patients'],
                'appointments_count'           => $data['counts']['appointments_count'],
                'completed_appointments'       => $data['counts']['completed_appointments'],
                'lab_orders_count'             => $data['counts']['lab_orders_count'],
                'completed_lab_orders'         => $data['counts']['completed_lab_orders'],
                'late_lab_orders'              => $data['counts']['late_lab_orders'],
                'material_requests_count'      => $data['counts']['material_requests_count'],
                'invoices_count'               => $data['counts']['invoices_count'],
                'complaints_count'             => $data['counts']['complaints_count'],
                'low_stock_materials'          => $data['counts']['low_stock_materials'],
                'revenue_by_day'               => $data['revenue_by_day'],
                'treatments_by_type'           => $data['treatments_by_type'],
                'clinic_performance'           => $data['clinic_performance'],
                'lab_orders_by_status'         => $data['lab_orders_by_status'],
                'warehouse_summary'            => $data['warehouse_summary'],
                'top_materials_consumed'       => $data['top_materials_consumed'],
                'appointments_by_status'       => $data['appointments_by_status'],
                'dentist_commission_breakdown' => $data['dentist_commission_breakdown'],
                'extra'                        => $data['extra'] ?? null,
                'generated_by'                 => auth()->id(),
                'generated_at'                 => now(),
            ]
        );
    }

    // ── Shape for frontend UI ────────────────────────────────────

    private function formatForFrontend(array $data): array
    {
        $financial = $data['financial'] ?? [];
        $counts    = $data['counts'] ?? [];
        $labStatus = $data['lab_orders_by_status'] ?? [];

        $lowStock = (int) ($counts['low_stock_materials'] ?? 0);
        $lateLab  = (int) ($counts['late_lab_orders'] ?? 0);

        $alerts = [];
        if ($lowStock > 0) {
            $alerts[] = [
                'type'    => 'low_stock',
                'message' => 'مواد ناقصة',
                'count'   => $lowStock,
            ];
        }
        if ($lateLab > 0) {
            $alerts[] = [
                'type'    => 'late_lab_orders',
                'message' => 'طلبات متأخرة',
                'count'   => $lateLab,
            ];
        }

        return [
            'year'             => $data['year'] ?? null,
            'month'            => $data['month'] ?? null,
            'period_label'     => $data['period_label'] ?? null,
            'is_current_month' => $this->isCurrentMonth(
                (int) ($data['year'] ?? now()->year),
                (int) ($data['month'] ?? now()->month)
            ),
            'is_saved'         => $data['is_saved'] ?? false,
            'generated_at'     => $data['generated_at'] ?? null,

            'kpis' => [
                'low_stock_materials' => $lowStock,
                'appointments'        => (int) ($counts['appointments_count'] ?? 0),
                'patients_total'      => (int) ($counts['patients_total'] ?? 0),
                'revenue'             => (float) ($financial['total_revenue'] ?? 0),
                'today_revenue'       => (float) ($data['today_revenue'] ?? 0),
            ],

            'orders_status' => [
                'new'         => (int) ($labStatus['new'] ?? 0),
                'in_progress' => (int) ($labStatus['in_progress'] ?? 0),
                'completed'   => (int) ($labStatus['completed'] ?? 0),
                'cancelled'   => (int) ($labStatus['cancelled'] ?? 0),
            ],

            'revenue_by_day'     => $data['revenue_by_day'] ?? [],
            'treatments_by_type' => $data['treatments_by_type'] ?? [],
            'alerts'             => $alerts,

            'financial' => [
                'total_revenue'       => (float) ($financial['total_revenue'] ?? 0),
                'dentist_commissions' => (float) ($financial['dentist_commissions'] ?? 0),
                'treatment_net'       => (float) ($financial['treatment_net'] ?? 0),
                'employee_salaries'   => (float) ($financial['employee_salaries'] ?? 0),
                'purchase_expenses'   => (float) ($financial['purchase_expenses'] ?? 0),
                'total_expenses'      => (float) ($financial['total_expenses'] ?? 0),
                'net_profit'          => (float) ($financial['net_profit'] ?? 0),
            ],

            'clinic_performance'           => $data['clinic_performance'] ?? [],
            'warehouse_summary'            => $data['warehouse_summary'] ?? [
                'incoming' => 0,
                'outgoing' => 0,
                'net'      => 0,
            ],
            'top_materials_consumed'       => $data['top_materials_consumed'] ?? [],
            'dentist_commission_breakdown' => $data['dentist_commission_breakdown'] ?? [],
            'counts'                       => $counts,
        ];
    }

    // ── Calculation ──────────────────────────────────────────────

    private function computeMonthlyData(int $year, int $month): array
    {
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to   = $from->copy()->endOfMonth();

        if ($this->isCurrentMonth($year, $month)) {
            $to = now()->endOfDay();
        }

        $monthsAr = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];
        $periodLabel = ($monthsAr[$month] ?? $month) . ' ' . $year;

        $financial = $this->computeFinancials($from, $to);

        $patientsTotal = Patient::count();
        $newPatients   = Patient::whereBetween('created_at', [$from, $to])->count();

        $appointmentsQuery = Appointment::whereBetween('appointment_time', [$from, $to]);
        $appointmentsCount = (clone $appointmentsQuery)->count();
        $completedAppointments = (clone $appointmentsQuery)->where('status', 'completed')->count();
        $appointmentsByStatus = (clone $appointmentsQuery)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $labOrdersQuery = LabOrder::whereBetween('created_at', [$from, $to]);
        $labOrdersCount = (clone $labOrdersQuery)->count();
        $completedLabOrders = (clone $labOrdersQuery)->where('status', 'completed')->count();
        $lateLabOrders = LabOrder::whereIn('status', ['new', 'in_progress'])
            ->whereNotNull('delivery_date_requested')
            ->whereDate('delivery_date_requested', '<', now()->toDateString())
            ->count();
        $labOrdersByStatus = (clone $labOrdersQuery)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $materialRequestsCount = MaterialRequest::whereBetween('created_at', [$from, $to])->count();
        $invoicesCount = Invoice::whereBetween('created_at', [$from, $to])->count();
        $complaintsCount = Complaint::whereBetween('created_at', [$from, $to])->count();
        $lowStock = $this->countLowStockMaterials();

        $revenueByDay = [];
        if ($this->isCurrentMonth($year, $month)) {
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->copy()->subDays($i);
                $revenueByDay[] = [
                    'date'   => $day->toDateString(),
                    'day'    => $day->locale('ar')->translatedFormat('D'),
                    'amount' => round((float) Payment::whereDate('created_at', $day)->sum('amount'), 2),
                ];
            }
        } else {
            $cursor = $from->copy();
            while ($cursor->lte($to)) {
                $revenueByDay[] = [
                    'date'   => $cursor->toDateString(),
                    'day'    => $cursor->locale('ar')->translatedFormat('D'),
                    'amount' => round((float) Payment::whereDate('created_at', $cursor)->sum('amount'), 2),
                ];
                $cursor->addDay();
            }
        }

        $todayRevenue = (float) Payment::whereDate('created_at', now())->sum('amount');

        $incoming = (int) WarehouseStockLog::where('type', 'in')->whereBetween('created_at', [$from, $to])->sum('quantity');
        $outgoing = (int) WarehouseStockLog::where('type', 'out')->whereBetween('created_at', [$from, $to])->sum('quantity');

        $topMaterials = WarehouseStockLog::where('type', 'out')
            ->whereBetween('created_at', [$from, $to])
            ->select('material_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('material_id')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $material = Material::find($row->material_id);
                return [
                    'material_id' => $row->material_id,
                    'name'        => $material->name ?? null,
                    'name_en'     => $material->name_en ?? null,
                    'unit'        => $material->unit ?? null,
                    'quantity'    => (int) $row->total_qty,
                ];
            })
            ->values()
            ->toArray();

        return [
            'year'                         => $year,
            'month'                        => $month,
            'period_label'                 => $periodLabel,
            'period'                       => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'today_revenue'                => $todayRevenue,
            'financial'                    => $financial,
            'counts'                       => [
                'patients_total'          => $patientsTotal,
                'new_patients'            => $newPatients,
                'appointments_count'      => $appointmentsCount,
                'completed_appointments'  => $completedAppointments,
                'lab_orders_count'        => $labOrdersCount,
                'completed_lab_orders'    => $completedLabOrders,
                'late_lab_orders'         => $lateLabOrders,
                'material_requests_count' => $materialRequestsCount,
                'invoices_count'          => $invoicesCount,
                'complaints_count'        => $complaintsCount,
                'low_stock_materials'     => $lowStock,
            ],
            'revenue_by_day'               => $revenueByDay,
            'treatments_by_type'           => $this->treatmentsByType($from, $to),
            'clinic_performance'           => $this->clinicPerformance($from, $to),
            'lab_orders_by_status'         => [
                'new'         => (int) ($labOrdersByStatus['new'] ?? 0),
                'in_progress' => (int) ($labOrdersByStatus['in_progress'] ?? 0),
                'completed'   => (int) ($labOrdersByStatus['completed'] ?? 0),
                'cancelled'   => (int) ($labOrdersByStatus['cancelled'] ?? 0),
            ],
            'appointments_by_status'       => $appointmentsByStatus,
            'warehouse_summary'            => [
                'incoming' => $incoming,
                'outgoing' => $outgoing,
                'net'      => $incoming - $outgoing,
            ],
            'top_materials_consumed'       => $topMaterials,
            'dentist_commission_breakdown' => $financial['dentist_breakdown'],
            'extra'                        => null,
            'generated_at'                 => null,
            'is_saved'                     => false,
        ];
    }

    private function computeFinancials(Carbon $from, Carbon $to): array
    {
        $payments = Payment::with(['invoice.treatment.dentist'])
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $totalRevenue = 0.0;
        $dentistCommissions = 0.0;
        $byDentist = [];

        foreach ($payments as $payment) {
            $amount = (float) $payment->amount;
            $totalRevenue += $amount;

            $dentist = $payment->invoice?->treatment?->dentist;
            if (!$dentist) {
                continue;
            }

            $rate = (float) ($dentist->commission_rate ?? 0);
            $commission = round($amount * $rate / 100, 2);
            $dentistCommissions += $commission;

            $id = $dentist->id;
            if (!isset($byDentist[$id])) {
                $byDentist[$id] = [
                    'dentist_id'      => $id,
                    'commission_rate' => $rate,
                    'revenue'         => 0.0,
                    'commission'      => 0.0,
                ];
            }
            $byDentist[$id]['revenue'] += $amount;
            $byDentist[$id]['commission'] += $commission;
        }

        $totalRevenue = round($totalRevenue, 2);
        $dentistCommissions = round($dentistCommissions, 2);
        $treatmentNet = round($totalRevenue - $dentistCommissions, 2);

        $employeeSalaries = round((float) Employee::whereDoesntHave('dentist')
            ->whereNotNull('salary')
            ->sum('salary'), 2);

        $purchaseExpenses = round((float) PurchaseInvoice::whereBetween('invoice_date', [$from, $to])
            ->sum('total_amount'), 2);

        $totalExpenses = round($employeeSalaries + $purchaseExpenses, 2);
        $netProfit = round($treatmentNet - $totalExpenses, 2);

        $dentistBreakdown = collect($byDentist)->map(function ($row) {
            $dentist = Dentist::with('employee.user')->find($row['dentist_id']);
            return [
                'dentist_id'      => $row['dentist_id'],
                'name'            => $dentist?->employee?->user?->name,
                'commission_rate' => $row['commission_rate'],
                'revenue'         => round($row['revenue'], 2),
                'commission'      => round($row['commission'], 2),
            ];
        })->values()->toArray();

        return [
            'total_revenue'       => $totalRevenue,
            'dentist_commissions' => $dentistCommissions,
            'treatment_net'       => $treatmentNet,
            'employee_salaries'   => $employeeSalaries,
            'purchase_expenses'   => $purchaseExpenses,
            'total_expenses'      => $totalExpenses,
            'net_profit'          => $netProfit,
            'dentist_breakdown'   => $dentistBreakdown,
        ];
    }

    private function formatMonthlyReport(MonthlyReport $r): array
    {
        return [
            'year'                         => $r->year,
            'month'                        => $r->month,
            'period_label'                 => $r->period_label,
            'is_saved'                     => true,
            'generated_at'                 => $r->generated_at?->toDateTimeString(),
            'today_revenue'                => (float) Payment::whereDate('created_at', now())->sum('amount'),
            'financial'                    => [
                'total_revenue'       => (float) $r->total_revenue,
                'dentist_commissions' => (float) $r->dentist_commissions,
                'treatment_net'       => (float) $r->treatment_net,
                'employee_salaries'   => (float) $r->employee_salaries,
                'purchase_expenses'   => (float) $r->purchase_expenses,
                'total_expenses'      => (float) $r->total_expenses,
                'net_profit'          => (float) $r->net_profit,
                'dentist_breakdown'   => $r->dentist_commission_breakdown ?? [],
            ],
            'counts' => [
                'patients_total'          => $r->patients_total,
                'new_patients'            => $r->new_patients,
                'appointments_count'      => $r->appointments_count,
                'completed_appointments'  => $r->completed_appointments,
                'lab_orders_count'        => $r->lab_orders_count,
                'completed_lab_orders'    => $r->completed_lab_orders,
                'late_lab_orders'         => $r->late_lab_orders,
                'material_requests_count' => $r->material_requests_count,
                'invoices_count'          => $r->invoices_count,
                'complaints_count'        => $r->complaints_count,
                'low_stock_materials'     => $r->low_stock_materials,
            ],
            'revenue_by_day'               => $r->revenue_by_day ?? [],
            'treatments_by_type'           => $r->treatments_by_type ?? [],
            'clinic_performance'           => $r->clinic_performance ?? [],
            'lab_orders_by_status'         => $r->lab_orders_by_status ?? [],
            'appointments_by_status'       => $r->appointments_by_status ?? [],
            'warehouse_summary'            => $r->warehouse_summary ?? [],
            'top_materials_consumed'       => $r->top_materials_consumed ?? [],
            'dentist_commission_breakdown' => $r->dentist_commission_breakdown ?? [],
            'extra'                        => $r->extra,
        ];
    }

    private function treatmentsByType(Carbon $from, Carbon $to): array
    {
        return Treatment::with('treatmentType:id,name,name_en')
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy(fn ($t) => $t->treatment_type_id)
            ->map(function ($group) {
                $type = $group->first()->treatmentType;
                return [
                    'treatment_type_id' => $type->id ?? null,
                    'name'              => $type->name ?? 'أخرى',
                    'name_en'           => $type->name_en ?? null,
                    'count'             => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->toArray();
    }

    private function clinicPerformance(Carbon $from, Carbon $to): array
    {
        return Clinic::orderBy('id')->get()->map(function (Clinic $clinic) use ($from, $to) {
            $dentistIds = $clinic->dentists()->pluck('dentists.id');
            $appointments = $dentistIds->isEmpty()
                ? 0
                : Appointment::whereIn('dentist_id', $dentistIds)
                    ->whereBetween('appointment_time', [$from, $to])
                    ->count();
            $materialRequests = MaterialRequest::where('clinic_id', $clinic->id)
                ->whereBetween('created_at', [$from, $to])
                ->count();

            return [
                'clinic_id'         => $clinic->id,
                'name'              => $clinic->name,
                'room_number'       => $clinic->room_number,
                'appointments'      => $appointments,
                'material_requests' => $materialRequests,
                'is_active'         => (bool) $clinic->is_active,
            ];
        })->values()->toArray();
    }

    private function countLowStockMaterials(): int
    {
        return WarehouseStock::select('material_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('material_id')
            ->havingRaw('SUM(quantity) <= ?', [self::LOW_STOCK_THRESHOLD])
            ->get()
            ->count();
    }
}