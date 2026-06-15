<?php

namespace App\Http\Controllers\LabManager;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-lab-manager');
    }

    // GET /lab/notifications
    public function index()
    {
        $notifications = Notification::where('user_id', request()->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $notifications,
        ]);
    }

    // PATCH /lab/notifications/{id}/read
    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', request()->user()->id)
            ->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'الإشعار غير موجود',
            ], 404);
        }

        $notification->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الإشعار',
        ]);
    }
}
