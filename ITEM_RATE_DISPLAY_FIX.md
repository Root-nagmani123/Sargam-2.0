# Item Rate Auto-Population Fix

## Problem
In the 'Add Selling Voucher' and 'Add Selling Voucher with Date Range' modules, when selecting an item in the Item Details section, the Rate field was not being automatically populated with the item's standard cost.

## Root Cause
The `standard_cost` field from the `mess_item_subcategories` table was not being:
1. Fetched in the controller when retrieving item subcategories
2. Passed to the frontend as a data attribute
3. Populated into the rate input field when an item was selected

## Solution Implemented

### 1. Controller Updates

#### Files Modified:
- `app/Http/Controllers/Mess/KitchenIssueController.php` (2 locations: `index()` and `create()` methods)
- `app/Http/Controllers/Mess/SellingVoucherDateRangeController.php` (`index()` method)

#### Changes Made:

**Before:**
```php
$itemSubcategories = ItemSubcategory::active()->orderBy('name')->get()->map(function ($s) {
    return [
        'id' => $s->id,
        'item_name' => $s->item_name ?? $s->name ?? '—',
        'unit_measurement' => $s->unit_measurement ?? '—',
    ];
});
```

**After:**
```php
$itemSubcategories = ItemSubcategory::active()->orderBy('name')->get()->map(function ($s) {
    return [
        'id' => $s->id,
        'item_name' => $s->item_name ?? $s->name ?? '—',
        'unit_measurement' => $s->unit_measurement ?? '—',
        'standard_cost' => $s->standard_cost ?? 0,
    ];
});
```

### 2. View Updates

#### Files Modified:
- `resources/views/mess/kitchen-issues/index.blade.php`
- `resources/views/mess/selling-voucher-date-range/index.blade.php`

#### Changes Made:

1. **Added `data-rate` attribute to item option tags:**

**Before:**
```blade
<option value="{{ $s['id'] }}" data-unit="{{ e($s['unit_measurement'] ?? '') }}">
    {{ e($s['item_name'] ?? '—') }}
</option>
```

**After:**
```blade
<option value="{{ $s['id'] }}" data-unit="{{ e($s['unit_measurement'] ?? '') }}" data-rate="{{ e($s['standard_cost'] ?? 0) }}">
    {{ e($s['item_name'] ?? '—') }}
</option>
```

2. **Updated JavaScript functions to include rate in dynamically generated rows:**

**Before:**
```javascript
'<option value="' + s.id + '" data-unit="' + (s.unit_measurement || '') + '">' + (s.item_name || '—') + '</option>'
```

**After:**
```javascript
'<option value="' + s.id + '" data-unit="' + (s.unit_measurement || '') + '" data-rate="' + (s.standard_cost || 0) + '">' + (s.item_name || '—') + '</option>'
```

3. **Updated item selection handler to auto-populate rate:**

**Selling Voucher (Kitchen Issues):**
```javascript
function updateUnit(row) {
    const sel = row.querySelector('.sv-item-select');
    const opt = sel && sel.options[sel.selectedIndex];
    const unitInp = row.querySelector('.sv-unit');
    const rateInp = row.querySelector('.sv-rate');
    if (unitInp) unitInp.value = opt && opt.dataset.unit ? opt.dataset.unit : '';
    if (rateInp && opt && opt.dataset.rate) rateInp.value = opt.dataset.rate;
}
```

**Selling Voucher with Date Range:**
```javascript
function updateAddRowUnit(row) {
    const sel = row.querySelector('.dr-item-select');
    const opt = sel && sel.options[sel.selectedIndex];
    const unitInp = row.querySelector('.dr-unit');
    const rateInp = row.querySelector('.dr-rate');
    if (unitInp) unitInp.value = (opt && opt.dataset.unit) ? opt.dataset.unit : '—';
    if (rateInp && opt && opt.dataset.rate) rateInp.value = opt.dataset.rate;
}
```

## Result

✅ When selecting an item in Item Details, the Rate field is now automatically populated with the item's `standard_cost`
✅ Works in both "Add Selling Voucher" and "Add Selling Voucher with Date Range" modules
✅ Rate is fetched from the `mess_item_subcategories.standard_cost` column
✅ If an item doesn't have a standard cost, the rate defaults to 0
✅ Users can still manually override the auto-populated rate if needed

## Testing Recommendations

1. Navigate to "Add Selling Voucher" module
2. Click "ADD Selling Voucher" button
3. In Item Details section, click "+ Add Item" if needed
4. Select an item from the "Item Name" dropdown
5. Verify that:
   - Unit field is auto-populated
   - **Rate field is auto-populated with the item's standard cost**
6. Repeat the same test for "Add Selling Voucher with Date Range" module

## Notes

- The rate can still be manually edited by the user after auto-population
- The rate will be recalculated whenever the item selection changes
- If an item has no standard_cost set, the rate will default to 0

## Date
Fixed: February 9, 2026
