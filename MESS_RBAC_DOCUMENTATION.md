# Mess Management RBAC (Role-Based Access Control) - Complete Documentation

## Overview
This document describes the complete implementation of RBAC (Role-Based Access Control) for the Mess Management module. This system allows granular permission management where specific users from specific roles can be granted permissions to perform actions like approving purchase orders, managing finance bookings, etc.

## Implementation Date
January 27, 2026

## Database Architecture

### Tables Created

#### 1. `mess_permissions`
Stores permission configuration linking roles to specific actions.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| role_id | BIGINT UNSIGNED | References user_role_master.pk |
| action_name | VARCHAR(100) | Permission action (e.g., 'purchase_order.approve') |
| display_name | VARCHAR(255) | Human-readable name |
| module | VARCHAR(50) | Always 'mess' |
| description | TEXT | Optional description |
| is_active | BOOLEAN | Active status |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |

**Indexes:**
- `role_id` - For role-based queries
- `action_name` - For action lookups
- `is_active` - For active permission queries
- Unique constraint on (`role_id`, `action_name`)

#### 2. `mess_permission_users`
Maps specific users to permissions (many-to-many relationship).

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| mess_permission_id | BIGINT UNSIGNED | References mess_permissions.id |
| user_id | BIGINT UNSIGNED | References user_credentials.pk |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |

**Indexes:**
- `mess_permission_id` - For permission queries
- `user_id` - For user queries
- Unique constraint on (`mess_permission_id`, `user_id`)

**Foreign Keys:**
- `mess_permission_id` → `mess_permissions.id` (CASCADE on delete)

---

## Files Created

### 1. Migrations
- `database/migrations/2026_01_27_120000_create_mess_permissions_table.php`
- `database/migrations/2026_01_27_120001_create_mess_permission_users_table.php`

### 2. Models
- `app/Models/Mess/MessPermission.php`
- `app/Models/Mess/MessPermissionUser.php`

### 3. Controller
- `app/Http/Controllers/Mess/MessPermissionController.php`

### 4. Middleware
- `app/Http/Middleware/CheckMessPermission.php`

### 5. Configuration
- `config/mess.php`

### 6. Views
- `resources/views/admin/mess/permissions/index.blade.php`
- `resources/views/admin/mess/permissions/create.blade.php`
- `resources/views/admin/mess/permissions/edit.blade.php`

---

## Available Permissions

The system supports 20 predefined permission actions:

| Action Name | Display Name | Description |
|-------------|--------------|-------------|
| purchase_order.create | Create Purchase Order | Create new purchase orders |
| purchase_order.approve | Approve Purchase Order | Approve pending purchase orders |
| purchase_order.reject | Reject Purchase Order | Reject purchase orders |
| material_request.create | Create Material Request | Create material indents |
| material_request.approve | Approve Material Request | Approve material requests |
| store_issue.create | Create Store Issue | Issue items from store |
| store_issue.approve | Approve Store Issue | Approve store issues |
| finance_booking.create | Create Finance Booking | Create finance entries |
| finance_booking.approve | Approve Finance Booking | Approve finance bookings |
| finance_booking.reject | Reject Finance Booking | Reject finance bookings |
| invoice.create | Create Invoice | Create invoices |
| invoice.approve | Approve Invoice | Approve invoices |
| vendor.manage | Manage Vendors | Full vendor management |
| inventory.manage | Manage Inventory | Full inventory management |
| store.manage | Manage Stores | Full store management |
| sale_counter.manage | Manage Sale Counters | Manage sale counters |
| credit_limit.manage | Manage Credit Limits | Manage user credit limits |
| menu_rate.manage | Manage Menu Rates | Manage menu pricing |
| reports.view | View Reports | View all reports |
| reports.export | Export Reports | Export reports to Excel/PDF |

---

## Routes

### Resource Routes
```php
Route::resource('permissions', MessPermissionController::class);
```

