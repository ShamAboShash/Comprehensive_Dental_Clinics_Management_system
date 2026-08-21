<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    /**
     * POST /api/fcm-token
     * Register or update a device token
     */
    public function store(Request $request)
    {
        $request->validate([
            'fcm_token'   => 'required|string',
            'device_type' => 'nullable|in:android,ios,web',
            'device_name' => 'nullable|string|max:100',
        ], [
            'fcm_token.required' => 'رمز الإشعارات مطلوب',
            'device_type.in'     => 'نوع الجهاز يجب أن يكون android أو ios أو web',
        ]);

        $device = UserDevice::updateOrCreate(
            ['fcm_token' => $request->fcm_token],
            [
                'user_id'      => $request->user()->id,
                'device_type'  => $request->device_type,
                'device_name'  => $request->device_name,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ رمز الإشعارات بنجاح',
            'data'    => $device,
        ]);
    }

    /**
     * DELETE /api/fcm-token
     * Remove a device token (usually on logout)
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ], [
            'fcm_token.required' => 'رمز الإشعارات مطلوب',
        ]);

        $deleted = UserDevice::where('user_id', $request->user()->id)
            ->where('fcm_token', $request->fcm_token)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted
                ? 'تم حذف رمز الإشعارات'
                : 'الرمز غير موجود',
        ]);
    }
}