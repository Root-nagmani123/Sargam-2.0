# Employee ID Card Request System - Quick Reference

## Quick Start

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Access URLs
- **List Requests:** `http://yoursite.com/admin/employee-idcard`
- **Create New:** `http://yoursite.com/admin/employee-idcard/create`
- **View Details:** `http://yoursite.com/admin/employee-idcard/show/1`
- **Edit Request:** `http://yoursite.com/admin/employee-idcard/edit/1`

### 3. Blade Template Usage
```blade
<!-- Link to list -->
<a href="{{ route('admin.employee_idcard.index') }}">View All Requests</a>

<!-- Link to create -->
<a href="{{ route('admin.employee_idcard.create') }}">New Request</a>

<!-- Link to show -->
<a href="{{ route('admin.employee_idcard.show', $request->id) }}">View Details</a>

<!-- Link to edit -->
<a href="{{ route('admin.employee_idcard.edit', $request->id) }}">Edit</a>
```

## File Structure

```
Created Files:
├── app/Models/EmployeeIDCardRequest.php
├── app/Http/Controllers/Admin/EmployeeIDCardRequestController.php
├── database/migrations/2026_01_30_143659_create_employee_idcard_requests.php
├── resources/views/admin/employee_idcard/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
└── EMPLOYEE_IDCARD_IMPLEMENTATION.md

Modified Files:
└── routes/web.php (Added new routes)
```

## Database Table Schema

```sql
CREATE TABLE employee_idcard_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_type ENUM('Permanent Employee', 'Contractual Employee'),
    card_type VARCHAR(255),
    sub_type VARCHAR(255),
    request_for VARCHAR(255),
    name VARCHAR(255),
    designation VARCHAR(255),
    date_of_birth DATE,
    father_name VARCHAR(255),
    academy_joining DATE,
    id_card_valid_upto VARCHAR(255),
    mobile_number VARCHAR(20),
    telephone_number VARCHAR(20),
    blood_group VARCHAR(10),
    section VARCHAR(255),
    approval_authority VARCHAR(255),
    vendor_organization_name VARCHAR(255),
    photo VARCHAR(255),
    documents VARCHAR(255),
    status ENUM('Pending', 'Approved', 'Rejected', 'Issued') DEFAULT 'Pending',
    remarks TEXT,
    created_by BIGINT UNSIGNED,
    updated_by BIGINT UNSIGNED,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    INDEX created_by (created_by),
    INDEX updated_by (updated_by),
    INDEX status (status)
);
```

## Controller Methods

### index()
- **URL:** GET `/admin/employee-idcard`
- **Returns:** View with paginated list of requests
- **Pagination:** 15 items per page

### create()
- **URL:** GET `/admin/employee-idcard/create`
- **Returns:** Create form view

### store()
- **URL:** POST `/admin/employee-idcard`
- **Accepts:** Form data with file uploads
- **Validates:** All fields as per validation rules
- **Stores:** Files in `storage/app/public/idcard/{photos|documents}`
- **Returns:** Redirect to index with success message

### show()
- **URL:** GET `/admin/employee-idcard/show/{id}`
- **Parameter:** Request ID
- **Returns:** Detailed view of request

### edit()
- **URL:** GET `/admin/employee-idcard/edit/{id}`
- **Parameter:** Request ID
- **Returns:** Edit form with pre-filled data

### update()
- **URL:** PUT `/admin/employee-idcard/update/{id}`
- **Parameter:** Request ID
- **Accepts:** Form data with file uploads
- **Returns:** Redirect to show view with success message

### destroy()
- **URL:** DELETE `/admin/employee-idcard/delete/{id}`
- **Parameter:** Request ID
- **Performs:** Soft delete
- **Returns:** Redirect to index with success message

## Form Fields Explanation

### Employee Type
- Options: Permanent Employee, Contractual Employee
- Default: Permanent Employee
- Required: Yes

### Card Type
- Examples: LBSNAA, Visitor, Contractor
- Optional
- Max 100 characters

### Sub Type
- Examples: Gazetted A Staff, Non-Gazetted, Support Staff
- Optional
- Max 100 characters

### Request For
- Options: Own ID Card, Family ID Card, Replacement
- Optional
- Max 100 characters

### Personal Information
- **Name:** Required, max 255 chars
- **Designation:** Optional
- **Date of Birth:** Optional, valid date
- **Father Name:** Optional
- **Academy Joining:** Optional, valid date