Generates:
- `GET /admin/mess/permissions` → index (List all permissions)
- `GET /admin/mess/permissions/create` → create (Show create form)
- `POST /admin/mess/permissions` → store (Save new permission)
- `GET /admin/mess/permissions/{id}/edit` → edit (Show edit form)
- `PUT /admin/mess/permissions/{id}` → update (Update permission)
- `DELETE /admin/mess/permissions/{id}` → destroy (Delete permission)

### Additional Routes
```php
GET /admin/mess/permissions/users-by-role → getUsersByRole (AJAX)
GET /admin/mess/permissions/check/{action} → checkPermission (AJAX)
```

---

## Usage Guide

### 1. Creating a Permission

**Access:** Navigate to **Mess Management → Setup & Configuration → Permissions (RBAC)**

**Steps:**
1. Click "Add New Permission"
2. Select **Role** (e.g., Finance Officer, Mess Manager)
3. Select **Permission Action** (e.g., Approve Purchase Order)
4. Enter **Display Name** (auto-filled from action)
5. Add optional **Description**
6. System loads users from selected role via AJAX
7. Select **specific users** who will get this permission
   - Use "Select All" / "Deselect All" buttons
   - Badge shows count of selected users
8. Set **Active** status (default: checked)
9. Click "Create Permission"

**Example:**
```
Role: Finance Officer
Action: purchase_order.approve
Display Name: Approve Purchase Orders
Description: Can approve purchase orders up to ₹50,000
Users Selected: Rajesh Kumar, Priya Sharma (2 users)
Status: Active
```

**Result:**
- Creates 1 record in `mess_permissions`
- Creates 2 records in `mess_permission_users` (one per user)

---

### 2. Editing a Permission

**Access:** Click "Edit" icon on any permission

**Steps:**
1. Modify role, action, or display name
2. Change user assignments:
   - Currently assigned users are pre-checked
   - Can add or remove users
3. Update active status
4. Click "Update Permission"

**Note:** Changing role will reload users from the new role

---

### 3. Deleting a Permission

**Access:** Click "Delete" (trash icon) on any permission

**Confirmation:** "Are you sure? This will remove all user assignments."

**Effect:**
- Deletes permission from `mess_permissions`
- Automatically deletes all user assignments (CASCADE)

---

### 4. Viewing Assigned Users

**Access:** Click "eye" icon next to user count badge

**Modal Shows:**
- Permission name
- List of all assigned users with names and emails

---

## Middleware Usage

### Syntax
```php
Route::middleware(['auth', 'mess.permission:action_name'])
    ->method('/url', [Controller::class, 'method']);
```

### Examples

**Protect Purchase Order Approval:**
```php
Route::post('/admin/mess/purchase-orders/{id}/approve', 
    [PurchaseOrderController::class, 'approve']
)->middleware(['auth', 'mess.permission:purchase_order.approve']);
```

**Protect Finance Booking Approval:**
```php
Route::post('/admin/mess/finance-bookings/{id}/approve',
    [FinanceBookingController::class, 'approve']
)->middleware(['auth', 'mess.permission:finance_booking.approve']);
```

**Protect Multiple Actions:**
```php
Route::middleware(['auth', 'mess.permission:vendor.manage'])
    ->group(function() {
        Route::get('/vendors/create', ...);
        Route::post('/vendors', ...);
        Route::put('/vendors/{id}', ...);
        Route::delete('/vendors/{id}', ...);
    });
```

---

## Configuration

### File: `config/mess.php`

**Enable/Disable RBAC:**
```php
'rbac_enabled' => env('MESS_RBAC_ENABLED', true),
```

**Environment Variable:**
```bash
# .env
MESS_RBAC_ENABLED=true   # Enable permission checks
MESS_RBAC_ENABLED=false  # Skip permission checks (bypass mode)
```

---

## Permission Checking Flow

### 1. Middleware Check
```
User Request → CheckMessPermission Middleware
    ↓
Check if RBAC enabled (config)
    ↓
If disabled → Allow request (bypass)
    ↓
If enabled → Check permission
    ↓
Query: Does user have this permission?
    ↓
YES → Allow request
NO → Redirect with error
```

