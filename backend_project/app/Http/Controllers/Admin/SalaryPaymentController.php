<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\SalaryPayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SalaryPaymentController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-admin');
    }

    /**
     * POST /admin/salary/pay
     * Body: { "employee_id": 5, "year": 2026, "month": 7, "notes": "..." }
     */
    public function pay(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'year'        => 'required|integer|min:2020|max:2100',
            'month'       => 'required|integer|min:1|max:12',
            'notes'       => 'nullable|string|max:1000',
        ], [
            'employee_id.required' => 'يجب تحديد الموظف',
            'employee_id.exists'   => 'الموظف غير موجود',
            'year.required'        => 'يجب تحديد السنة',
            'month.required'       => 'يجب تحديد الشهر',
        ]);

        $year  = (int) $data['year'];
        $month = (int) $data['month'];

        if ($this->isFutureMonth($year, $month)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن دفع راتب لشهر مستقبلي',
            ], 422);
        }

        $existing = SalaryPayment::forPeriod($year, $month)
            ->where('employee_id', $data['employee_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'تم دفع راتب هذا الموظف لهذا الشهر مسبقاً',
                'data'    => $this->formatPayment($existing),
            ], 422);
        }

        $employee = Employee::with(['user', 'dentist'])->findOrFail($data['employee_id']);
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to   = $from->copy()->endOfMonth();
        $calc = $this->calculateForEmployee($employee, $from, $to);

        $payment = SalaryPayment::create([
            'employee_id'        => $employee->id,
            'paid_by'            => auth()->id(),
            'amount'             => $calc['amount'],
            'year'               => $year,
            'month'              => $month,
            'type'               => $calc['type'],
            'base_salary'        => $calc['base_salary'],
            'commission_rate'    => $calc['commission_rate'],
            'treatments_revenue' => $calc['treatments_revenue'],
            'payment_date'       => now()->toDateString(),
            'period'             => $this->periodLabel($year, $month),
            'notes'              => $data['notes'] ?? null,
        ]);

        $payment->load('employee.user');

        return response()->json([
            'success' => true,
            'message' => 'تم دفع الراتب بنجاح',
            'data'    => $this->formatPayment($payment),
        ], 201);
    }

    /**
     * POST /admin/salary/pay-all
     * Body: { "year": 2026, "month": 7, "notes": "..." }
     */
    public function payAll(Request $request): JsonResponse
    {
        $data = $request->validate([
            'year'  => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'notes' => 'nullable|string|max:1000',
        ], [
            'year.required'  => 'يجب تحديد السنة',
            'month.required' => 'يجب تحديد الشهر',
        ]);

        $year  = (int) $data['year'];
        $month = (int) $data['month'];

        if ($this->isFutureMonth($year, $month)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن دفع راتب لشهر مستقبلي',
            ], 422);
        }

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to   = $from->copy()->endOfMonth();

        $employees = Employee::with(['user', 'dentist'])
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get();

        $alreadyPaidIds = SalaryPayment::forPeriod($year, $month)
            ->pluck('employee_id')
            ->all();

        $created = [];
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($employees as $employee) {
                if (in_array($employee->id, $alreadyPaidIds, true)) {
                    $skipped++;
                    continue;
                }

                $calc = $this->calculateForEmployee($employee, $from, $to);

                $payment = SalaryPayment::create([
                    'employee_id'        => $employee->id,
                    'paid_by'            => auth()->id(),
                    'amount'             => $calc['amount'],
                    'year'               => $year,
                    'month'              => $month,
                    'type'               => $calc['type'],
                    'base_salary'        => $calc['base_salary'],
                    'commission_rate'    => $calc['commission_rate'],
                    'treatments_revenue' => $calc['treatments_revenue'],
                    'payment_date'       => now()->toDateString(),
                    'period'             => $this->periodLabel($year, $month),
                    'notes'              => $data['notes'] ?? null,
                ]);

                $payment->load('employee.user');
                $created[] = $this->formatPayment($payment);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'فشل دفع الرواتب: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم دفع رواتب ' . count($created) . ' موظف',
            'summary' => [
                'paid_count'   => count($created),
                'skipped'      => $skipped,
                'total_amount' => round(collect($created)->sum('amount'), 2),
            ],
            'data' => $created,
        ]);
    }

    // ── Calculation ──────────────────────────────────────────────

    private function calculateForEmployee(Employee $employee, Carbon $from, Carbon $to): array
    {
        // Dentist → commission from payments in this month only
        if ($employee->dentist) {
            $dentist = $employee->dentist;
            $rate = (float) ($dentist->commission_rate ?? 0);

            $revenue = (float) Payment::whereBetween('created_at', [$from, $to])
                ->whereHas('invoice.treatment', function ($q) use ($dentist) {
                    $q->where('dentist_id', $dentist->id);
                })
                ->sum('amount');

            $amount = round($revenue * $rate / 100, 2);

            return [
                'type'               => 'commission',
                'base_salary'        => null,
                'commission_rate'    => $rate,
                'treatments_revenue' => round($revenue, 2),
                'amount'             => $amount,
            ];
        }

        // Non-dentist → fixed salary
        $base = (float) ($employee->salary ?? 0);

        return [
            'type'               => 'fixed_salary',
            'base_salary'        => $base,
            'commission_rate'    => null,
            'treatments_revenue' => null,
            'amount'             => round($base, 2),
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function isFutureMonth(int $year, int $month): bool
    {
        $requested = Carbon::create($year, $month, 1)->startOfMonth();
        return $requested->gt(now()->startOfMonth());
    }

    private function periodLabel(int $year, int $month): string
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return ($months[$month] ?? $month) . ' ' . $year;
    }

    private function formatPayment(SalaryPayment $p): array
    {
        return [
            'id'                 => $p->id,
            'employee_id'        => $p->employee_id,
            'employee_name'      => $p->employee?->user?->name,
            'year'               => $p->year,
            'month'              => $p->month,
            'period_label'       => $p->period_label,
            'type'               => $p->type,
            'amount'             => (float) $p->amount,
            'base_salary'        => $p->base_salary !== null ? (float) $p->base_salary : null,
            'commission_rate'    => $p->commission_rate !== null ? (float) $p->commission_rate : null,
            'treatments_revenue' => $p->treatments_revenue !== null ? (float) $p->treatments_revenue : null,
            'payment_date'       => $p->payment_date?->toDateString(),
            'paid_by'            => $p->paid_by,
            'paid_by_name'       => $p->paidBy?->name,
            'notes'              => $p->notes,
            'created_at'         => $p->created_at?->toDateTimeString(),
        ];
    }
}