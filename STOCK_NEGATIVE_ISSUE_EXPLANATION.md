# Stock Summary Report - Negative Stock Issue Explanation

## समस्या की व्याख्या (Problem Explanation)

### ❌ क्या गलत हो रहा था?

Stock Summary Report में कुछ items की **Closing Quantity negative (-) में आ रही थी**।

**उदाहरण:**
- **Chocolate**: Opening = 0, Purchase = 0, Sale = 40, **Closing = -40** ❌
- **Tea**: Opening = 0, Purchase = 10, Sale = 11, **Closing = -1** ❌

### 🤔 यह क्यों हो रहा था?

**मूल कारण:** Kitchen Issue (Sale) entries बिना stock के की गई थीं।

**Logic:**
```
Closing Stock = Opening Stock + Purchase - Sale
```

**Chocolate का Example:**
```
Closing = 0 + 0 - 40 = -40 (NEGATIVE!)
```

यह **गलत** है क्योंकि:
- अगर stock नहीं है (Opening = 0, Purchase = 0)
- तो Sale कैसे हो सकता है? (Sale = 40)
- परिणाम: Negative Closing Stock (-40)

### 🎯 असली समस्या

यह **Data Entry Error** है:

1. **Kitchen Issue में गलत entry** - जब stock ही नहीं था, तब भी sale की entry की गई
2. **Purchase Order missing** - हो सकता है purchase हुआ हो लेकिन system में entry नहीं की गई
3. **Wrong Date Entry** - Purchase की date गलत हो सकती है

## ✅ समाधान (Solution Implemented)

### 1. **Error Detection System**

अब system automatically detect करता है:

```php
// Negative closing stock check
if ($itemData['closing_qty'] < 0) {
    $itemData['has_negative_stock'] = true;
    $itemData['error_message'] = 'Negative closing stock - Sale without sufficient inventory';
}

// Sale without stock check
if ($itemData['sale_qty'] > 0 && ($itemData['opening_qty'] + $itemData['purchase_qty']) <= 0) {
    $itemData['has_negative_stock'] = true;
    $itemData['error_message'] = 'Sale recorded without any stock available';
}
```

### 2. **Visual Indicators**

**Report में अब दिखता है:**

1. **Red Background** - पूरी row red background में
2. **Warning Alert** - Report के top पर alert message
3. **Error Message** - Item name के नीचे detailed error message
4. **Icons** - ⚠️ Warning icons negative values के साथ
5. **Bold Red Text** - Negative values bold और red में

**Example Display:**
```
┌─────────────────────────────────────────────────────────────┐
│ ⚠️ Data Integrity Issues Detected!                          │
│                                                              │
│ कुछ items में negative stock है:                           │
│ • Sale entries बिना stock के की गई हैं                     │
│ • Data entry में error है                                   │
└─────────────────────────────────────────────────────────────┘

SR. | Item Name                                    | Opening | ...
────┼──────────────────────────────────────────────┼─────────┼────
 1  | chocolate                                    |   0.00  | ...
    | ⚠️ Sale recorded without any stock available |         |
    | (RED BACKGROUND)                             |         |
```

### 3. **Detailed Error Messages**

**दो प्रकार के error messages:**

1. **"Negative closing stock - Sale without sufficient inventory"**
   - Closing stock negative है
   - यह दिखाता है कि ज़्यादा sale हुई है stock से

2. **"Sale recorded without any stock available"**
   - Opening = 0, Purchase = 0, लेकिन Sale > 0
   - यह clearly गलत data entry है

## 🔍 Data को कैसे Fix करें?

### Step 1: Report देखें
1. Stock Summary Report खोलें
2. Red highlighted rows देखें
3. Error message पढ़ें

### Step 2: Verify करें

**Chocolate का Example (Closing = -40):**

