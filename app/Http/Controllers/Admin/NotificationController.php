<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        try {
            $result = $this->notificationService->markAsReadAndGetRedirect($id);
            
            return response()->json([
                'success' => $result['success'],
                'redirect_url' => $result['redirect_url'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'redirect_url' => route('admin.dashboard'),
                'error' => $e->getMessage(),
            ], 500);
        }
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

