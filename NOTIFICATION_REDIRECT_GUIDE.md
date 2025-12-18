# Global Notification Redirect Mechanism - Implementation Guide

## Overview
This system provides a centralized, config-based approach to handle notification redirects. When a user clicks on a notification, the system automatically:
1. Marks the notification as read
2. Determines the appropriate route based on notification type and module
3. Redirects the user to the relevant module view

## Architecture

### 1. Configuration File (`config/notifications.php`)
- Maps notification types and module names to their respective routes
- Easy to maintain and extend
- Supports parameter mapping from notification fields to route parameters

### 2. NotificationService (`app/Services/NotificationService.php`)
- `getRedirectUrl($notificationPk)` - Gets the redirect URL for a notification
- `markAsReadAndGetRedirect($notificationPk)` - Marks as read and returns redirect URL
- Uses the config file to determine the correct route

### 3. NotificationController (`app/Http/Controllers/Admin/NotificationController.php`)
- `markAsReadAndRedirect($id)` - API endpoint that marks notification as read and returns redirect URL
- Handles errors gracefully with fallback to dashboard

### 4. Frontend JavaScript (`resources/views/admin/layouts/header.blade.php`)
- Updated `markAsRead()` function to use the new redirect endpoint
- Automatically redirects users after marking notification as read

## How It Works

### Step-by-Step Flow:
1. User clicks on a notification in the header dropdown
2. JavaScript calls `/admin/notifications/mark-read-redirect/{id}`
3. NotificationController processes the request:
   - Marks notification as read
   - Gets redirect URL from NotificationService
   - Returns JSON response with redirect URL
4. JavaScript receives response and redirects user to the appropriate page

### Route Mapping Logic:
1. System looks up notification `type` and `module_name` in config
2. If exact match found, uses that route configuration
3. If no exact match, tries case-insensitive module name match
4. If still no match, falls back to default route (dashboard)

## Adding New Notification Types

To add support for a new notification type, simply add it to `config/notifications.php`:

```php
'new_type' => [
    'ModuleName' => [
        'route' => 'route.name',
        'params' => ['param_name' => 'reference_pk'], // or static value
    ],
],
```

### Example:
```php
'calendar' => [
    'Event' => [
        'route' => 'calendar.index',
        'params' => ['event_id' => 'reference_pk'],
    ],
],
```

## Route Parameter Mapping

The `params` array maps route parameter names to notification fields:
- `'id' => 'reference_pk'` - Uses notification's `reference_pk` field
- `'type' => 'memo'` - Uses static value 'memo'
- `'course_pk' => 'reference_pk'` - Maps reference_pk to course_pk parameter

## Current Supported Types

- **Course/Programme**: Redirects to `programme.show`
- **Notice**: Redirects to `notice.index`
- **Memo**: Redirects to `memo.notice.management.conversation_student`
- **Student/Member**: Redirects to `member.show`
- **Attendance**: Redirects to `attendance.index`
- **Group Mapping**: Redirects to `group.mapping.index`
- **Faculty**: Redirects to `faculty.show`
- **MDO**: Redirects to `admin.mdo.index`
- **Calendar**: Redirects to `calendar.index`

## Testing

1. Create a notification with a specific type and module
2. Click on the notification in the header
3. Verify it redirects to the correct page
4. Verify the notification is marked as read

## Maintenance

- All route mappings are centralized in `config/notifications.php`
- No need to modify controllers or views when adding new types
- Easy to update route names if they change
- Fallback mechanism ensures system never breaks

## Error Handling

- If route doesn't exist, system falls back to dashboard
- If notification not found, returns dashboard route
- All errors are logged and handled gracefully

