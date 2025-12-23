<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class NotificationService
{
    /**
     * Create a new notification
     * 
     * @param int $receiverUserId The user_id of the receiver (Faculty/Student)
     * @param string $type Notification type (mdo, memo, course, notice, etc.)
     * @param string $moduleName Module name (Duty, Memo, Course, Notice, etc.)
     * @param int $referencePk Reference primary key (duty_pk, memo_pk, course_pk, etc.)
     * @param string $title Notification title
     * @param string $message Notification message
     * @param int|null $senderUserId Optional sender user_id (defaults to current logged-in user)
     * @return Notification
     */
    public function create(
        int $receiverUserId,
        string $type,
        string $moduleName,
        int $referencePk,
        string $title,
        string $message,
        ?int $senderUserId = null
    ): Notification {
        $senderUserId = $senderUserId ?? (Auth::user() ? Auth::user()->user_id : null);

        return Notification::create([
            'sender_user_id' => $senderUserId,
            'receiver_user_id' => $receiverUserId,
            'type' => $type,
            'module_name' => $moduleName,
            'reference_pk' => $referencePk,
            'title' => $title,
            'message' => $message,
            'is_read' => 0,
            'created_at' => now(),
        ]);
    }

    /**
     * Create multiple notifications for multiple receivers
     * 
     * @param array $receiverUserIds Array of receiver user_ids
     * @param string $type Notification type
     * @param string $moduleName Module name
     * @param int $referencePk Reference primary key
     * @param string $title Notification title
     * @param string $message Notification message
     * @param int|null $senderUserId Optional sender user_id
     * @return int Number of notifications created
     */
    public function createMultiple(
        array $receiverUserIds,
        string $type,
        string $moduleName,
        int $referencePk,
        string $title,
        string $message,
        ?int $senderUserId = null
    ): int {
        $senderUserId = $senderUserId ?? (Auth::user() ? Auth::user()->user_id : null);
        $notifications = [];
        $now = now();

        foreach ($receiverUserIds as $receiverUserId) {
            $notifications[] = [
                'sender_user_id' => $senderUserId,
                'receiver_user_id' => $receiverUserId,
                'type' => $type,
                'module_name' => $moduleName,
                'reference_pk' => $referencePk,
                'title' => $title,
                'message' => $message,
                'is_read' => 0,
                'created_at' => $now,
            ];
        }

        return DB::table('notifications')->insert($notifications) ? count($notifications) : 0;
    }

    /**
     * Mark notification as read
     * 
     * @param int $notificationPk Notification primary key
     * @param int|null $userId Optional user ID to verify ownership (for security)
     * @return bool
     */
    public function markAsRead(int $notificationPk, ?int $userId = null): bool
    {
        $query = Notification::where('pk', $notificationPk);
        // Verify notification belongs to user if userId is provided
        if ($userId !== null) {
            $query->where('receiver_user_id', $userId);
        }
        
        // Check if notification exists and belongs to user
        $notification = $query->first();
        if (!$notification) {
            return false;
        }
        
        // If already read, return true (no need to update)
        if ($notification->is_read == 1) {
            return true;
        }
        
        // Update to read status - rebuild query since first() consumed it
        $updateQuery = Notification::where('pk', $notificationPk);
        if ($userId !== null) {
            $updateQuery->where('receiver_user_id', $userId);
        }
        $updated = $updateQuery->update(['is_read' => 1]);
        
        // Return true if update was successful
        return $updated > 0;
    }

    /**
     * Mark multiple notifications as read
     * 
     * @param array $notificationPks Array of notification primary keys
     * @return int Number of notifications marked as read
     */
    public function markMultipleAsRead(array $notificationPks): int
    {
        return Notification::whereIn('pk', $notificationPks)
            ->update(['is_read' => 1]);
    }

    /**
     * Mark all notifications as read for a specific user
     * 
     * @param int $userId User ID
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('receiver_user_id', $userId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);
    }

    /**
     * Get unread notifications for a user
     * 
     * @param int $userId User ID
     * @param int|null $limit Optional limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnreadNotifications(int $userId, ?int $limit = null)
    {
        $query = Notification::where('receiver_user_id', $userId)
            ->where('is_read', 0)
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get all notifications for a user
     * 
     * @param int $userId User ID
     * @param int|null $limit Optional limit
     * @param bool $unreadOnly If true, only return unread notifications
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNotifications(int $userId, ?int $limit = null, bool $unreadOnly = false)
    {
        $query = Notification::where('receiver_user_id', $userId);

        if ($unreadOnly) {
            $query->where('is_read', 0);
        }

        $query->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get unread notification count for a user
     * 
     * @param int $userId User ID
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('receiver_user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }

    /**
     * Get notifications by type
     * 
     * @param int $userId User ID
     * @param string $type Notification type
     * @param int|null $limit Optional limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNotificationsByType(int $userId, string $type, ?int $limit = null)
    {
        $query = Notification::where('receiver_user_id', $userId)
            ->where('type', $type)
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get notifications by module
     * 
     * @param int $userId User ID
     * @param string $moduleName Module name
     * @param int|null $limit Optional limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNotificationsByModule(int $userId, string $moduleName, ?int $limit = null)
    {
        $query = Notification::where('receiver_user_id', $userId)
            ->where('module_name', $moduleName)
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Delete a notification
     * 
     * @param int $notificationPk Notification primary key
     * @return bool
     */
    public function delete(int $notificationPk): bool
    {
        return Notification::where('pk', $notificationPk)->delete() > 0;
    }

    /**
     * Delete notifications by reference
     * 
     * @param string $type Notification type
     * @param int $referencePk Reference primary key
     * @return int Number of notifications deleted
     */
    public function deleteByReference(string $type, int $referencePk): int
    {
        return Notification::where('type', $type)
            ->where('reference_pk', $referencePk)
            ->delete();
    }

    /**
     * Delete old read notifications (cleanup)
     * 
     * @param int $daysOld Number of days old (default: 30)
     * @return int Number of notifications deleted
     */
    public function deleteOldReadNotifications(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);

        return Notification::where('is_read', 1)
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * Get redirect URL for a notification based on type and module
     * 
     * @param int $notificationPk Notification primary key
     * @return string|null Redirect URL or null if no mapping found
     */
    public function getRedirectUrl(int $notificationPk): ?string
    {
        $notification = Notification::find($notificationPk);
        
        if (!$notification) {
            return null;
        }

        $config = config('notifications', []);
        //print_r($config);
        $type = strtolower($notification->type ?? '');
        $moduleName = $notification->module_name ?? '';

       
        // Try to find route mapping by type and module
        if (isset($config[$type]) && is_array($config[$type])) {
            // Check for exact module name match
            if (isset($config[$type][$moduleName])) {
                $routeConfig = $config[$type][$moduleName];
                return $this->buildRouteUrl($routeConfig, $notification);
            }

            // Try case-insensitive module name match
            foreach ($config[$type] as $configModuleName => $routeConfig) {
                if (strtolower($configModuleName) === strtolower($moduleName)) {
                    return $this->buildRouteUrl($routeConfig, $notification);
                }
            }
        }

        // Fallback to default route
        if (isset($config['default'])) {
            return $this->buildRouteUrl($config['default'], $notification);
        }

        // Ultimate fallback to dashboard
        return route('admin.dashboard');
    }

    /**
     * Build route URL from configuration
     * 
     * @param array $routeConfig Route configuration
     * @param Notification $notification Notification instance
     * @return string Route URL
     */
    protected function buildRouteUrl(array $routeConfig, Notification $notification): string
    {
        $routeName = $routeConfig['route'] ?? 'admin.dashboard';
        $params = $routeConfig['params'] ?? [];

        // Build parameters array
        $routeParams = [];
        foreach ($params as $paramName => $sourceField) {
            if ($sourceField === 'reference_pk') {
                $routeParams[$paramName] = $notification->reference_pk;
            } elseif (isset($notification->$sourceField)) {
                $routeParams[$paramName] = $notification->$sourceField;
            } elseif (is_string($sourceField) && !empty($sourceField)) {
                // Static value (e.g., 'type' => 'memo')
                $routeParams[$paramName] = $sourceField;
            }
        }

        // Check if route exists
        try {
            if (Route::has($routeName)) {
                return route($routeName, $routeParams);
            }
        } catch (\Exception $e) {
            // Route doesn't exist or has invalid parameters, fallback to dashboard
        }

        // Fallback to dashboard if route doesn't exist
        return route('admin.dashboard');
    }

    /**
     * Mark notification as read and get redirect URL
     * 
     * @param int $notificationPk Notification primary key
     * @param int|null $userId Optional user ID to verify ownership (for security)
     * @return array ['success' => bool, 'redirect_url' => string|null]
     */
    public function markAsReadAndGetRedirect(int $notificationPk, ?int $userId = null): array
    {
        //echo $notificationPk;
        $marked = $this->markAsRead($notificationPk, $userId);
        $redirectUrl = $this->getRedirectUrl($notificationPk);

        return [
            'success' => $marked,
            'redirect_url' => $redirectUrl,
        ];
    }
}

