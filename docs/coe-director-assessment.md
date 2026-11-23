# COE: Director Assessment (Evaluation & Marks, Training Section)

The Training Section (TI) records the **Director's overall assessment** of each OT and sends it
to COE. It then counts as part B of Merit & Ranking and appears on the marksheet.

- Screen: **Setup › COE › Examination Management › Evaluation & Marks**
  (`GET admin/coe/evaluation-marks`, menu 276)
- Permission: `coe_evaluation_marks`, checked on the route (`menu.permission`). Grant it to the
  TI role (`Training-Induction`) in Roles & Permissions.
- Figma: Sargam 2.0, page "Super Admin View", section **"Examination Drive for Training
  Section"** (node `2270:64416`). Frames: two "Evaluation & Marks" table versions and
  "Reviewer · OT profile" (`3089:25616`).
- Source requirement: the Record of Discussion (ROD) on the Evaluation & Marks flow (22 Nov 2026).

## Code

| Kind | Where |
|---|---|
| Controller | `app/Http/Controllers/Admin/COE/DirectorAssessmentController.php` (`index`, `review`, `save`) |
| Service | `app/Services/COE/DirectorAssessmentService.php` (all rules) |
| Shared rules | `ResultMarksService::isDirectorComponent()`, `examOver()`, `column()`, `entryDrives()` |
| Views | `resources/views/admin/coe/director_assessment/` (`index`, `review`, `styles`, `avatar`) |
| Routes | `routes/web.php`, the COE block: `evaluation-marks.index`, `.review`, `.save` |
| Tests | `tests/Feature/CoeDirectorAssessmentFlowTest.php` |

`EvaluationMarksController` remains only as the faculty-side mock (`faculty-evaluation-marks`,
sample data).

## Setup (who does what)

1. **Masters, once:**
   - Examination Type Master: a type whose name contains **"Director"**, e.g. "Director
     Assessment".
   - Component Master: the faculty components (Discipline, Journaling, …) and one whose name
     contains **"Director"**, e.g. "Director's Overall".
2. **Admin**, Examination Drive: a drive with that exam type. Set its status to **Active**,
   then map Subject → Faculty.
3. **Faculty setup**, per subject:
   - Faculty components as usual.
   - **Director's Overall**: max marks and exam range, with *Evaluation Through* =
     **Sargam 2.0** so no faculty is needed.
   - Its max marks count towards the subject's Subject Max Marks.
4. **Course Coordinator** approves the drive.
5. The course has roll numbers / Unique Participant Codes. They give the OT list.

## Flow

```
01 Faculty marks received   faculty enter + Approve the drive's other components (Result Entry)
02 TI · Director assessment TI reviews each OT, enters Director's marks + remarks, Send to COE
03 COE                      Result Compilation → Marks Moderation → Merit & Ranking (B) → Marksheet
```

### When OTs show on Evaluation & Marks

A row appears for every OT × Director component when **all** of these hold:

- the drive is **Active** and **CC-approved** (`entryDrives()`)
- the drive's exam type contains "director", and so does the component's name
  (`isDirectorComponent()`)
- the component's exam is over (`examOver()`). That means its slot end
  (`proposed_end_at`, else `exam_end_at`) has passed, to the minute, or, with no slot, its end
  date (`exam_end_date`, `exam_date`, the drive's `end_date`) is today or earlier.

  An exam range ending "today 23:59" opens only after 23:59.

### Table view (`index`)

- Filters: Course, Examination (drive). Tabs: **Pending** (not sent) and **Completed** (sent).
- Columns:
  - checkbox
  - Officer trainee (photo, name, `sno · code`)
  - Course · subject with attendance
  - Source / faculty
  - one column per faculty component
  - **Director /max** (input)
  - Faculty remarks
  - Notices (notices + memos count)
  - Review status with **Review profile**
- Typing a Director mark and leaving the field saves a **draft**.
- **Approve** (header) sends the ticked rows to COE.
- Download (Excel / PDF), Print, Columns and Search come from `CoeGrid`.

### OT profile (`review?key=&student=&course=&drive=&tab=`)

- **Band:** photo, name, code, course, subject; an eligibility chip; and the 3-step handoff bar.
- **← Previous / Next →** follow the table's order within the tab. The current OT stays in
  that list even after it is sent, so Next still works. After **Send to COE** a dialog offers
  **Next OT →**, or **Back to table** after the last one.
- **Faculty assessment** (read-only): summary tiles, a component table (faculty, marks,
  status, remarks) and the scored total.
- **Notices & memos:**
  - notices from `student_notice_status` (`notice_memo = 1`)
  - memos from `student_memo_status` and active `discipline_memo_status`
- **Director's overall assessment:** marks (0..max) and remarks (required). Then **Save draft**
  or **Send to COE** (sticky bar). Leaving with unsaved typing asks first.

## Data mapping (no new table or column)

| What | Stored in |
|---|---|
| Director's marks | `coe_evaluation_marks.marks` on (drive, subject, Director component, student) |
| Director's remarks | `coe_evaluation_marks.remarks` (varchar 500; the Figma says 1000) |
| Draft / Sent to COE | `status` 1 Draft / 2 Approved; `approved_by`, `approved_at` = TI user |
| Who entered it | `created_by`, `modified_by` = TI user; `evaluated_by_faculty_pk` = null |
| Faculty marks shown | the same table, other components of that drive + subject, **approved only** |
| Attendance | derived: Absent if every received faculty mark is AB, Present if any is a mark |
| Photo | `student_master.photo_path` → `storage/form-uploads/photo/…`; initials if missing |
| OT list | `ResultMarksService::participants()` (roll numbers / UPC of the course + batch) |

## Rules

- **Marks:** 0..max with up to 2 decimals. No `AB`. A blank value clears the mark.
- **Remarks:** at most 500 characters. Required to send.
- **Send to COE** needs, for each OT:
  - the marks,
  - the remarks,
  - every faculty mark of that drive + subject approved ("Eligible for assessment").

  It is **all or nothing**: one failing OT sends none and saves nothing.
- **Locked once sent.** Any later change is refused. There is no "unsend".
- Only Director components can be written through this screen. A faculty column is refused.
- Write path: the `save()` transaction with `lockForUpdate`; MySQL 1062 becomes "someone else
  saved".

## Effect on the rest of COE

- **Faculty Result Entry:** the Director component is never offered, even to COE staff or
  Super Admin.
- **Entry Ready / Notify for Pending bells:** they skip the Director component.
- **Result Compilation:** it counts once sent. It is never "No faculty mapped", because
  `has_faculty` is true for it.
- **Marks Moderation:** the evaluator is named **"Training Section"**. With one evaluator, the
  moderated marks equal the raw marks.
- **Merit & Ranking:** part B (all components of a Director Assessment drive). An OT is
  ranked only once **every** held mark is approved **and moderated**.
- **No bell to COE** on Send to COE.

## Open points

- Remarks max 500 (the column) vs 1000 in the Figma. 1000 needs DBA DDL.
- The Director component is recognised by name ("director"). A component named without it
  is treated as a faculty component.
- ROD point 5, one faculty or a faculty group per subject, is already covered by the drive's
  faculty and sub-coordinators. Nothing new was built for it.
