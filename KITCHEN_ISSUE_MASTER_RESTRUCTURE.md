# Kitchen Issue Master Table Restructure

## Date: February 5, 2026

## Overview
The `kitchen_issue_master` table has been restructured to simplify the schema and align with new requirements for Selling Voucher and Selling Voucher with Date Range modules.

## New Table Structure

| Column Name | Type | Description |
|------------|------|-------------|
| `pk` (id) | BIGINT UNSIGNED | Primary Key |
| `client_type` | TINYINT | 1=Employee, 2=OT, 3=Course, 4=Other |
| `payment_type` | TINYINT | 0=Cash, 1=Credit, 2=Online |
| `client_id` | BIGINT UNSIGNED | ID based on selected client type |
| `name_id` | BIGINT UNSIGNED | Name ID based on selected client type |
| `issue_date` | DATE | Issue date |
| `store_id` | BIGINT UNSIGNED | Store ID (FK to mess_stores) |
| `kitchen_issue_type` | TINYINT | 1=Selling Voucher, 2=Selling Voucher with Date Range |
| `remarks` | TEXT | Remarks |
| `status` | TINYINT | 0=Pending, 1=Processing, 2=Approved, 3=Rejected, 4=Completed |
| `client_type_pk` | BIGINT UNSIGNED | FK to mess_client_types (retained for compatibility) |
| `client_name` | VARCHAR | Client name (retained for compatibility) |
| `created_at` | TIMESTAMP | Created timestamp |
| `updated_at` | TIMESTAMP | Updated timestamp |

## Removed Columns

The following columns have been removed as they are no longer needed:

- `inve_item_master_pk` - Items are now in kitchen_issue_items table
- `requested_store_id` - Not needed in new structure
- `quantity` - Moved to kitchen_issue_items table
- `user_id` - Replaced by client_id
- `store_employee_master_pk` - Not needed
- `request_date` - Replaced by issue_date
- `unit_price` - Moved to kitchen_issue_items table
- `transfer_to` - Not needed
- `employee_student_pk` - Replaced by client_id
- `bill_no` - Not needed in new structure
- `send_for_approval` - Removed
- `notify_status` - Removed
- `approve_status` - Removed (use status instead)
- `paid_unpaid` - Removed
- `created_by` - Using Laravel's created_at
- `modified_by` - Using Laravel's updated_at

## Model Constants

### Client Types
```php
const CLIENT_EMPLOYEE = 1;
const CLIENT_OT = 2;
const CLIENT_COURSE = 3;
const CLIENT_OTHER = 4;
```

### Payment Types
```php
const PAYMENT_CASH = 0;
const PAYMENT_CREDIT = 1;
const PAYMENT_ONLINE = 2;
```

### Kitchen Issue Types
```php
const TYPE_SELLING_VOUCHER = 1;
const TYPE_SELLING_VOUCHER_DATE_RANGE = 2;
```

### Status
```php
const STATUS_PENDING = 0;
const STATUS_PROCESSING = 1;
const STATUS_APPROVED = 2;
const STATUS_REJECTED = 3;
const STATUS_COMPLETED = 4;
```

## Migration File
- `database/migrations/mess/2026_02_05_183448_restructure_kitchen_issue_master_table.php`

## Model Updates
- Updated `app/Models/KitchenIssueMaster.php`
  - Modified fillable fields
  - Updated constants
  - Updated relationships
  - Updated accessor methods
  - Updated scopes

## Next Steps

1. **Run the migration:**
   ```bash
   php artisan migrate --path=database/migrations/mess/2026_02_05_183448_restructure_kitchen_issue_master_table.php
   ```

2. **Update Controllers:**
   - Update `app/Http/Controllers/Mess/KitchenIssueController.php` to use new column names
   - Update validation rules
   - Update data insertion/update logic

3. **Update Views:**
   - Update `resources/views/mess/kitchen-issues/` views to use new column names
   - Update JavaScript to match new field names

4. **Test:**
   - Test creating new Selling Vouchers
   - Test creating Selling Vouchers with Date Range
   - Test editing and viewing existing records
   - Test filters and search functionality

## Important Notes

⚠️ **BACKUP YOUR DATABASE BEFORE RUNNING THIS MIGRATION!**

This migration will:
- Drop several columns and their data
- Rename columns
- Add new columns

Ensure you have backed up any important data from the old columns before proceeding.

## Backward Compatibility

Some methods and relationships have been kept for backward compatibility:
- `storeMaster()` - Alias for `store()`
- `client_type_pk` and `client_name` columns retained temporarily

These can be removed once all controllers and views are updated to use the new structure.
