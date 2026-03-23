<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

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
    public function getNotifications(int $userId, ?int $limit = null, bool $unreadOnly = false, ?int $daysOld = null)
    {
        $query = Notification::where('receiver_user_id', $userId);

        if ($unreadOnly) {
            $query->where('is_read', 0);
        }

        if ($daysOld !== null) {
            $query->where('created_at', '>=', now()->subDays($daysOld));
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
    public function getUnreadCount(int $userId, ?int $daysOld = null): int
    {
        $query = Notification::where('receiver_user_id', $userId)
            ->where('is_read', 0);

        if ($daysOld !== null) {
            $query->where('created_at', '>=', now()->subDays($daysOld));
        }

        return $query->count();
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
        log::info($notification);
        
        if (!$notification) {
            return null;
        }

        $config = config('notifications', []);
        //print_r($config);
        $type = strtolower($notification->type ?? '');
        log::info($type);
        $moduleName = $notification->module_name ?? '';

        // Estate: when estate bill is ready, redirect to Generate Estate Bill
        // page for the bill's month and auto-open the specific bill print.
        if ($type === 'estate' && strtolower($moduleName) === 'estatebill') {
            $readingPk = (int) ($notification->reference_pk ?? 0);

            if ($readingPk > 0) {
                $row = DB::table('estate_month_reading_details as emrd')
                    ->leftJoin('estate_possession_details as epd', 'emrd.estate_possession_details_pk', '=', 'epd.pk')
                    ->leftJoin('estate_house_master as ehm', 'epd.estate_house_master_pk', '=', 'ehm.pk')
                    ->where('emrd.pk', $readingPk)
                    ->select([
                        'emrd.bill_no',
                        'emrd.bill_month',
                        'emrd.bill_year',
                        'ehm.estate_unit_sub_type_master_pk as unit_sub_type_pk',
                    ])
                    ->first();

                if ($row) {
                    $billNo = trim((string) ($row->bill_no ?? ''));
                    $billMonthName = trim((string) ($row->bill_month ?? ''));
                    $billYearRaw = trim((string) ($row->bill_year ?? ''));
                    $billYear = $billYearRaw !== '' ? (int) $billYearRaw : 0;

                    $resolveMonthNumber = function (?string $rawMonth) : ?int {
                        $rawMonth = trim((string) ($rawMonth ?? ''));
                        if ($rawMonth === '') return null;

                        $rawMonthLower = strtolower($rawMonth);
                        $monthMap = [
                            'january' => 1, 'jan' => 1,
                            'february' => 2, 'feb' => 2,
                            'march' => 3, 'mar' => 3,
                            'april' => 4, 'apr' => 4,
                            'may' => 5,
                            'june' => 6, 'jun' => 6,
                            'july' => 7, 'jul' => 7,
                            'august' => 8, 'aug' => 8,
                            'september' => 9, 'sep' => 9, 'sept' => 9,
                            'october' => 10, 'oct' => 10,
                            'november' => 11, 'nov' => 11,
                            'december' => 12, 'dec' => 12,
                        ];
                        if (isset($monthMap[$rawMonthLower])) {
                            return $monthMap[$rawMonthLower];
                        }

                        // Numeric month (e.g., 9 or 09)
                        if (preg_match('/^\d{1,2}$/', $rawMonth)) {
                            $n = (int) $rawMonth;
                            return ($n >= 1 && $n <= 12) ? $n : null;
                        }

                        // YYYY-mm passed as bill_month (rare, but handle)
                        if (preg_match('/^\d{4}-\d{1,2}$/', $rawMonth)) {
                            $parts = explode('-', $rawMonth);
                            $n = (int) ($parts[1] ?? 0);
                            return ($n >= 1 && $n <= 12) ? $n : null;
                        }

                        $rawMonthCap = ucfirst($rawMonthLower);
                        try {
                            return \Carbon\Carbon::createFromFormat('F', $rawMonthCap)->month;
                        } catch (\Throwable $e) {}

                        try {
                            return \Carbon\Carbon::createFromFormat('M', $rawMonthCap)->month;
                        } catch (\Throwable $e) {}

                        try {
                            return \Carbon\Carbon::parse('1 ' . $rawMonthCap . ' 2000')->month;
                        } catch (\Throwable $e) {}

                        return null;
                    };

                    $monthNum = $resolveMonthNumber($billMonthName);
                    $billNoInt = (int) $billNo;

                    // If we can build month filter for the Generate Estate Bill page, redirect there.
                    // Even if bill_no is 0/empty, we still redirect to the month page and let the page highlight (if possible).
                    if ($billMonthName !== '' && $billYear > 0 && $monthNum !== null) {
                        $billMonthYm = sprintf('%04d-%02d', $billYear, $monthNum); // matches <input type="month">

                        $query = [
                            'bill_month' => $billMonthYm,
                        ];

                        $unitSubTypePk = (int) ($row->unit_sub_type_pk ?? 0);
                        if ($unitSubTypePk > 0) {
                            $query['unit_sub_type_pk'] = $unitSubTypePk;
                        }

                        // Only auto-open print when bill_no is a valid non-zero integer.
                        if ($billNoInt > 0) {
                            $query['open_estate_bill'] = 1;
                        } else {
                            $query['open_estate_bill'] = 0;
                        }

                        // Provide identifiers so the page can locate/highlight the correct card.
                        // (Even bill_no=0 might still exist as a card identifier.)
                        $query['bill_no'] = (string) $billNoInt;
                        $query['bill_print_month'] = $billMonthName;
                        $query['bill_print_year'] = $billYear;

                        $base = route('admin.estate.generate-estate-bill');
                        return $base . '?' . http_build_query($query);
                    }
                }
            }
        }

       
        // Try to find route mapping by type and module
        if (isset($config[$type]) && is_array($config[$type])) {
            // Check for exact module name match
            if (isset($config[$type][$moduleName])) {
                $routeConfig = $config[$type][$moduleName];
                log::info($routeConfig);
                return $this->buildRouteUrl($routeConfig, $notification);
            }

            // Try case-insensitive module name match
            foreach ($config[$type] as $configModuleName => $routeConfig) {
                if (strtolower($configModuleName) === strtolower($moduleName)) {
                    log::info($routeConfig);
                    return $this->buildRouteUrl($routeConfig, $notification);
                }
            }
        }

        // Fallback to default route
        if (isset($config['default'])) {
            log::info($config['default']);
            return $this->buildRouteUrl($config['default'], $notification);
        }

        // Ultimate fallback to dashboard
        log::info(route('admin.dashboard'));
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
        // print_r($marked);die;
        $redirectUrl = $this->getRedirectUrl($notificationPk);
        // print_r($redirectUrl);die;

        return [
            'success' => $marked,
            'redirect_url' => $redirectUrl,
        ];
    }
}

