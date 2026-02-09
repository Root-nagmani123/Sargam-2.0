# Kitchen Issue Controller Updates Summary

## Date: February 5, 2026

## Overview
Updated `KitchenIssueController.php` to work with the new restructured `kitchen_issue_master` table structure.

## Changes Made

### 1. **index() Method**
- Changed `inve_store_master_pk` → `store_id`
- Changed `request_date` → `issue_date`
- Removed `approve_status` filter
- Added `client_type` and `kitchen_issue_type` filters
- Updated relationship from `storeMaster` to `store`

### 2. **store() Method**
- Updated validation: `inve_store_master_pk` → `store_id`
- Updated validation: payment_type now accepts only `0,1,2` (removed 5)
- Added `client_id` and `name_id` to validation
- Added client_type_slug to numeric mapping:
  - `employee` → 1
  - `ot` → 2
  - `course` → 3
  - `other` → 4
- Removed old columns from create():
  - `inve_item_master_pk`, `quantity`, `unit_price`
  - `user_id`, `request_date`, `created_by`
  - `employee_student_pk`, `approve_status`, `send_for_approval`
  - `notify_status`, `paid_unpaid`, `transfer_to`
- Added new columns to create():
  - `store_id`, `client_id`, `name_id`
  - `kitchen_issue_type` (defaults to TYPE_SELLING_VOUCHER)

### 3. **show() Method**
- Updated relationship from `storeMaster` to `store`
- Removed relationships: `itemMaster`, `paymentDetails`, `approvals`
- Changed `request_date` → `issue_date`
- Used model accessors for labels: `client_type_label`, `payment_type_label`, `kitchen_issue_type_label`, `status_label`

### 4. **edit() Method**
- Changed `approve_status` check to `status` check
- Added numeric to slug mapping for client_type
- Updated all column references:
  - `inve_store_master_pk` → `store_id`
  - Added `client_id`, `name_id`

### 5. **update() Method**
- Updated validation same as store() method
- Changed `approve_status` check to `status` check
- Added client_type_slug to numeric mapping
- Updated all column references
- Removed `modified_by` field (using Laravel timestamps)

### 6. **destroy() Method**
- Changed `approve_status` check to `status` check
- Removed duplicate condition (both were checking status)

### 7. **returnData() Method**
- Updated relationship from `storeMaster` to `store`

### 8. **getKitchenIssueRecords() Method**
- Updated relationships: `storeMaster` → `store`
- Removed: `itemMaster`, `paymentDetails`
- Changed `inve_store_master_pk` → `store_id`
- Changed `request_date` → `issue_date`
- Changed `approve_status` → `status`

### 9. **sendForApproval() Method**
- Changed `send_for_approval` → `status`
- Changed status to `STATUS_PROCESSING` instead of flag
- Removed `notify_status` field

### 10. **billReport() Method**
- Updated relationships
- Changed `inve_store_master_pk` → `store_id`
- Changed `employee_student_pk` → `client_id`
- Changed `request_date` → `issue_date`
- Added `client_type` filter
- Removed `paymentDetails` relationship

## Column Mapping Summary

| Old Column | New Column | Notes |
|-----------|-----------|-------|
| `inve_store_master_pk` | `store_id` | Renamed |
| `request_date` | `issue_date` | Renamed |
| `approve_status` | `status` | Merged into status |
| `employee_student_pk` | `client_id` | Renamed |
| `client_type_slug` | `client_type` | String to numeric (1=Employee, 2=OT, 3=Course, 4=Other) |
| `payment_type` | `payment_type` | Updated values (0=Cash, 1=Credit, 2=Online) |
| - | `name_id` | New field |
| - | `kitchen_issue_type` | New field (1=Selling Voucher, 2=Date Range) |

## Deleted Columns (No Longer Used)
- `inve_item_master_pk` - Items moved to kitchen_issue_items table
- `quantity`, `unit_price` - Per-item data in kitchen_issue_items
- `user_id`, `created_by`, `modified_by` - Using Laravel timestamps
- `transfer_to` - Not needed
- `send_for_approval`, `notify_status` - Merged into status
- `approve_status` - Merged into status
- `paid_unpaid` - Removed

## Next Steps

1. **Update Views** - Forms and display pages need updating:
   - `resources/views/mess/kitchen-issues/index.blade.php`
   - `resources/views/mess/kitchen-issues/create.blade.php`
   - `resources/views/mess/kitchen-issues/edit.blade.php`
   - `resources/views/mess/kitchen-issues/show.blade.php`

2. **Update JavaScript** - Form submission and AJAX calls need field name updates

3. **Test All Functionality:**
   - Create new Selling Voucher
   - Edit existing Selling Voucher
   - View Selling Voucher details
   - Delete Selling Voucher
   - Filter and search
   - Return modal
   - Bill report

## Breaking Changes
⚠️ **This update is NOT backward compatible with old data structure!**

Make sure the migration was run successfully before using these controller updates.
