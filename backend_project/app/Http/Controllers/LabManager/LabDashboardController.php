<?php

namespace App\Http\Controllers\LabManager;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LabDashboardController extends Controller
{
    const LOW_STOCK_THRESHOLD = 10;

    public function __construct()
    {
        Gate::authorize('is-lab-manager');
    }

    // GET /lab/dashboard/orders
    public function orderCounts()
    {
        $today     = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd   = now()->endOfMonth()->toDateString();

        $data = [
            'due_today'       => LabOrder::whereDate('delivery_date_requested', $today)->count(),
            'completed_month' => LabOrder::where('status', 'completed')
                ->whereBetween('updated_at', [$monthStart, $monthEnd])
                ->count(),
            'in_progress'     => LabOrder::where('status', 'in_progress')->count(),
            'new_orders'      => LabOrder::where('status', 'new')->count(),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    // GET /lab/dashboard/technicians
    public function technicianCounts()
    {
        $allTechnicians = Employee::whereHas('user', fn($q) =>
            $q->where('role', UserRole::TECHNICIAN)
        )->with(['user', 'workSchedules'])->get();

        $inShift = $allTechnicians->filter(fn($t) => $t->isCurrentlyInShift());
        $available = $inShift->filter(fn($t) =>
            !LabOrder::where('technician_id', $t->id)
                ->where('status', 'in_progress')
                ->exists()
        );

        $data = [
            'total'         => $allTechnicians->count(),
            'in_shift'      => $inShift->count(),
            'available'     => $available->count(),
            'busy'          => $inShift->count() - $available->count(),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    // GET /lab/dashboard/stock
    public function stockCounts()
    {
        $data = [
            'total_materials'     => LabStock::count(),
            'low_stock_materials' => LabStock::where('quantity', '<=', self::LOW_STOCK_THRESHOLD)->count(),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }


    /**
     * GET /labManager/reports
     * Query params:
     * - period: daily | weekly | monthly | yearly  (required)
     * - date: YYYY-MM-DD (optional, defaults to today)
     */
    public function reports(Request $request)
    {
        $request->validate([
            'period' => 'required|in:daily,weekly,monthly,yearly',
            'date'   => 'nullable|date',
        ], [
            'period.required' => 'يجب تحديد نوع الفترة',
            'period.in'       => 'نوع الفترة يجب أن يكون daily أو weekly أو monthly أو yearly',
        ]);

        $period = $request->period;
        $baseDate = $request->date ? Carbon::parse($request->date) : Carbon::today();

        // Determine date range
        switch ($period) {
            case 'daily':
                $from = $baseDate->copy()->startOfDay();
                $to   = $baseDate->copy()->endOfDay();
                $label = $baseDate->locale('ar')->translatedFormat('d F Y');
                break;

            case 'weekly':
                $from = $baseDate->copy()->startOfWeek(Carbon::SATURDAY); // or Carbon::SUNDAY depending on your week start
                $to   = $baseDate->copy()->endOfWeek(Carbon::FRIDAY);
                $label = 'أسبوع ' . $from->locale('ar')->translatedFormat('d M') . ' - ' . $to->locale('ar')->translatedFormat('d M Y');
                break;

            case 'monthly':
                $from = $baseDate->copy()->startOfMonth();
                $to   = $baseDate->copy()->endOfMonth();
                $label = $baseDate->locale('ar')->translatedFormat('F Y');
                break;

            case 'yearly':
                $from = $baseDate->copy()->startOfYear();
                $to   = $baseDate->copy()->endOfYear();
                $label = $baseDate->format('Y');
                break;
        }

        // ========== Base query for the period ==========
        $ordersQuery = LabOrder::whereBetween('created_at', [$from, $to]);

        // ========== KPIs ==========
        $totalOrders = (clone $ordersQuery)->count();

        $completedOrders = (clone $ordersQuery)
            ->where('status', 'completed')
            ->get();

        $completedOnTime = $completedOrders->filter(function ($order) {
            if (!$order->delivery_date_requested) {
                return false;
            }
            return $order->updated_at->lte(Carbon::parse($order->delivery_date_requested)->endOfDay());
        })->count();

        // Average completion hours (created_at → updated_at)
        $averageHours = 0;
        if ($completedOrders->count() > 0) {
            $totalHours = $completedOrders->sum(function ($order) {
                return $order->created_at->diffInMinutes($order->updated_at) / 60;
            });
            $averageHours = round($totalHours / $completedOrders->count(), 1);
        }

        // ========== Orders by Day of Week ==========
        $ordersByDayRaw = (clone $ordersQuery)
            ->select(DB::raw('DAYOFWEEK(created_at) as day_num'), DB::raw('COUNT(*) as count'))
            ->groupBy('day_num')
            ->pluck('count', 'day_num');

        // MySQL DAYOFWEEK: 1=Sunday, 2=Monday, ... 7=Saturday
        $dayMap = [
            1 => ['day' => 'sunday',    'day_ar' => 'أحد'],
            2 => ['day' => 'monday',    'day_ar' => 'إثنين'],
            3 => ['day' => 'tuesday',   'day_ar' => 'ثلاثاء'],
            4 => ['day' => 'wednesday', 'day_ar' => 'أربعاء'],
            5 => ['day' => 'thursday',  'day_ar' => 'خميس'],
            6 => ['day' => 'friday',    'day_ar' => 'جمعة'],
            7 => ['day' => 'saturday',  'day_ar' => 'سبت'],
        ];

        $ordersByDay = [];
        foreach ($dayMap as $num => $info) {
            $ordersByDay[] = [
                'day'    => $info['day'],
                'day_ar' => $info['day_ar'],
                'count'  => $ordersByDayRaw[$num] ?? 0,
            ];
        }

        // ========== Team Performance ==========
        $teamPerformance = LabOrder::where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('technician_id')
            ->with('technician.user')
            ->get()
            ->groupBy('technician_id')
            ->map(function ($orders, $technicianId) {
                $technician = $orders->first()->technician;
                $totalHours = $orders->sum(function ($order) {
                    return $order->created_at->diffInMinutes($order->updated_at) / 60;
                });

                return [
                    'technician_id'    => (int) $technicianId,
                    'name'             => $technician?->user?->name ?? 'غير معروف',
                    'completed_orders' => $orders->count(),
                    'average_hours'    => round($totalHours / $orders->count(), 1),
                ];
            })
            ->values()
            ->sortByDesc('completed_orders')
            ->values();

        // ========== Orders by Type ==========
        $ordersByTypeRaw = LabOrderItem::whereHas('labOrder', function ($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from, $to]);
            })
            ->join('lab_order_types', 'lab_order_items.lab_order_type_id', '=', 'lab_order_types.id')
            ->select('lab_order_types.type', DB::raw('COUNT(*) as count'))
            ->groupBy('lab_order_types.type')
            ->pluck('count', 'type');

        $totalItems = $ordersByTypeRaw->sum();

        $typeLabels = [
            'crown'  => 'تلييسات',
            'bridge' => 'جسور',
            'other'  => 'أخرى',
        ];

        $ordersByType = [];
        foreach ($ordersByTypeRaw as $type => $count) {
            $ordersByType[] = [
                'type'       => $type,
                'type_ar'    => $typeLabels[$type] ?? $type,
                'count'      => $count,
                'percentage' => $totalItems > 0 ? round(($count / $totalItems) * 100) : 0,
            ];
        }

        // Make sure all main types appear even if 0
        foreach (['crown', 'bridge', 'other'] as $requiredType) {
            if (!$ordersByTypeRaw->has($requiredType)) {
                $ordersByType[] = [
                    'type'       => $requiredType,
                    'type_ar'    => $typeLabels[$requiredType],
                    'count'      => 0,
                    'percentage' => 0,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'type'  => $period,
                    'from'  => $from->toDateString(),
                    'to'    => $to->toDateString(),
                    'label' => $label,
                ],
                'kpis' => [
                    'average_completion_hours' => $averageHours,
                    'completed_on_time'        => $completedOnTime,
                    'total_orders'             => $totalOrders,
                ],
                'orders_by_day'     => $ordersByDay,
                'team_performance'  => $teamPerformance,
                'orders_by_type'    => $ordersByType,
            ],
        ]);
    }

}
