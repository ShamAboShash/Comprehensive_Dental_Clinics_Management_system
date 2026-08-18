<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\Clinic;
use App\Models\Dentist;
use App\Models\DentistRating;
use App\Models\Employee;
use App\Models\LabOrder;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\SalaryPayment;
use App\Models\Treatment;
use App\Models\WarehouseFulfillment;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class InventoryController extends Controller
{
    const LOW_STOCK_THRESHOLD = 10;

    public function __construct()
    {
        Gate::authorize('is-admin');
    }

    /**
     * GET /admin/inventory?year=2026&month=7
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

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to   = $from->copy()->endOfMonth();

        if ($year === (int) now()->year && $month === (int) now()->month) {
            $to = now()->endOfDay();
        }

        $financial = $this->financials($from, $to);
        $warehouse = $this->warehouseMovement($from, $to);
        $clinics   = $this->clinicStats($from, $to);
        $lowStock  = $this->materialsNeedingReorder();
        $top       = $this->topPerformers($from, $to);

        $monthsAr = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return response()->json([
            'success' => true,
            'year'    => $year,
            'month'   => $month,
            'period_label' => ($monthsAr[$month] ?? $month) . ' ' . $year,
            'period'  => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
            'data' => [
                'financial' => [
                    'total_revenue'  => $financial['total_revenue'],
                    'total_expenses' => $financial['total_expenses'],
                    'net_profit'     => $financial['net_profit'],
                    'breakdown'      => $financial['breakdown'],
                ],
                'warehouse' => [
                    'materials_used'    => $warehouse['materials_used'],
                    'materials_damaged' => $warehouse['materials_damaged'],
                    'summary'           => $warehouse['summary'],
                ],
                'clinics' => $clinics,
                'materials_needing_reorder' => $lowStock,
                'best_doctor'              => $top['best_doctor'],
                'best_technician'          => $top['best_technician'],
                'most_requested_treatment' => $top['most_requested_treatment'],
                'most_sold_material'       => $top['most_sold_material'],
                'staff_highlights'         => $top['staff_highlights'],
                'alerts'                   => $this->alerts($lowStock, $from, $to),
            ],
        ]);
    }

    private function financials(Carbon $from, Carbon $to): array
    {
        $totalRevenue = round((float) Payment::whereBetween('created_at', [$from, $to])->sum('amount'), 2);

        $payments = Payment::with(['invoice.treatment.dentist'])
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $dentistCommissions = 0.0;
        foreach ($payments as $p) {
            $dentist = $p->invoice?->treatment?->dentist;
            if (!$dentist) {
                continue;
            }
            $rate = (float) ($dentist->commission_rate ?? 0);
            $dentistCommissions += (float) $p->amount * $rate / 100;
        }
        $dentistCommissions = round($dentistCommissions, 2);
        $treatmentNet = round($totalRevenue - $dentistCommissions, 2);

        $employeeSalaries = 0.0;
        if (Schema::hasTable('salary_payments') && Schema::hasColumn('salary_payments', 'amount')) {
            $employeeSalaries = round((float) SalaryPayment::whereBetween('payment_date', [$from, $to])->sum('amount'), 2);
        }
        if ($employeeSalaries <= 0) {
            $employeeSalaries = round((float) Employee::whereDoesntHave('dentist')
                ->whereNotNull('salary')
                ->sum('salary'), 2);
        }

        $purchaseExpenses = round((float) PurchaseInvoice::whereBetween('invoice_date', [$from, $to])
            ->sum('total_amount'), 2);

        $totalExpenses = round($employeeSalaries + $purchaseExpenses, 2);
        $netProfit = round($treatmentNet - $totalExpenses, 2);

        return [
            'total_revenue'  => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_profit'     => $netProfit,
            'breakdown'      => [
                'dentist_commissions' => $dentistCommissions,
                'treatment_net'       => $treatmentNet,
                'employee_salaries'   => $employeeSalaries,
                'purchase_expenses'   => $purchaseExpenses,
            ],
        ];
    }

    private function warehouseMovement(Carbon $from, Carbon $to): array
    {
        $usedLogs = WarehouseStockLog::with('material')
            ->where('type', 'out')
            ->whereIn('reason', ['fulfillment', 'adjustment'])
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $materialsUsed = $usedLogs
            ->groupBy('material_id')
            ->map(function ($logs) {
                $m = $logs->first()->material;
                return [
                    'material_id' => $logs->first()->material_id,
                    'name'        => $m->name ?? null,
                    'name_en'     => $m->name_en ?? null,
                    'unit'        => $m->unit ?? null,
                    'quantity'    => (int) $logs->sum('quantity'),
                ];
            })
            ->sortByDesc('quantity')
            ->values()
            ->toArray();

        $damagedLogs = WarehouseStockLog::with('material')
            ->where('type', 'out')
            ->where('reason', 'expired')
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $materialsDamaged = $damagedLogs
            ->groupBy('material_id')
            ->map(function ($logs) {
                $m = $logs->first()->material;
                return [
                    'material_id' => $logs->first()->material_id,
                    'name'        => $m->name ?? null,
                    'name_en'     => $m->name_en ?? null,
                    'unit'        => $m->unit ?? null,
                    'quantity'    => (int) $logs->sum('quantity'),
                ];
            })
            ->sortByDesc('quantity')
            ->values()
            ->toArray();

        $incoming = (int) WarehouseStockLog::where('type', 'in')
            ->whereBetween('created_at', [$from, $to])
            ->sum('quantity');
        $outgoing = (int) WarehouseStockLog::where('type', 'out')
            ->whereBetween('created_at', [$from, $to])
            ->sum('quantity');

        return [
            'materials_used'    => $materialsUsed,
            'materials_damaged' => $materialsDamaged,
            'summary'           => [
                'incoming'          => $incoming,
                'outgoing'          => $outgoing,
                'net'               => $incoming - $outgoing,
                'used_total_qty'    => (int) collect($materialsUsed)->sum('quantity'),
                'damaged_total_qty' => (int) collect($materialsDamaged)->sum('quantity'),
            ],
        ];
    }

    private function clinicStats(Carbon $from, Carbon $to): array
    {
        return Clinic::orderBy('id')->get()->map(function (Clinic $clinic) use ($from, $to) {
            $dentistIds = $clinic->dentists()->pluck('dentists.id');

            $patientsCount = $dentistIds->isEmpty()
                ? 0
                : Appointment::whereIn('dentist_id', $dentistIds)
                    ->whereBetween('appointment_time', [$from, $to])
                    ->distinct('patient_id')
                    ->count('patient_id');

            $appointmentsCount = $dentistIds->isEmpty()
                ? 0
                : Appointment::whereIn('dentist_id', $dentistIds)
                    ->whereBetween('appointment_time', [$from, $to])
                    ->count();

            $expense = 0.0;
            $requestIds = MaterialRequest::where('clinic_id', $clinic->id)
                ->whereBetween('created_at', [$from, $to])
                ->pluck('id');

            if ($requestIds->isNotEmpty()) {
                $fulfillments = WarehouseFulfillment::with('materialRequestItem.material')
                    ->where('status', 'fulfilled')
                    ->whereHas('materialRequestItem', function ($q) use ($requestIds) {
                        $q->whereIn('material_request_id', $requestIds);
                    })
                    ->whereBetween('created_at', [$from, $to])
                    ->get();

                foreach ($fulfillments as $f) {
                    $price = (float) ($f->materialRequestItem?->material?->price_per_unit ?? 0);
                    $expense += $price * (int) $f->quantity_given;
                }
            }

            return [
                'clinic_id'          => $clinic->id,
                'name'               => $clinic->name,
                'room_number'        => $clinic->room_number,
                'is_active'          => (bool) $clinic->is_active,
                'patients_count'     => $patientsCount,
                'appointments_count' => $appointmentsCount,
                'expenses'           => round($expense, 2),
            ];
        })->values()->toArray();
    }

    private function materialsNeedingReorder(): array
    {
        $stocks = WarehouseStock::with('material')->get();

        return $stocks
            ->groupBy('material_id')
            ->map(function ($batches) {
                $total = (int) $batches->sum('quantity');
                $m = $batches->first()->material;
                return [
                    'material_id'    => $batches->first()->material_id,
                    'name'           => $m->name ?? null,
                    'name_en'        => $m->name_en ?? null,
                    'unit'           => $m->unit ?? null,
                    'total_quantity' => $total,
                    'is_low'         => $total <= self::LOW_STOCK_THRESHOLD,
                    'threshold'      => self::LOW_STOCK_THRESHOLD,
                ];
            })
            ->filter(fn ($row) => $row['is_low'])
            ->sortBy('total_quantity')
            ->values()
            ->toArray();
    }

    private function topPerformers(Carbon $from, Carbon $to): array
    {
        $bestDoctorRow = Treatment::whereBetween('created_at', [$from, $to])
            ->select('dentist_id', DB::raw('COUNT(*) as treatments_count'))
            ->groupBy('dentist_id')
            ->orderByDesc('treatments_count')
            ->first();

        $bestDoctor = null;
        if ($bestDoctorRow) {
            $dentist = Dentist::with('employee.user', 'specialization')->find($bestDoctorRow->dentist_id);
            $avgRating = DentistRating::where('dentist_id', $bestDoctorRow->dentist_id)->avg('rating');
            $bestDoctor = [
                'dentist_id'       => $bestDoctorRow->dentist_id,
                'name'             => $dentist?->employee?->user?->name,
                'specialization'   => $dentist?->specialization?->name,
                'treatments_count' => (int) $bestDoctorRow->treatments_count,
                'average_rating'   => $avgRating !== null ? round((float) $avgRating, 2) : null,
            ];
        }

        $bestTechRow = LabOrder::where('status', 'completed')
            ->whereNotNull('technician_id')
            ->whereBetween('updated_at', [$from, $to])
            ->select('technician_id', DB::raw('COUNT(*) as orders_count'))
            ->groupBy('technician_id')
            ->orderByDesc('orders_count')
            ->first();

        $bestTechnician = null;
        if ($bestTechRow) {
            $tech = Employee::with('user')->find($bestTechRow->technician_id);
            $bestTechnician = [
                'employee_id'  => $bestTechRow->technician_id,
                'name'         => $tech?->user?->name,
                'orders_count' => (int) $bestTechRow->orders_count,
            ];
        }

        $topTreatment = Treatment::with('treatmentType:id,name,name_en')
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy('treatment_type_id')
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
            ->first();

        $topMaterialLog = WarehouseStockLog::with('material')
            ->where('type', 'out')
            ->whereBetween('created_at', [$from, $to])
            ->select('material_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('material_id')
            ->orderByDesc('total_qty')
            ->first();

        $mostSoldMaterial = null;
        if ($topMaterialLog) {
            $m = $topMaterialLog->material ?? Material::find($topMaterialLog->material_id);
            $mostSoldMaterial = [
                'material_id' => $topMaterialLog->material_id,
                'name'        => $m->name ?? null,
                'name_en'     => $m->name_en ?? null,
                'unit'        => $m->unit ?? null,
                'quantity'    => (int) $topMaterialLog->total_qty,
            ];
        }

        $mostAttendance = null;
        if (Schema::hasTable('attendance')) {
            $row = Attendance::whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->where('status', 'present')
                ->select('employee_id', DB::raw('COUNT(*) as days'))
                ->groupBy('employee_id')
                ->orderByDesc('days')
                ->first();
            if ($row) {
                $emp = Employee::with('user')->find($row->employee_id);
                $mostAttendance = [
                    'employee_id'  => $row->employee_id,
                    'name'         => $emp?->user?->name,
                    'present_days' => (int) $row->days,
                ];
            }
        }

        return [
            'best_doctor'              => $bestDoctor,
            'best_technician'          => $bestTechnician,
            'most_requested_treatment' => $topTreatment ?: null,
            'most_sold_material'       => $mostSoldMaterial,
            'staff_highlights'         => [
                'most_committed_attendance' => $mostAttendance,
                'best_doctor'               => $bestDoctor,
                'best_technician'           => $bestTechnician,
            ],
        ];
    }

    private function alerts(array $lowStock, Carbon $from, Carbon $to): array
    {
        $alerts = [];

        if (count($lowStock) > 0) {
            $alerts[] = [
                'type'    => 'low_stock',
                'message' => 'يوجد مواد ناقصة في المستودع',
                'count'   => count($lowStock),
            ];
        }

        $lateLab = LabOrder::whereIn('status', ['new', 'in_progress'])
            ->whereNotNull('delivery_date_requested')
            ->whereDate('delivery_date_requested', '<', now()->toDateString())
            ->count();

        if ($lateLab > 0) {
            $alerts[] = [
                'type'    => 'late_lab_orders',
                'message' => 'هناك طلبات مخبر متأخرة',
                'count'   => $lateLab,
            ];
        }

        return $alerts;
    }
}
