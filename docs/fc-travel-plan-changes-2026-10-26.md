give# FC Travel Plan changes — 26 Oct 2026 (revert guide)

Branch: `hotfix/fc_chnages_23oct`

Everything changed on 26 Oct 2026 for the FC travel plan, and how to undo each part.
Hidden fields were **commented out, not deleted**, so the original code is still in the files.

| # | Change | Where it lives |
|---|---|---|
| 1 | Label "Arrival time at Dehradun Airport/Railway Station" → "Arrival time at Airport/Railway Station (24-hour)" | commit `b61f80258` |
| 2 | **Activity slot** hidden on the form and made optional; column removed from the Excel export | commit `b61f80258` |
| 3 | Excel header renamed to "Arrival time at Airport/Railway Station (24-hour)" | commit `b61f80258` |
| 4 | Travel Plan unlocks after **Descriptive Roll 2** when Bank Details is off (was: after the last active step) | commit `9a6fb5381` |
| 5 | Mode of journey shows its `message` from `mctp_travel_mode_masters` in a highlighted box | commit `9a6fb5381` |
| 6 | **Require Academy Vehicle** hidden on the form and made optional; column removed from the Excel export | **not committed yet** |
| 7 | Test data deleted/cleared for trainee `shailitm` | database only (`sargam_live`) |

---

## Quick full revert (code)

Undo all code changes at once:

```bash
# 1. Drop the uncommitted change (#6)
git checkout -- app/Exports/FcTravelJoiningReportExport.php \
  app/Http/Controllers/FC/TravelPlanController.php \
  resources/views/admin/travel/excel/joining-report-excel.blade.php \
  resources/views/fc/registration/travel.blade.php

# 2. Revert the two commits, newest first (#4, #5, then #1–#3)
git revert --no-edit 9a6fb5381
git revert --no-edit b61f80258

php artisan view:clear
```

If #6 has been committed by the time you read this, revert that commit too
(`git log --oneline -- resources/views/fc/registration/travel.blade.php` to find it),
newest first.

To revert only **one** change, use the per-change steps below.

---

## 1. Arrival-time label (form)

**File:** `resources/views/fc/registration/travel.blade.php`

Label changed from
`Arrival time at Dehradun Airport/Railway Station (24-hour)` to
`Arrival time at Airport/Railway Station (24-hour)`.

**Revert:** put "Dehradun " back into that `<label>`.

Not changed (still say "Dehradun"): the admin edit screen
(`resources/views/admin/travel/edit.blade.php`) and the vehicle-question label.

## 2. Activity slot hidden and optional

**Form view** — `resources/views/fc/registration/travel.blade.php`. Each block is wrapped in
`{{-- ... --}}` with the note *"Activity slot hidden for now (will be used in future)"*:

- the **Activity slot** dropdown (`fc_travel_arrival_slot_id`)
- the hint *"Choose an activity slot at least 2 hours after this time."* under arrival time
- **Slot name** / **Slot time** rows in the confirmation popup
- **Slot & time** row in the submitted-plan summary

The page script was also made to work without the dropdown (guards like
`if (!slotSelect) return;`). These are harmless once the dropdown is back; leave them.

**Controller** — `app/Http/Controllers/FC/TravelPlanController.php`, in both `save()` and `submit()`:

- validation `fc_travel_arrival_slot_id` changed `required` → `nullable`
- the slot checks (available, matches date, not full, 2-hour gap) were wrapped in
  `if ($request->filled('fc_travel_arrival_slot_id')) { ... }` — nothing was removed

**Export** — see section 3.

**Revert:**
1. Remove the `{{--` / `--}}` around the four blocks above.
2. In the controller change `nullable` back to `required` for `fc_travel_arrival_slot_id`
   (twice). The `if (...filled...)` wrapper can stay.
3. Restore the export column (section 3).

## 3. Excel export (joining report)

**Files:** `resources/views/admin/travel/excel/joining-report-excel.blade.php`,
`app/Exports/FcTravelJoiningReportExport.php`