### 2. Database Query
```sql
SELECT COUNT(*) FROM mess_permissions
INNER JOIN mess_permission_users 
    ON mess_permissions.id = mess_permission_users.mess_permission_id
WHERE mess_permissions.action_name = 'purchase_order.approve'
  AND mess_permissions.is_active = 1
  AND mess_permission_users.user_id = [current_user_id]
```

If count > 0 → Permission granted  
If count = 0 → Permission denied

---

## Security Features

### 1. Duplicate Prevention
- Unique constraint on (`role_id`, `action_name`)
- Controller validates before creating permission
- Error message: "This permission already exists"

### 2. User Assignment Validation
- Validates that users belong to selected role
- Prevents assigning users from different roles
- Validates user exists and is active

### 3. Audit Logging
Middleware logs unauthorized access attempts:
```php
\Log::warning('Unauthorized mess access attempt', [
    'user_id' => $userId,
    'permission' => $permission,
    'url' => $request->url(),
    'ip' => $request->ip()
]);
```

### 4. Cascade Delete
- Deleting permission automatically removes user assignments
- Maintains referential integrity

---

## Model Relationships

### MessPermission Model

**Relationships:**
```php
// Get the role
$permission->role  // UserRoleMaster

// Get assigned users
$permission->users  // Collection of User models

// Get permission-user mappings
$permission->permissionUsers  // Collection of MessPermissionUser
```

**Static Methods:**
```php
// Check if user has permission
MessPermission::userHasPermission($userId, 'purchase_order.approve')

// Get available actions
MessPermission::getAvailableActions()
```

### MessPermissionUser Model

**Relationships:**
```php
// Get the permission
$permissionUser->permission  // MessPermission

// Get the user
$permissionUser->user  // User
```

---

## AJAX Functionality

### Get Users by Role
**Endpoint:** `GET /admin/mess/permissions/users-by-role`

**Request:**
```javascript
$.ajax({
    url: '/admin/mess/permissions/users-by-role',
    data: { role_id: 5 },
    success: function(users) {
        // users = [{pk: 101, name: 'Rajesh', email: '...'}, ...]
    }
});
```

**Response:**
```json
[
    {
        "pk": 101,
        "name": "Rajesh Kumar",
        "email": "rajesh@example.com"
    },
    {
        "pk": 102,
        "name": "Priya Sharma",
        "email": "priya@example.com"
    }
]
```

---

## User Interface Features

### Index Page
- ✅ List all permissions with pagination (15 per page)
- ✅ Show role, action, display name
- ✅ Badge showing user count
- ✅ Modal to view assigned users
- ✅ Active/Inactive status badge
- ✅ Edit and Delete actions
- ✅ Success/Error alerts

### Create Page
- ✅ Role dropdown (active roles only)
- ✅ Action dropdown (20 predefined actions)
- ✅ Auto-fill display name from action
- ✅ AJAX load users on role selection
- ✅ Checkbox list for user selection
- ✅ Select All / Deselect All buttons
- ✅ Live user count badge
- ✅ Active status checkbox
- ✅ Form validation

### Edit Page
- ✅ Pre-filled form data
- ✅ Pre-checked assigned users
- ✅ AJAX reload users on role change
- ✅ Same features as create page

---

## Error Handling

### Common Errors

**1. Permission Already Exists**
```
Message: "This permission already exists for the selected role and action."
Action: User redirected back with input preserved
```

**2. No Users Selected**
```
Validation: "The user ids field is required."
Action: Form validation error shown
```

**3. Unauthorized Access**
```
Message: "You do not have permission to perform this action."
Action: Redirect back with error message
Logging: Warning logged with user ID, action, URL, IP
```

**4. Role/User Not Found**
```
Validation: "The selected role id is invalid."
Action: Form validation error shown
```

---

## Integration with Existing Systems

### 1. Works Alongside Spatie Permission
- **Spatie** handles general system permissions (Admin, Faculty, Student)
- **Mess RBAC** handles mess-specific permissions
- Both can coexist without conflict
- Different middleware, different tables