### Contact Information
- **Mobile Number:** Optional, max 20 chars
- **Telephone Number:** Optional, max 20 chars
- **Blood Group:** Optional, should be O+, O-, A+, A-, B+, B-, AB+, AB-

### ID Information
- **ID Card Valid Upto:** Optional
- **Section:** Optional
- **Approval Authority:** Optional
- **Vendor/Organization Name:** Optional

### Documents
- **Photo:** Optional, image file (JPEG, PNG, JPG, GIF), max 2MB
- **Documents:** Optional, PDF/DOC/DOCX, max 5MB

### Status (Admin Only)
- Options: Pending, Approved, Rejected, Issued
- Default: Pending
- Editable in edit form only

### Remarks
- Optional text for admin notes
- Max 65535 characters

## Bootstrap 5 Classes Used

```
Spacing: m-*, p-*, mb-*, mt-*, etc.
Flexbox: d-flex, justify-content-*, align-items-center
Display: d-none, d-block, d-inline-block
Colors: bg-light, bg-primary, text-muted, text-dark, text-danger
Borders: border, border-dashed, rounded-*, rounded-top-*
Shadows: shadow-sm
Grid: row, col-md-*, col-lg-*
Tables: table, table-hover, table-light
Forms: form-control, form-select, form-check, form-label
Buttons: btn, btn-primary, btn-outline-*, btn-danger
Badges: badge, bg-*
Cards: card, card-body, card-header, card-title
Navigation: nav, nav-tabs, nav-link
Tabs: tab-pane, show, active
Alerts: alert, alert-*
Modals: modal, modal-dialog, modal-content (ready for use)
```

## Icons Used

All icons use Material Symbols Rounded from Google Material Icons:
- badge
- add_circle
- visibility
- edit
- delete
- check_circle
- archive
- info
- work
- person
- calendar_today
- settings
- etc.

## Styling Features

### Color Scheme
- Primary: #004a93 (Navy Blue)
- Success: Green (Bootstrap success)
- Warning: Orange (Bootstrap warning)
- Danger: Red (Bootstrap danger)
- Info: Blue (Bootstrap info)
- Light: #f8f9fa (Bootstrap light)

### Hover Effects
- Cards: Subtle shadow increase
- Tables: Light background color on row hover
- Buttons: Bootstrap default hover states
- Upload areas: Background color change with border highlight

### Responsive Design
- Mobile: Single column
- Tablet: Optimized grid
- Desktop: Full layout with sidebars
- All views are fully responsive

## Common Tasks

### Add a New Request
1. Click "Generate New ID Card" button on index page
2. Fill in required fields (Employee Type, Name, Blood Group)
3. Upload photo and/or documents
4. Click "Save"

### View Request Details
1. Click "View" icon in the Actions column
2. See all details organized by sections
3. Download attached documents if available

### Edit a Request
1. Click "Edit" icon in the Actions column (from index)
2. Or click "Edit Request" button on show page
3. Update fields as needed
4. Update status if necessary
5. Click "Update"

### Change Status
1. Edit the request
2. Select new status (Pending, Approved, Rejected, Issued)
3. Add remarks if needed
4. Click "Update"

### Delete a Request
1. Click "Delete" button in Actions column
2. Or click "Delete Request" on show page
3. Confirm deletion
4. Record is soft-deleted (can be recovered from database)

## API Ready

The controller is structured to easily add API endpoints:

```php
Route::apiResource('employee-idcards', EmployeeIDCardRequestController::class);
```

## Performance Considerations

1. **Pagination:** Default 15 items per page to optimize load
2. **Relationships:** Ready to add relationships to Users table
3. **Indexing:** Status field is indexed for quick filtering
4. **Storage:** Files stored in public disk for fast access
5. **Soft Deletes:** Archived records hidden by default

## Security Features

- CSRF protection (Laravel default)
- Form validation
- File type validation
- File size limits
- User authentication required
- Authorization ready (can add middleware)
- SQL injection prevention (Eloquent ORM)
- XSS protection (Blade escaping)

## Next Steps

1. Run migration: `php artisan migrate`
2. Create symbolic link: `php artisan storage:link`
3. Test the application
4. Add authorization policies if needed
5. Customize colors and styling as needed
6. Add email notifications
7. Integrate with existing employee database
8. Add additional fields as required

---

**Created:** January 30, 2026
**Bootstrap Version:** 5.2.3+
**Laravel Version:** 9.x+
**PHP Version:** 8.0+