```sql
-- Check Kitchen Issue entries
SELECT * FROM kitchen_issue_items kii
JOIN kitchen_issue_master kim ON kii.kitchen_issue_master_pk = kim.pk
WHERE kii.item_subcategory_id = [chocolate_id]
AND kim.issue_date BETWEEN '2026-02-01' AND '2026-02-06';

-- Check Purchase Orders
SELECT * FROM mess_purchase_order_items poi
JOIN mess_purchase_orders po ON poi.purchase_order_id = po.id
WHERE poi.item_subcategory_id = [chocolate_id]
AND po.po_date <= '2026-02-06'
AND po.status = 'approved';
```

### Step 3: सही करें

**Option A: Delete Incorrect Sale Entry**
- अगर sale actually नहीं हुई थी
- Kitchen Issue entry को delete करें

**Option B: Add Missing Purchase**
- अगर purchase हुई थी लेकिन entry नहीं की गई
- सही Purchase Order बनाएं

**Option C: Fix Dates**
- अगर dates गलत हैं
- Purchase Order या Kitchen Issue की date सही करें

## 📊 Business Logic Explanation

### सही Flow होना चाहिए:

```
Day 1:
├── Opening Stock: 0
├── Purchase: 50 units (PO Entry required)
├── Sale: 40 units (Kitchen Issue)
└── Closing: 10 units ✅

Day 2:
├── Opening Stock: 10 (Previous Closing)
├── Purchase: 30 units
├── Sale: 25 units
└── Closing: 15 units ✅
```

### गलत Flow (जो हो रहा था):

```
Day 1:
├── Opening Stock: 0
├── Purchase: 0 (❌ No PO Entry)
├── Sale: 40 units (❌ बिना stock के sale!)
└── Closing: -40 units ❌ NEGATIVE!
```

## 🎯 Prevention (भविष्य में रोकने के लिए)

### 1. Kitchen Issue Entry करते समय:
- पहले check करें कि stock available है या नहीं
- Current stock देखें
- अगर stock नहीं है, तो sale entry न करें

### 2. Purchase Order Entry करें:
- जब भी सामान आए, तुरंत PO entry करें
- सही date और quantity डालें
- PO को approve करें

### 3. Regular Audit:
- हर week Stock Summary Report देखें
- Red highlighted items को immediately fix करें
- Stock और actual inventory को match करें

## 💡 Technical Details

### Controller Changes

**File:** `app/Http/Controllers/Mess/ReportController.php`

**Added Code:**
```php
// Flag for negative stock (indicates data error)
$itemData['has_negative_stock'] = false;
$itemData['error_message'] = null;

// Check for data integrity issues
if ($itemData['closing_qty'] < 0) {
    $itemData['has_negative_stock'] = true;
    $itemData['error_message'] = 'Negative closing stock - Sale without sufficient inventory';
}

// Check if sale happened without opening or purchase
if ($itemData['sale_qty'] > 0 && ($itemData['opening_qty'] + $itemData['purchase_qty']) <= 0) {
    $itemData['has_negative_stock'] = true;
    $itemData['error_message'] = 'Sale recorded without any stock available';
}
```

### View Changes

**File:** `resources/views/admin/mess/reports/stock-summary.blade.php`

**Added Features:**
1. Warning alert at top of report
2. Red background for error rows
3. Error messages in table
4. Warning icons
5. Bold red text for negative values

## 📝 Summary

### मुख्य बिंदु:

1. ✅ **Negative stock = Data Entry Error**
2. ✅ **System अब errors को highlight करता है**
3. ✅ **Red color और warning messages दिखते हैं**
4. ✅ **Data को fix करने के steps clear हैं**
5. ✅ **भविष्य में ऐसी errors को रोक सकते हैं**

### याद रखें:

> **"बिना stock के sale नहीं हो सकती!"**
> 
> अगर negative stock दिख रहा है = Data entry में गलती है।
> 
> Solution: Kitchen Issue entries और Purchase Orders को verify और correct करें।

---

**Created:** February 11, 2026  
**Purpose:** Stock Summary Report - Negative Stock Issue Explanation  
**Status:** ✅ Issue Identified and Solution Implemented