### 2. Uses Existing Role System
- Leverages `user_role_master` table (existing)
- Leverages `employee_role_mapping` table (existing)
- No changes to existing role structure

### 3. Isolated to Mess Module
- All tables prefixed with `mess_*`
- Middleware only applies to mess routes
- No impact on other modules

---

## Testing Checklist

### Permission Management
- [ ] Create permission with single user
- [ ] Create permission with multiple users
- [ ] Try creating duplicate permission (should fail)
- [ ] Edit permission and change users
- [ ] Edit permission and change role (users should reload)
- [ ] Delete permission (confirm user assignments deleted)
- [ ] Deactivate permission

### Middleware Testing
- [ ] User with permission can access protected route
- [ ] User without permission gets error message
- [ ] Disabled RBAC (`MESS_RBAC_ENABLED=false`) allows all
- [ ] Unauthorized attempt logged in laravel.log
- [ ] JSON request returns 403 status

### AJAX Testing
- [ ] Change role on create page → users load
- [ ] Change role on edit page → users reload
- [ ] Select All button works
- [ ] Deselect All button works
- [ ] User count badge updates

### UI Testing
- [ ] View assigned users modal displays correctly
- [ ] Success messages display
- [ ] Error messages display
- [ ] Form validation works
- [ ] Pagination works
- [ ] Active/Inactive badges correct

---

## Troubleshooting

### Issue: "Table not found"
**Solution:**
```bash
docker-compose exec app php artisan migrate --path=database/migrations/2026_01_27_120000_create_mess_permissions_table.php
docker-compose exec app php artisan migrate --path=database/migrations/2026_01_27_120001_create_mess_permission_users_table.php
```

### Issue: "Middleware not found"
**Solution:** Check `app/Http/Kernel.php` has:
```php
'mess.permission' => \App\Http\Middleware\CheckMessPermission::class,
```

### Issue: "Users not loading"
**Check:**
1. User has role assigned in `employee_role_mapping`
2. User status is active (`active_inactive = 1`)
3. AJAX route exists: `/admin/mess/permissions/users-by-role`

### Issue: "Permission always allowed"
**Check:** `MESS_RBAC_ENABLED` in `.env` is set to `true`

---

## Future Enhancements

1. **Permission Templates**
   - Predefined sets of permissions for common roles
   - One-click assignment

2. **Time-based Permissions**
   - Permissions valid only during specific dates
   - Auto-expire after deadline

3. **Hierarchical Permissions**
   - Parent-child permission structure
   - Inherit permissions from parent

4. **Permission History**
   - Track who granted permission and when
   - Audit trail for permission changes

5. **Conditional Permissions**
   - Amount-based: Approve POs up to ₹50,000
   - Location-based: Only for specific stores
   - Item-based: Only for specific categories

---

## Support

### Logs
Check Laravel logs for permission issues:
```bash
docker-compose exec app tail -f storage/logs/laravel.log
```

### Clear Caches
After any changes:
```bash
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
```

### Database Verification
```sql
-- Check permissions
SELECT * FROM mess_permissions;

-- Check user assignments
SELECT * FROM mess_permission_users;

-- Check specific user permissions
SELECT mp.action_name, mp.display_name 
FROM mess_permissions mp
INNER JOIN mess_permission_users mpu ON mp.id = mpu.mess_permission_id
WHERE mpu.user_id = [USER_ID] AND mp.is_active = 1;
```

---

## Module Status

- ✅ **Database**: 2 tables created and migrated
- ✅ **Models**: 2 Eloquent models with relationships
- ✅ **Controller**: Full CRUD implementation
- ✅ **Middleware**: Permission checking enabled
- ✅ **Views**: 3 views (index, create, edit)
- ✅ **Routes**: All routes registered
- ✅ **Sidebar**: Menu item added
- ✅ **Configuration**: Config file created
- ✅ **AJAX**: Dynamic user loading implemented

**Status**: ✅ Production Ready  
**Last Updated**: January 27, 2026  
**Laravel Version**: 9+  
**Database**: MySQL 5.7+
