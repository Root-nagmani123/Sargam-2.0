# Issue Management Create Form - API Logic Integration

## Summary of Changes

The `create.blade.php` file has been updated to use the same logic and data structure as the existing `ApiController`'s `submit_complaint` function.

---

## Key Changes Made

### 1. **Field Name Changes**
   - `issue_category_master_pk` → `issue_category_id`
   - `issue_sub_category_master_pk` → `issue_sub_category_id` (single selection now)
   - Added: `sub_category_name` (auto-fills from selected sub-category)

### 2. **Location Type Changes**
   - Changed from: `location_type` with values (building, hostel, other)
   - Changed to: `location` with values (H, R, O) matching API
     - `H` = Hostel
     - `R` = Residential
     - `O` = Other

### 3. **New Fields Added**
   - **Complainant**: Employee selection dropdown (required)
   - **Nodal Employee**: Auto-fills based on selected complainant
   - **Sub Category Name**: Auto-fills from selected sub-category (readonly)

### 4. **Form Structure**
   ```
   Category → Sub-Category (single select)
   ↓
   Complainant → Nodal Employee (auto-fill)
   ↓
   Sub Category Name (auto-fill)
   ↓
   Description (max 1000 chars)
   ↓
   Location (H/R/O radio buttons)
   ↓
   Building/Hostel Details (conditional)
   ↓
   Images (complaint_img_url - multiple)
   ```

### 5. **Database Mapping Changes**
   - Uses `issue_log_management` table directly (not through Eloquent create)
   - Maps locations to appropriate tables based on type:
     - **H (Hostel)**: `issue_log_hostel_map`
     - **R (Residential)**: `issue_log_hostel_map`
     - **O (Other)**: `issue_log_building_map`
   - Creates sub-category mapping: `issue_log_sub_category_map`
   - Creates status history: `issue_log_status`

### 6. **Image Upload**
   - Changed from: `image` (single file)
   - Changed to: `complaint_img_url` (multiple files)
   - Stores as JSON array in `complaint_img` field

### 7. **JavaScript Logic Updates**
   - Auto-fills `sub_category_name` when sub-category is selected
   - Auto-fills `nodal_employee` when complainant is selected
   - Handles dynamic floor/room loading based on building selection
   - Location type toggle shows/hides building details section

---

## Form Fields Mapping

| Label | Field Name | Type | Required | Notes |
|-------|-----------|------|----------|-------|
| Complaint Category | issue_category_id | select | Yes | Loads sub-categories |
| Complaint Sub-Category | issue_sub_category_id | select | Yes | Populates sub_category_name |
| Complainant | created_by | select | Yes | Employee list |
| Nodal Employee | nodal_employee | text | No | Auto-fills (readonly) |
| Sub Category Name | sub_category_name | text | Yes | Auto-fills (readonly) |
| Detail Description | description | textarea | Yes | Max 1000 chars |
| Location | location | radio | Yes | H, R, O values |
| Building/Hostel | building_master_pk | select | Yes* | *if location selected |
| Floor Name/Type | floor_id | select | No | Loads based on building |
| Room No/House No | room_name | select | No | Loads based on floor |
| Attach Image | complaint_img_url | file | No | Multiple files allowed |

---

## API Compatibility

The form now directly matches the `ApiController::submit_complaint` expectations:
- Same field names
- Same location values (H/R/O)
- Same database insertion logic
- Same image handling (JSON array)
- Same sub-category mapping

---

## Database Insert Logic

The controller now mirrors the API's insert pattern:

```php
// Main issue record
INSERT INTO issue_log_management (
    issue_category_master_pk,
    location,
    description,
    created_by,
    issue_logger,
    issue_status,
    created_date,
    complaint_img
)

// Sub-category mapping
INSERT INTO issue_log_sub_category_map (
    issue_log_management_pk,
    issue_category_master_pk,
    issue_sub_category_master_pk,
    sub_category_name
)

// Location mapping (H)
INSERT INTO issue_log_hostel_map (
    issue_log_management_pk,
    hostel_building_master_pk,
    floor_name,
    room_name
)

// Location mapping (R)
INSERT INTO issue_log_hostel_map (...)

// Location mapping (O)
INSERT INTO issue_log_building_map (
    issue_log_management_pk,
    building_master_pk,
    floor_name,
    room_name
)

// Status history
INSERT INTO issue_log_status (
    issue_log_management_pk,
    issue_status,
    issue_date,
    created_by
)
```

---

## Frontend Features

✅ Character counter for description (0/1000)  
✅ Dynamic sub-category loading based on category  
✅ Auto-fill sub-category name from selection  
✅ Auto-fill nodal employee from complainant selection  
✅ Radio buttons for location type (H/R/O)  
✅ Conditional building details section  
✅ Dynamic floor/room loading based on building  
✅ Multiple image upload support  
✅ Form validation with error messages  
✅ Loading state during submission  

---

## Changes Made to Files

### 1. `resources/views/admin/issue_management/create.blade.php`
- Updated form fields to match API structure
- Changed field names and values
- Updated JavaScript for dynamic loading
- Changed location type from select to radio buttons (H/R/O)
- Added complainant and nodal employee fields
- Changed image upload to multiple files

### 2. `app/Http/Controllers/Admin/IssueManagement/IssueManagementController.php`
- Added `EmployeeMaster` import
- Added `ValidationException` import
- Updated `create()` method to load employees data
- Completely rewrote `store()` method to match API logic
- Uses direct DB insert instead of Eloquent create
- Handles location mapping for H/R/O types
- Validates fields as per API requirements

---

## Testing Checklist

- [ ] Category selection loads sub-categories
- [ ] Sub-category selection auto-fills sub_category_name
- [ ] Complainant selection auto-fills nodal_employee
- [ ] Location radio button shows/hides building section
- [ ] Building selection loads floors
- [ ] Floor selection loads rooms
- [ ] Multiple images can be uploaded
- [ ] Form validation works correctly
- [ ] Complaint is saved with correct structure
- [ ] Database records are created in correct tables

---

## Version
Created: February 2, 2026
Matches: ApiController::submit_complaint logic
Status: Ready for testing
