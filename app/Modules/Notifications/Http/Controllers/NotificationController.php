<?php

namespace App\Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications->map(function ($notification) {
            return [
                'message' => $notification->data['message'] ?? 'إشعار جديد',
                'created_at' => $notification->created_at->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'تم اضافة منتج  بنجاح',
            'notifications' => $notifications
        ], 200);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'تم تعليم الإشعار كمقروء']);
    }
}