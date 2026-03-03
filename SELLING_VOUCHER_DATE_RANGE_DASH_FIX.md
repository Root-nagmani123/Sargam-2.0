# Selling Voucher with Date Range - Return Column Dash Fix

## Issue Fixed

**Problem:** A dash "—" was showing in the Return Item column next to the Return button on the "Selling Voucher with Date Range" page

**Solution:** Removed the unnecessary dash while keeping the "Returned" badge functionality

## Changes Made

### View: `resources/views/mess/selling-voucher-date-range/index.blade.php`

#### Before (Lines 101-110):
```blade
<td>
    @if(($item->return_quantity ?? 0) > 0)
        <span class="badge bg-info">Returned</span>
    @else
        —                    <!-- ❌ This dash was showing -->
    @endif
    @if($loop->first)
        <button type="button" class="btn btn-sm btn-outline-secondary ms-1 btn-return-report" data-report-id="{{ $report->id }}" title="Return">Return</button>
    @endif
</td>
```

#### After:
```blade
<td>
    @if(($item->return_quantity ?? 0) > 0)
        <span class="badge bg-info">Returned</span>
    @endif                   <!-- ✅ No more dash in @else -->
    @if($loop->first)
        <button type="button" class="btn btn-sm btn-outline-secondary ms-1 btn-return-report" data-report-id="{{ $report->id }}" title="Return">Return</button>
    @endif
</td>
```

## What Changed

**Removed:** The `@else` block with the dash "—" on line 104-105

**Logic:**
- ✅ If item has return quantity → Show "Returned" badge
- ✅ If no return quantity → Show nothing (just the Return button)
- ✅ Return button always shows for first item

## Result

| Scenario | Before | After |
|----------|--------|-------|
| No return quantity | "—" + Return button | Return button only |
| Has return quantity | "Returned" badge + Return button | "Returned" badge + Return button |

## Visual Improvement

**Before:**
```
Return Item Column:  —   [Return]
```

**After:**
```
Return Item Column:  [Return]
```

## Files Modified

1. `resources/views/mess/selling-voucher-date-range/index.blade.php`
   - Line 101-110: Removed dash from Return Item column

## Testing

1. ✅ Go to "Selling Voucher with Date Range" page
2. ✅ Check Return Item column - no more dash
3. ✅ Only shows "Returned" badge if item was returned
4. ✅ Return button still works properly

## Summary - Both Pages Fixed

Both pages now have clean Return Item columns:

| Page | Status |
|------|--------|
| **Selling Voucher** (kitchen-issues/index.blade.php) | ✅ Fixed |
| **Selling Voucher with Date Range** (selling-voucher-date-range/index.blade.php) | ✅ Fixed |

All Return columns are now clean and professional! 🎉
