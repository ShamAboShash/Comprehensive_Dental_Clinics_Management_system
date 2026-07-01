<?php

namespace App\Http\Controllers\LabManager;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LabOrder;
use App\Models\LabStock;
use App\Models\LabOrderType;
use Illuminate\Support\Facades\Gate;

class LabDashboardController extends Controller
{
    const LOW_STOCK_THRESHOLD = 10;

    public function __construct()
    {
        Gate::authorize('is-lab-manager');
    }

    // GET /lab/dashboard
    // public function index()
    // {
    //     // Call each public method and extract the 'data' part
    //     $ordersData = $this->orderCounts()->getData(true);
    //     $techniciansData = $this->technicianCounts()->getData(true);
    //     $stockData = $this->stockCounts()->getData(true);

    //     return response()->json([
    //         'success' => true,
    //         'data'    => [
    //             'orders'      => $ordersData['data'],
    //             'technicians' => $techniciansData['data'],
    //             'stock'       => $stockData['data'],
    //         ],
    //     ]);
    // }

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
}
