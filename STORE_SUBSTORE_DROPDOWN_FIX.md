# Store and Sub-Store Dropdown Fix

## Problem
In the "Add Selling Voucher" and "Add Selling Voucher with Date Range" modules, the "Transfer From Store" dropdown was only displaying stores from the `mess_stores` table. Sub-stores from the `mess_sub_stores` table were not being included in the dropdown options.

## Root Cause
Both controllers (`KitchenIssueController` and `SellingVoucherDateRangeController`) were only fetching active stores:

```php
$stores = Store::active()->get();
```

This query only retrieved records from the `mess_stores` table and excluded all sub-stores.

## Solution Implemented

### 1. Controller Updates

#### Files Modified:
- `app/Http/Controllers/Mess/KitchenIssueController.php`
- `app/Http/Controllers/Mess/SellingVoucherDateRangeController.php`

#### Changes Made:

1. **Added SubStore model import:**
   ```php
   use App\Models\Mess\SubStore;
   ```

2. **Modified store retrieval logic** (in `index()`, `create()`, and `edit()` methods):
   ```php
   // Get active stores and sub-stores
   $stores = Store::active()->get()->map(function ($store) {
       return [
           'id' => $store->id,
           'store_name' => $store->store_name,
           'type' => 'store'
       ];
   });
   
   $subStores = SubStore::active()->get()->map(function ($subStore) {
       return [
           'id' => 'sub_' . $subStore->id,
           'store_name' => $subStore->sub_store_name . ' (Sub-Store)',
           'type' => 'sub_store',
           'original_id' => $subStore->id
       ];
   });
   
   // Combine stores and sub-stores
   $stores = $stores->concat($subStores)->sortBy('store_name')->values();
   ```

### 2. View Updates

#### Files Modified:
- `resources/views/mess/selling-voucher-date-range/index.blade.php`
- `resources/views/mess/kitchen-issues/index.blade.php`
- `resources/views/mess/kitchen-issues/create.blade.php`
- `resources/views/mess/kitchen-issues/edit.blade.php`
- `resources/views/mess/kitchen-issues/bill-report.blade.php`

#### Changes Made:

Updated all store dropdown loops to use array notation instead of object notation:

**Before:**
```blade
@foreach($stores as $store)
    <option value="{{ $store->id }}">{{ $store->store_name }}</option>
@endforeach
```

**After:**
```blade
@foreach($stores as $store)
    <option value="{{ $store['id'] }}">{{ $store['store_name'] }}</option>
@endforeach
```

## Result

The "Transfer From Store" dropdown now displays:
- ✅ All active stores from `mess_stores` table
- ✅ All active sub-stores from `mess_sub_stores` table (with "(Sub-Store)" label)
- ✅ Sorted alphabetically by name
- ✅ Sub-stores are distinguished with IDs prefixed by 'sub_' (e.g., 'sub_1', 'sub_2')

## Benefits

1. **Complete Store Visibility**: Users can now see and select from all available stores and sub-stores
2. **Clear Labeling**: Sub-stores are clearly marked with "(Sub-Store)" suffix
3. **Alphabetical Sorting**: All options are sorted for easy navigation
4. **Type Differentiation**: The solution maintains store type information for future processing if needed

## Testing Recommendations

1. Create some active sub-stores in the Sub-Stores module
2. Navigate to "Add Selling Voucher" and verify both stores and sub-stores appear in the "Transfer From Store" dropdown
3. Navigate to "Add Selling Voucher with Date Range" and verify the same
4. Verify that inactive stores/sub-stores do NOT appear in the dropdown
5. Test creating a selling voucher using both a regular store and a sub-store

## Date
Fixed: February 9, 2026
