# OT Code Display in Student Name Fix

## Problem
When selecting OT (Officer Training) as the Client Type and then selecting a course, the student names in the dropdown were displaying only the student's display name without their OT code.

## Requirement
Display student names with their OT codes in brackets in the format: `Student Name (OT_CODE)`

For example:
- Before: `AAYUSHI BANSAL`
- After: `AAYUSHI BANSAL (OT2025001)`

## Solution Implemented

### Files Modified:
1. `app/Http/Controllers/Mess/KitchenIssueController.php`
2. `app/Http/Controllers/Mess/SellingVoucherDateRangeController.php`

### Changes Made:

#### Updated `getStudentsByCourse()` method in both controllers:

**Before:**
```php
public function getStudentsByCourse(Request $request, $course_pk)
{
    $students = StudentMaster::join('student_master_course__map', 'student_master.pk', '=', 'student_master_course__map.student_master_pk')
        ->where('student_master_course__map.course_master_pk', $course_pk)
        ->select('student_master.pk', 'student_master.display_name')
        ->orderBy('student_master.display_name')
        ->get();

    return response()->json([
        'students' => $students->map(fn($s) => ['pk' => $s->pk, 'display_name' => $s->display_name ?? '—'])->filter(fn($s) => $s['display_name'] !== '—')->values(),
    ]);
}
```

**After:**
```php
public function getStudentsByCourse(Request $request, $course_pk)
{
    $students = StudentMaster::join('student_master_course__map', 'student_master.pk', '=', 'student_master_course__map.student_master_pk')
        ->where('student_master_course__map.course_master_pk', $course_pk)
        ->select('student_master.pk', 'student_master.display_name', 'student_master.generated_OT_code')
        ->orderBy('student_master.display_name')
        ->get();

    return response()->json([
        'students' => $students->map(function($s) {
            $displayName = $s->display_name ?? '—';
            // Append OT code in brackets if available
            if (!empty($s->generated_OT_code)) {
                $displayName .= ' (' . $s->generated_OT_code . ')';
            }
            return ['pk' => $s->pk, 'display_name' => $displayName];
        })->filter(fn($s) => $s['display_name'] !== '—')->values(),
    ]);
}
```

## Key Changes:

1. **Added `generated_OT_code` to SELECT clause:**
   - Now fetching the OT code from the `student_master` table

2. **Updated mapping logic:**
   - Check if `generated_OT_code` exists and is not empty
   - If OT code exists, append it to the display name in brackets
   - Format: `DisplayName (OT_CODE)`

3. **Backward Compatibility:**
   - If a student doesn't have an OT code, only the display name is shown
   - No breaking changes to existing functionality

## Result

✅ Student names now display with OT codes in brackets when selecting OT client type
✅ Format: `STUDENT NAME (OT_CODE)`
✅ OT code is fetched from `student_master.generated_OT_code` column
✅ Works in both "Add Selling Voucher" and "Add Selling Voucher with Date Range" modules
✅ Maintains backward compatibility if OT code is not available

## Testing Recommendations

1. Navigate to "Add Selling Voucher" or "Add Selling Voucher with Date Range"
2. Select Client Type: **OT**
3. Select a Course from the Client Name dropdown
4. Verify the Name dropdown shows students with format: `NAME (OT_CODE)`
5. Verify students without OT codes still display correctly (name only)

## Date
Fixed: February 9, 2026
