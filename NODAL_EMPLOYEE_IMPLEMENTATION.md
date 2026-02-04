# Nodal Employee & Mobile Number Implementation

## Overview
Updated the issue management form to dynamically load nodal employees based on selected category and auto-fill complainant's mobile number.

## Changes Made

### 1. **Database Query Implementation**
Added method to fetch nodal employees mapped to a category:

```php
// Query: issue_category_master → issue_category_employee_map → employee_master
$employees = DB::table('issue_category_master as a')
    ->join('issue_category_employee_map as b', 'a.pk', '=', 'b.issue_category_master_pk')
    ->join('employee_master as d', 'b.employee_master_pk', '=', 'd.pk')
    ->where('a.pk', $categoryId)
    ->select([...])
    ->orderBy('priority', 'asc')
    ->get();
```

### 2. **Form Fields Updated**

#### Old Structure:
- Complainant (dropdown) → Nodal Employee (text, readonly)
- Single row layout

#### New Structure:
- Complainant (dropdown) → Mobile Number (text, auto-filled)
- Nodal Employee (dropdown, loads based on category) → Sub Category Name (text, auto-filled)
- Two-row layout for better organization

### 3. **Form HTML Changes**

**Complainant Field:**
```blade
<option value="{{ $employee->employee_pk }}" data-mobile="{{ $employee->mobile }}">
    {{ $employee->employee_name }}
</option>
```
- Added `data-mobile` attribute to store mobile number

**Mobile Number Field (NEW):**
```blade
<input type="text" class="form-control" placeholder="Auto-filled" readonly 
       id="mobile_number" name="mobile_number">
```
- Non-editable field
- Auto-fills from complainant selection

**Nodal Employee Field (CHANGED):**
```blade
<select name="nodal_employee_id" id="nodal_employee" class="form-select" required>
    <option value="">- Select Category First -</option>
</select>
```
- Changed from text input to dropdown
- Dynamically loads based on category
- Required field

### 4. **JavaScript Implementation**

**Category Change Handler:**
```javascript
$('#issue_category').change(function() {
    // 1. Load sub-categories
    $.ajax({
        url: '/admin/issue-management/sub-categories/' + categoryId,
        // ...
    });
    
    // 2. Load nodal employees
    $.ajax({
        url: '/admin/issue-management/nodal-employees/' + categoryId,
        success: function(response) {
            // Populate dropdown with employees ordered by priority
            $.each(response.data, function(key, employee) {
                var fullName = employee.first_name + ' ' + employee.last_name;
                $('#nodal_employee').append(
                    '<option value="'+ employee.employee_id +'">'+ fullName +'</option>'
                );
            });
        }
    });
});
```

**Complainant Change Handler (NEW):**
```javascript
$('#complainant').change(function() {
    var mobile = $(this).find('option:selected').data('mobile');
    $('#mobile_number').val(mobile || '');
});
```

### 5. **Controller Updates**

**New Method: `getNodalEmployees($categoryId)`**
- Fetches employees mapped to category
- Joins three tables: issue_category_master, issue_category_employee_map, employee_master
- Returns JSON response with employee list ordered by priority
- Handles errors gracefully

**Updated Validation in `store()` method:**
```php
'nodal_employee_id' => 'required|integer',
'mobile_number' => 'nullable|string',
```

### 6. **Routes Updated**

Added AJAX endpoint:
```php
Route::get('issue-management/nodal-employees/{categoryId}', 
    [IssueManagementController::class, 'getNodalEmployees'])->name('issue-management.nodal-employees');
```

## Form Flow

1. **User selects Category**
   - Sub-categories load (existing functionality)
   - Nodal employees load (NEW) - filtered by category and sorted by priority

2. **User selects Complainant**
   - Mobile number auto-fills (NEW) in next field - readonly

3. **User selects Sub-Category**
   - Sub category name auto-fills (existing)

4. **User selects Nodal Employee**
   - Selected nodal employee saved with the complaint

## Database Tables Used

- `issue_category_master` - Category information
- `issue_category_employee_map` - Maps employees to categories with priority
- `employee_master` - Employee details (includes mobile number)

## Validation Rules

| Field | Rule | Notes |
|-------|------|-------|
| issue_category_id | required, integer, exists | Existing |
| created_by | required, integer | Complainant - existing |
| nodal_employee_id | required, integer | NEW - must be selected |
| mobile_number | nullable, string | NEW - auto-filled from complainant |
| sub_category_name | required, string | Existing - auto-filled |

## Testing Checklist

- [ ] Category dropdown loads successfully
- [ ] Selecting category loads nodal employees
- [ ] Nodal employees appear in priority order
- [ ] Selecting complainant auto-fills mobile number
- [ ] Mobile number is readonly
- [ ] Form validation works for nodal_employee_id
- [ ] Complaint saves with nodal employee ID
- [ ] Mobile number saves correctly (nullable)
- [ ] Empty mobile numbers handled gracefully

## Files Modified

1. **app/Http/Controllers/Admin/IssueManagement/IssueManagementController.php**
   - Added `getNodalEmployees($categoryId)` method
   - Updated validation in `store()` method

2. **resources/views/admin/issue_management/create.blade.php**
   - Changed form field layout
   - Added mobile number field
   - Changed nodal employee from text to dropdown
   - Updated JavaScript handlers

3. **routes/web.php**
   - Added AJAX route for nodal employees endpoint

## API Response Example

**Request:** `GET /admin/issue-management/nodal-employees/1`

**Response:**
```json
{
    "success": true,
    "message": "Nodal employees fetched successfully",
    "data": [
        {
            "issue_category": "Infrastructure",
            "priority": 1,
            "employee_id": 5,
            "first_name": "John",
            "middle_name": "Kumar",
            "last_name": "Sharma"
        },
        {
            "issue_category": "Infrastructure",
            "priority": 2,
            "employee_id": 8,
            "first_name": "Jane",
            "middle_name": null,
            "last_name": "Patel"
        }
    ]
}
```

## Error Handling

- If category has no nodal employees: "No nodal employees available" message shown
- If AJAX fails: "Error loading employees" message shown
- If mobile number missing: Field remains empty (nullable)
- All database errors logged

## Version
Created: February 2, 2026
Status: Implementation Complete
