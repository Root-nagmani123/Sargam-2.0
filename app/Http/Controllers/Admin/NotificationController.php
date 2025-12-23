<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Mark notification as read and redirect
     * 
     * @param int $id Notification ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsReadAndRedirect($id)
    {
        $userId = Auth::user()->user_id ?? null;

        // Verify notification exists and belongs to user
        $notification = Notification::where('pk', $id)
            ->where('receiver_user_id', $userId)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'redirect_url' => route('admin.dashboard'),
                'error' => 'Notification not found or access denied',
            ], 404);
        }
       
        
        $result = $this->notificationService->markAsReadAndGetRedirect($id, $userId);
        
      
        return response()->json([
            'success' => $result['success'],
            'redirect_url' => $result['redirect_url'],
        ]);
    }

    /**
     * Mark notification as read (legacy method for backward compatibility)
     * 
     * @param int $id Notification ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        $result = $this->notificationService->markAsRead($id);
        return response()->json(['success' => $result]);
    }

    /**
     * Mark all notifications as read
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        $userId = Auth::user()->user_id ?? 0;
        $count = $this->notificationService->markAllAsRead($userId);
        return response()->json(['success' => true, 'count' => $count]);
    }
}