| | Original (before today) | Now |
|---|---|---|
| Columns | 13 | 11 |
| Activity slot column | shown | commented out |
| Require Academy Vehicle column | shown | commented out (change #6) |
| Arrival-time header | "Arrival time at Dehradun Airport/Railway Station" | "Arrival time at Airport/Railway Station (24-hour)" |

**Full revert of the export** — in `FcTravelJoiningReportExport.php` set:

```php
public const COL_COUNT = 13;
// uncomment 'slot' => ... and 'require_vehicle' => ... in mappedRows()
$centerCols = ['A', 'F', 'H', 'K', 'M'];
$widths = [6, 16, 22, 12, 14, 13, 20, 16, 24, 24, 26, 12, 10];
```

and in `joining-report-excel.blade.php` remove the `{{-- --}}` around the two `<th>` and the
two `<td>` cells (`$row['slot']`, `$row['require_vehicle']`); restore the header text if wanted.

**Bring back only Activity slot** (keep vehicle hidden): `COL_COUNT = 12`,
`$centerCols = ['A', 'F', 'H', 'L']`, `$widths = [6, 16, 22, 12, 14, 13, 20, 16, 24, 24, 12, 10]`.

**Bring back only Require Academy Vehicle** (keep slot hidden): `COL_COUNT = 12`,
`$centerCols = ['A', 'F', 'G', 'J', 'L']`, `$widths = [6, 16, 22, 12, 14, 13, 16, 24, 24, 26, 12, 10]`.

> Column letters in `$centerCols` shift whenever a column is added or removed — check them.

## 4. When Travel Plan unlocks

**File:** `app/Services/FC/FcRegistrationFlowService.php`, method `travelAnchorStep()`
(plus a comment in `resources/views/forms/dashboard.blade.php`).

| | Rule |
|---|---|
| Before | Bank Details if active, otherwise the **last active step** |
| Now | Bank Details if active, otherwise **Descriptive Roll 2** (`tracker_column = step3_done`), otherwise the last active step |

Why: on form 30 with Special Assistance switched on, Travel Plan was locked behind Special Assistance.

**Revert:** delete this one line in `travelAnchorStep()`:

```php
?? $steps->first(fn ($step) => ($step->tracker_column ?? '') === 'step3_done')
```

## 5. Mode-of-journey message

**Files:**

- `app/Models/FC/MctpTravelModeMaster.php` — new `activeMessages()` (mode name → `message`),
  plus a shared private `rememberLookup()` cache helper now also used by `activeNames()`
- `app/Http/Controllers/FC/TravelPlanController.php` — passes `travelModeMessages` to the view
- `resources/views/fc/registration/travel.blade.php` — each option has `data-message`, a
  highlighted box `#modeOfJourneyMessage` ("IMPORTANT") under the dropdown, and a small script

Display only: nothing new is saved and validation is unchanged. Message edits in the table show
within `fc.lookup_cache_ttl` (default 600 s) unless the FC lookup cache is flushed.

**Revert:** remove the `#modeOfJourneyMessage` box and its `<script>`, the `data-message` attribute
and `id="modeOfJourneySelect"` from the select, and the `'travelModeMessages' => ...` line in the
controller. `activeMessages()` can stay unused, or remove it.

## 6. Require Academy Vehicle hidden and optional (not committed yet)

**Form view** — `resources/views/fc/registration/travel.blade.php`. Wrapped in `{{-- ... --}}` with the
note *"Require academy vehicle hidden for now (will be used in future)"*:

- the **Whether Require Academy Vehicle From Dehradun Airport/Railway Station to Academy** Yes/No radios
- **Require academy vehicle** row in the confirmation popup
- **Require academy vehicle** row in the submitted-plan summary

**Controller** — `save()`: `require_academy_vehicle` changed `required|boolean` → `nullable|boolean`
(`submit()` was already `nullable`). With no answer the value is stored as `0`
(the column is `NOT NULL DEFAULT 0`).

**Export** — column commented out (section 3).

**Revert:** remove the comment markers around the three blocks, set the `save()` rule back to
`required|boolean`, and restore the export column (section 3).

---

## 7. Database changes (`sargam_live`, trainee `shailitm`)

`shailitm` has no `user_credentials` row; FC tables key them by roster id
`fc_registration_master.pk = 3`, so `user_id = 3` below **is shailitm**
(credentials pk 3, `rohit.kumar`, has no FC registration).

These were test entries, deleted so the forms could be filled again. Restore only if needed.

### 7a. Travel plan deleted (first time) — row `id = 2`

```sql
INSERT INTO student_travel_plan_masters
  (id, user_id, joining_date, fc_travel_arrival_slot_id, mode_of_journey, journey_vehicle_no,
   arrival_time_dehradun, require_academy_vehicle, special_requirements, joining_time,
   academy_arrival_date, is_submitted, created_at, updated_at)
VALUES
  (2, 3, '2026-08-24', 9, 'Flight-IndiGo-Additional daily flights', 'AZ45566',
   '02:00', 1, 'wswswsws', NULL, NULL, 1, '2026-10-23 22:30:45', '2026-10-23 22:30:45');
UPDATE student_masters SET travel_done = 1 WHERE id = 1 AND user_id = '3';
```

### 7b. Health Risk Factors (step 145) cleared

Only the health columns of `student_master_seconds` were set to NULL — the row was **not**
deleted because it also holds Descriptive Roll data.

```sql
UPDATE student_master_seconds SET health_asthma='No', health_lung_disease='None',
  health_kidney_disease='No', health_diabetes='No', health_blood_disorder='None',
  health_immunocompromised='None', health_liver_disease='No', health_cardiac_condition='None',
  health_pregnant_lactating='No', health_additional_info='Not Applicable', health_completed=1
WHERE id = 1 AND user_id = 3;
UPDATE student_masters SET health_done = 1 WHERE id = 1 AND user_id = '3';
```

### 7c. Travel plan deleted (second time) — row `id = 3`

```sql
INSERT INTO student_travel_plan_masters
  (id, user_id, joining_date, fc_travel_arrival_slot_id, mode_of_journey, journey_vehicle_no,
   arrival_time_dehradun, require_academy_vehicle, special_requirements, joining_time,
   academy_arrival_date, is_submitted, created_at, updated_at)
VALUES
  (3, 3, '2026-08-24', NULL, 'Train - Sabarmati Junction Railway Station (SBI)', 'AZ45566',
   '01:00', 1, 'wdwdwdwd', NULL, NULL, 1, '2026-10-26 15:14:05', '2026-10-26 15:14:05');
UPDATE student_masters SET travel_done = 1 WHERE id = 1 AND user_id = '3';
```

7a and 7c are alternatives: a trainee has only one travel plan, so restore one of them, not both.

### Not changed by these edits

Steps were switched on and off on form 30 through the admin screen (`fc_form_steps.is_active`),
outside this change set. Current state is whatever the admin screen shows.
