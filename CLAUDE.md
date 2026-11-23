# Sargam 2.0: COE (Controller of Examinations) module

LBSNAA training-management app. This file documents the **COE module**: how
examinations run, from setting up a drive to publishing marksheets. Use class and
method names to find code; line numbers drift.

## New functionality (latest first)

Each COE change gets a line here; the details live in its section below.

- **2026-11-23: Re-exam opens only after the marksheets are published.** The Re-exam button
  and Create Re-exam need every participant's marksheet on the course signed by UDC, AAO
  and COE (published), on top of final marks. Details: Re-exam → "Who is listed". Tests:
  `CoeReexamFlowTest::test_no_reexam_until_the_marksheets_are_published`.
- **2026-11-23: Re-exam, steps 2–3 (faculty, CC, question paper).** On a re-exam the
  faculty setup page ("Map Faculty to Examination Drive Re-exam") lets the faculty set only
  the Tentative Exam Range (date + time); everything else is copied and locked, also on the
  server. All subjects dated = sent to the CC, whose slots must sit inside the re-exam dates
  (not the course dates). Faculty / CC lists show "Re-exam N" under the name. Question
  paper and Rajbhasha work unchanged (a new paper per re-exam drive). **Re-exams have no
  moderation** (marks count once approved); the re-exam marksheet will show only
  Pass / Fail, no marks. Tests: `CoeReexamFlowTest`.
- **2026-11-23: Re-exam, step 1 (COE side).** On Examination Drive Configuration a main
  drive whose results are final shows **Re-exam** when an OT failed or was absent in a
  subject; the Create Re-exam popup lists them, COE ticks OTs, sets dates and creates a
  re-exam drive (a copy of the main drive, exam dates empty). New tables `coe_reexams`,
  `coe_reexam_candidates`; old tables unchanged. Re-exam drives are kept out of Result
  Entry, Compilation, Merit, Moderation, Marksheet and Venue Allocation for now. Details:
  Re-exam. Tests: `CoeReexamFlowTest`.

- **2026-11-20: OTs see their exam seat.** The freeze bell (`coe_seat_allocation`, already sent)
  now opens **My Exam Seat** (`GET admin/coe/my-exam-seat`, `VenueAllocationController::mySeats`,
  OT from the session like Laptop Declaration; frozen plans only): a card per exam with date,
  Today/Upcoming/Completed, paper, drive, room, building/floor, seat + block, laptop/scriber,
  and a hall map marking only their desk. Sidebar entry: add at /sidebar/menus. Tests:
  `CoeVenueAllocationFlowTest::test_an_ot_sees_their_seat_once_the_plan_is_frozen`.
- **2026-11-20: Invigilators are notified on freeze and see their duties.** Freezing a seating
  plan sends each invigilator (faculty via `employee_master_pk`, MTS via their own
  `employee_master.pk`) a `coe_invigilation` bell: role, paper, date, room. Their page **My
  Invigilation Duty** (`GET admin/coe/my-invigilation-duty`, `VenueAllocationController::myDuties`,
  auth only, own duties on frozen plans only) lists date, Today/Upcoming/Completed, role, drive,
  course, paper, room, OTs, with View Seating / Print. Sidebar entry: add at /sidebar/menus.
  Tests: `CoeVenueAllocationFlowTest::test_freeze_notifies_invigilators_and_lists_their_duty`.
- **2026-11-20: Freezing a seating plan locks its invigilators.** Frozen rows lose Add / Edit
  Invigilators; the dialog refuses to open from one (422) and shows a drive's frozen rooms
  read-only (`frozen`, not sent on save); `storeInvigilators` refuses a frozen room. Assign
  invigilators before freezing. Tests: `CoeVenueAllocationFlowTest::test_a_frozen_plan_locks_its_invigilators`.
- **2026-11-20: An invigilator takes one room per exam date.** Was "one room per paper"; now a
  person (any role; faculty and MTS separately) in any room on a date is hidden from every other
  room that day, across papers and drives (`busy` in the `invigilators` JSON), and the save
  refuses it (`storeInvigilators`, `dayRoomsOutside()`, day = `COALESCE(dcm.exam_date,
  sp.exam_date)`). Tests: `CoeVenueAllocationFlowTest::test_one_person_invigilates_one_room_a_day_across_papers`.
- **2026-11-20: Add / Edit Invigilators pickers show full names.** The open list is 340–440px
  wide (`.va-inv-dd`, one name per line) and slides left to stay inside the dialog; the chosen
  name wraps to two lines (full name in the tooltip). `invSelect2()` in `venue_allocation/index`.
- **2026-11-20: Venue Allocation Delete works.** The list's Delete buttons (`.va-act--del`) never
  opened the confirm: `CoeGrid.confirmDelete` was called without `trigger`, so it listened for
  `.coe-act--del`. Server rule unchanged: any room of a not-frozen plan can be removed.
- **2026-11-20: View Seating Plan lists every OT.** Details panel (drive, course, paper, date,
  room, status, counts) and a searchable Seat Details table; a row and its desk highlight
  together. `allocationDetail()` now carries `drive` and decodes HTML-encoded names. Tests:
  `CoeVenueAllocationFlowTest::test_view_lists_every_seated_ot_with_the_plan_details`.
- **2026-11-20: Seating plan Download: Excel, PDF, CSV.** Edit / View Seating Plan's Download
  menu calls `GET venue-allocation/{id}/export/{excel|pdf|csv}` (`seatsExport`, the shared
  `ExportsMasterGrid` export: branded, exam + room in the filter band, audit-logged). Tests:
  `CoeVenueAllocationFlowTest::test_seat_list_exports_as_excel_pdf_and_csv`.
- **2026-11-20: Seating plan Print is a branded sheet.** Print on Edit / View Seating Plan
  opens `GET venue-allocation/{id}/print` (`printSheet`, view `venue_allocation/print`): a
  standalone A4 landscape page with the masters' LBSNAA print header, exam details, the hall
  with each desk's roll number, seat list, invigilators (signature column) and signature
  lines; it prints itself. Tests: `CoeVenueAllocationFlowTest::test_print_renders_the_branded_seating_plan`.
- **2026-11-20: Edit Seating Plan asks for Verify & Save before leaving.** After a swap or
  Change Room (each saved at once), leaving by a link or the back arrow shows "Please Verify &
  Save" and stays; reload/close gets the browser prompt. Flag in `sessionStorage`
  (`coeSeatPlanEdit:unverified:{id}`, this tab only), cleared when Verify & Save passes.
  Client-side only: nothing stops another tab or a direct URL.
- **2026-11-20: Edit Seating Plan can change an OT's room.** Tick OTs → Change Room →
  another room of the paper, or a free exam room (joins the plan as a Manual room). Details:
  Venue and seat allocation → "Change Room". Tests: `CoeVenueAllocationFlowTest::test_change_room_*`.
- **2026-11-20: Venue Allocation list has an Examination Drive column** (with a filter),
  from the plan's component map (`—` for plans made before drives). Tests:
  `CoeVenueAllocationFlowTest::test_the_list_shows_the_examination_drive`.
- **2026-11-20: Venue Allocation shows the exam date, not the range.** Map Seating Plan and
  Edit Invigilators show the component's `exam_date` only (as Faculty Coordinates &
  Components does), not `exam_date – exam_end_date`. Tests:
  `CoeVenueAllocationFlowTest::test_drive_shows_the_component_exam_date_not_the_range`.
- **2026-11-20: Venue Allocation offers only CC-approved drives.** Map Seating Plan's drive
  list (and the course-first component list) skips drives pending / rejected at CC; the
  server refuses them too (`VenueAllocationController::mappableDrives`, `resolveValidated`).
  Tests: `CoeVenueAllocationFlowTest::test_a_drive_pending_at_cc_is_not_offered_or_accepted`.
- **2026-11-23: Signature Master names mapped to UDC / AAO / COE.** For marksheet
  verify / publish, `UPPER DIVISION CLERK` signs as UDC, `ASSISTANT ADMINISTRATIVE OFFICER`
  as AAO and `COE-Incharge` (the name used on the server) as COE, next to the short names
  (`MarksheetService::SIGNATORY_ALIASES`, `signatoryOf()`, `designationNamesOf()`).
  Details: Marksheets → "Designation mismatch". Tests: `CoeMarksheetSigningFlowTest`.
- **2026-11-19: Marksheet shows Remarks, not the number.** On the printed marksheet
  (`marksheet/show.blade.php`) the "Marksheet No." line is replaced by a blank
  "Remarks: ____" line, filled by hand. View only: nothing stored; the number is still
  generated and shown in the Generation / Verified lists.
- **2026-11-19: Unfreeze needs remarks.** COE must enter a reason (required, max 500) in
  the Unfreeze popup; the server refuses it without one. Saved on the paper
  (`question_papers.unfreeze_remarks`, latest only), in the Question Paper Logs detail
  (`V2 · reason`, cut to 100 chars) and `laravel.log`, and added to the "Question Paper
  Reopened" bell for faculty and Rajbhasha. Details: Question papers → "Flow". Tests:
  `AdminQuestionPaperTest::test_unfreeze_sends_a_published_paper_back_to_the_faculty`.

- **2026-11-18: Faculty edits the translated paper, then freezes.** As soon as Rajbhasha
  sends the translation back (Pending Verification), the faculty sees **Edit Translated
  Paper**, not only after a COE unfreeze. The edited translation stays with the faculty
  (no trip back to Rajbhasha) and they freeze it with the OTP. Edit Original Paper still
  sends it back to Rajbhasha. Details: Question papers → "Flow". Tests:
  `FacultyQuestionPaperUploadTest::test_faculty_edits_the_sent_back_translation_then_freezes_it`.
- **2026-11-18: Rajbhasha View Original Paper: OTP verification first.** Before the
  original question paper opens, Rajbhasha must verify a 5-digit OTP sent to their
  mobile; a verified OTP opens that paper once (10 min), so every view needs a new OTP.
  Applies to View Original Paper and the original's See Attachment on My Question Paper.
  Translated paper, faculty and admin views are unchanged. Details: Question papers →
  "Rajbhasha View Original Paper needs an OTP". Tests: `RajbhashaQuestionPaperTest`.

## Stack and commands

- Laravel 9, PHP 8.2 (XAMPP), MySQL. Spatie permission, Yajra DataTables,
  Maatwebsite Excel, DomPDF / mPDF.
- `QUEUE_CONNECTION=sync`, files on the private `local` disk (`storage/app`).
- Tests: `php artisan test --filter=Coe` (Feature `tests/Feature/Coe*FlowTest.php`,
  Unit `CoeMarksModerationTest`, `CoeSeatAllocatorTest`, plus `AdminQuestionPaperTest`,
  `FacultyQuestionPaperUploadTest`, `QuestionPaperLogTest`, `RajbhashaQuestionPaperTest`;
  `CoeReexamFlowTest` is matched by the filter too).
- **Tests run against the real database `.env` points at** (see the comment in
  `phpunit.xml`). Never use `RefreshDatabase` / `DatabaseMigrations`: they would drop
  the dev schema. Use `DatabaseTransactions` and skip when fixture data is missing.

## Where the code lives

| Kind | Path |
|---|---|
| Controllers | `app/Http/Controllers/Admin/COE/` (+ `Concerns/`) |
| Services (business rules) | `app/Services/COE/` |
| Models | `app/Models/COE/` **and** `app/Models/` (`ExaminationDrive`, `ExaminationDriveFacultyMap`, `ExaminationDriveComponentMap`, `QuestionPaper`, `QuestionPaperLog`, masters) |
| Imports / Exports | `app/Imports/COE/`, `app/Exports/COE/` |
| DataTables | `app/DataTables/Coe*`, `ExaminationDrive*DataTable`, `Concerns/CoeMasterCells` |
| Sanitizer | `app/Support/COE/PaperHtmlSanitizer.php` |
| Views | `resources/views/admin/coe/<screen>/`, shared bits in `admin/coe/partials/` |
| JS / CSS | `public/js/coe-grid.js`, `coe-paper-viewer.js`, `coe-seat-map.js`, `public/css/coe-admin.css` |
| Routes | `routes/web.php`, one block `prefix('admin/coe')->name('admin.coe.')` (starts at the `// COE -> Question Paper Management` comment) |
| Migrations | `database/migrations/*coe*`, `*question_paper*`, `*examination_drive*` |

## End-to-end flow

```
Masters (type, term, component, building/floor/room, subject max marks,
         service passing marks, grade, scriber, signature)
  → Examination Drive (admin maps Subject → Faculty)
  → Faculty setup (components, marks, dates, sub-coordinators, OT codes)
  → Course Coordinator approves (cc_status = approved)
  → Question paper: faculty writes → Rajbhasha translates → faculty freezes with OTP → published
  → Roll numbers + Unique Participant Codes (per course + batch), then freeze
  → Laptop declaration (OT) / OT Laptop Status (admin), scriber mapping for PwBD OTs
  → Venue allocation / seat plan → freeze (published)
  → Result Entry (faculty, after the exam date) → Approve
  → Marks Moderation (Training section) → Result Compilation → Merit & Ranking
  → Marksheet: UDC signs → AAO signs → COE signs = published → Verified Marksheet
```

## Access control

- Everything sits behind `auth`. Screens are gated with `menu.permission:<key>`
  (`App\Http\Middleware\EnsureMenuPermission`, alias in `app/Http/Kernel.php`):
  Super Admin always passes; otherwise the user needs **any** listed permission
  (`hasMenuPermission()` in `app/helpers.php`). The permission name equals
  `menus.permission_name`; the sidebar is DB-driven (`MenuService::buildMenus()`),
  under the Setup › **COE** menu group.
- Menus and permissions are added by idempotent migrations (`Permission::firstOrCreate`,
  then clear caches). Follow that pattern for new screens.
- **Enforced on the route:** question paper screens (`coe_question_paper`,
  `coe_my_question_paper_faculty`, `coe_my_question_paper_rajbhasha`,
  `coe_question_paper_logs`), `coe_roll_code_mapping`, `coe_result_compilation`,
  `coe_faculty_result_entry`, `coe_merit_ranking`, `coe_marks_moderation`,
  `coe_signature_master`, `coe_marksheet_generation`, `coe_verified_marksheet`.
- **Not enforced (`auth` only):** all Master Data routes, Examination Drive (admin,
  faculty, CC, COE), Roll Numbers, UPC, Laptop & Scriber Seats, OT Laptop Status,
  Venue Allocation, My Invigilation Duty / My Exam Seat (each shows only the user's own data), Scriber Master,
  the Evaluation & Marks mock screens. The menu keys
  exist but no route checks them. Some controllers check access themselves (`coeIndex`
  needs role COE/Super Admin, CC approve/reject checks coordinator, Laptop Declaration
  checks the user is an OT).
- Roles granted by migrations: **Faculty** gets `coe_my_question_paper_faculty`,
  `coe_faculty_examination_drive`, `coe_cc_examination_drive`,
  `coe_faculty_result_entry`. **COE** gets `coe_coe_examination_drive`,
  `coe_roll_code_mapping`. All other keys are assigned through Roles & Permissions.
- **Course Coordinator is not a Spatie role:** `course_coordinator_master.Coordinator_name`
  holds the CC's `faculty_master.pk` per course (`CourseCoordinatorService`; main CC only).
- **Rajbhasha** = whoever holds `coe_my_question_paper_rajbhasha`.
- **Login ↔ faculty:** `faculty_master.employee_master_pk = user_credentials.user_id`.
  Faculty without this link can't be notified or recognised as mapped.
- Spatie roles used by name: `Faculty`, `COE`, `UDC`, `AAO` (marksheet signing,
  notifications), `Rajbhasha` (holds `coe_my_question_paper_rajbhasha`). Only `COE` is
  created by a migration; the others were created through the Roles UI (they exist on
  the dev DB). Grants made through the UI (e.g. COE → `coe_question_paper`,
  `coe_marksheet_generation`; UDC/AAO → `coe_marksheet_generation`) live only in the DB.
- `coe_marks_moderation` has **no migration and no menu**: until someone creates the
  permission, only Super Admin can open Marks Moderation.

## Examination Drive (`ExaminationDriveController`)

- A drive = one course + service + exam type + term + `academic_session` (MySQL YEAR).
  Tables: `examination_drives`, `examination_drive_faculty_maps` (one faculty per
  subject per drive), `examination_drive_component_maps` (one row per
  drive+subject+component: `max_marks`, `passing_marks`, `weightage`, `source`,
  `exam_date`/`exam_end_date`, `exam_mode`, `exam_type`, JSON `sub_faculty_names`,
  `faculty_codes` {faculty: [roll nos]}, `faculty_unique_codes` {faculty: [UPCs]}).
- `status`: 0 Draft, 1 Active, 2 Closed. `cc_status`: null, `pending`, `approved`,
  `rejected`. `ExaminationDrive::progress()` gives the label shown everywhere.
- **Admin** (`index`, `store`, `update`, `show` = Map Faculty, `coordinates`): drive dates
  must sit inside the course dates (`course_master.start_year` holds the start date);
  faculty must be active **internal** (`faculty_type = 1`); `update` replaces all faculty
  maps (so map ids change) and is refused when Closed / CC-pending / CC-approved.
  `phase` is written from `academic_session`.
- **Faculty** (`facultyIndex`, `facultySetup`, `saveComponents`, `assignFaculty`): never
  sees Draft drives; a mapped faculty sees only their subjects. Save rules:
  - `passing_marks` is forced to the drive service's active **Service Passing Marks**.
  - Sum of a subject's component `max_marks` ≤ its active **Subject Max Marks**;
    total weightage ≤ 100. Missing master value = save fails.
  - Exam range must fall inside the drive dates.
  - **OT codes:** within one subject, a roll number / UPC belongs to only one faculty
    (`sharedCodesError`); the same faculty may hold it on several components.
  - Course codes come from the batch equal to `academic_session`, else the latest batch.
  - When every mapped subject has components, the drive goes to the CC
    (`sendToCoordinator`, `cc_status=pending`). An admin save never sends it.
- **CC** (`ccIndex`, `ccApprove`, `ccReject`): approve sets proposed start/end dates
  (inside course dates) as the drive dates; reject needs remarks, faculty can re-save.
  A mapped faculty is locked after approval; admins are not.
- **COE** (`coeIndex`): read-only overview, no approve/reject.

## Masters

All server-side masters share the view `admin.coe.examination_term.index`, driven by a
`$master` config array and `CoeGrid.simpleMasterServerSide()`. Row ids are `encrypt()`ed;
store with `pk` = edit. Delete is allowed only for **inactive** rows that nothing uses.

| Master | Table | Key rules |
|---|---|---|
| Examination Type / Term / Component | `examination_type_master`, `term_master`, `component_master` | name unique, max 100 |
| Building | shared `building_master`, `building_type='Classroom'` only | name unique among Classroom buildings; inactive = **2** |
| Floor | shared `floor_master` | name unique; inactive = 2 |
| Room | shared `building_floor_room_mapping` | `room_name` = active `venue_master.venue_short_name`; `room_type` frozen to Classroom; laptop/scriber count ≤ capacity; `faulty_laptop_count` only if the DBA column exists |
| Subject Max Marks | `subject_max_marks_master` | one active row per subject; caps component max totals |
| Service Passing Marks | `service_passing_marks_master` | one per service; **labelled "Passing Percentage" but compared as raw marks** |
| Grade | `coe_grade_master` | active ranges may not overlap; **not used by any result calculation yet** |
| Scriber's Master | `coe_scriber_master` | phone 10 digits, unique email, role 1 Primary / 2 Secondary; can't change role / delete while assigned |
| Signature Master | `coe_signature_master` | designation from `designation_master`, person from active `employee_master`; jpg/png ≤ 2 MB on `local` disk `coe/signatures`; served via `signature-master/{id}/file` |

Exports: `Concerns/ExportsCoeMaster` → `ExportsMasterGrid::renderMasterExport`
(csv / excel / pdf / print, audit-logged). Client-side grids print with
`partials/print_brand.blade.php`.

## Question papers

Services: `QuestionPaperService` (faculty), `RajbhashaPaperService`, `AdminPaperService`,
`QuestionPaperStorage`, `QuestionPaperLogger`, `QuestionPaperNotifier`, `FreezeOtpService`.
Table `question_papers` (one per drive + subject, soft deletes), `question_paper_logs`
(append-only, no FK).

- **Statuses** (strings, also used as CSS classes, so keep the hyphens):
  `pending-faculty` (virtual, no row yet) → `pending-rajbhasha` → `translated` →
  `sent-for-approval` → `published`. Virtual `pending-cc` = drive not CC-approved yet.
  Each screen maps these to its own badge labels.
- Flow: faculty writes in Summernote (no file upload any more) → `pending-rajbhasha`;
  edit allowed in `pending-rajbhasha` / `sent-for-approval` (bumps version, sends back to
  Rajbhasha). Faculty can't edit while it is `translated` (Rajbhasha's unsent draft).
  Rajbhasha translates in place (templates are bilingual) → `translated` →
  Send for Approval → `sent-for-approval` (faculty: "Pending Verification"). There the
  faculty either **edits the original** (next version → back to Rajbhasha, translation kept
  for Rajbhasha to correct), or **edits the translation itself** (Edit Translated Paper,
  `QuestionPaperService::translationEditable()`: stays `sent-for-approval`, no trip back
  to Rajbhasha, `translated_by` unchanged, logged as Faculty / Translation / Updated), then
  **freezes with a 5-digit SMS OTP** → `published`. COE can **unfreeze** (back to
  `sent-for-approval`, same choices again; **remarks required**,
  `QuestionPaperListController::unfreeze` validates `remarks` required|max:500, the popup
  keeps itself open while the box is empty; stored in `unfreeze_remarks`, logged, and sent
  in the Reopened bell) or **delete** (route only, no button, any status).
- Faculty routes use the **`examination_drive_faculty_maps.id`** as `{paper}`; Rajbhasha
  and admin routes use `question_papers.id`.
- **OTP** (`FreezeOtpService`): hashed in cache 10 min, 5 sends / 5 wrong tries per 10 min,
  sent via `GupshupSmsService` using `COE_FREEZE_OTP_DLT_TEMPLATE_ID` /
  `COE_FREEZE_OTP_MESSAGE`. If unset, local/testing/staging **write the OTP to
  `laravel.log`** (`COE freeze OTP …` / `COE view OTP …`); other environments refuse.
  Two purposes, each with its own cache/throttle keys so one can't stand in for the other:
  `PURPOSE_FREEZE` (default, keys `coe_freeze_otp:*`, unchanged) and `PURPOSE_VIEW`.
- **Rajbhasha View Original Paper needs an OTP, every time** (`RajbhashaPaperService`
  `sendViewOtp` / `verifyViewOtp` / `takeViewPass`):
  1. View Original Paper or the original's See Attachment opens the shared OTP modal
     (`partials/freeze_modal`, retitled "Verify OTP"), which sends
     `POST my-question-paper/{paper}/original-otp`. The number is the login's
     `user_credentials.mobile_no`, else `employee_master.mobile`.
  2. `POST …/original-otp/verify` with the right code puts a one-time pass for that
     paper in the session (`coe_qp_view_pass.{id}`, 10 min).
  3. `GET …/original` uses the pass up; without one → 403. So every view needs a new OTP,
     and an OTP opens only its own paper. A browser (non-JSON) GET is 404 and doesn't
     use the pass. Both POSTs are `throttle:10,1`; the code is checked by
     `FreezeQuestionPaperRequest`.
  The pass lives in the session only: logout (`session()->invalidate()`) or session
  expiry drops it, so after a new login the OTP is asked for again (tested in
  `test_a_new_login_needs_the_otp_again`). An OTP *code* sent before is in the cache by
  user + paper and still works for its 10 min.
  View Translated Paper, and the faculty / admin original views, need no OTP.
- **Templates are Blade files** in `faculty_question_paper/templates/` (`standard`,
  `multi_section`, `case_study`, `omr`, shared `_head`, `_section`), registered in
  `FacultyQuestionPaperController::TEMPLATES`. COE sends templates as .docx; convert them
  by hand into Blade. The chosen template isn't stored, only the HTML.
- Paper sets page (`sets.blade.php`) is design-only with sample data; nothing is saved
  (an attempt to move sets into Add Question Paper was reverted in `ccf9a75a9`).
- Storage: `local` disk `coe/question-papers/{drive}/…html`, not encrypted. HTML is
  cleaned by `PaperHtmlSanitizer` on write **and** on every read. Legacy `.docx` papers
  are view-only.
- Viewer: JSON only (`ShowsPaperInViewer`), no-store headers, name·ID·IP·time watermark;
  the editor reports "Paper Hidden" (window left / PrintScreen). These guards only deter.
- Logs: every view/edit/freeze/print is logged with role, module, IP; failures never
  block the action.

## Roll numbers, UPC, mapping

- Participants of a course: `CourseParticipants` (`student_master.course_master_pk` UNION
  active `student_master_course__map`; batch = `COALESCE(NULLIF(batch,0), course_year)`).
- **One roll number and one UPC per OT across the whole system**; generation is refused
  if an OT already holds one on another course.
- **Roll numbers** (`RollNumberController`, `RollNumberAllocator`, `RollNumberWriter`):
  one allocation per course + batch (`coe_roll_number_allocations`, `coe_roll_numbers`).
  Format `prefix-serial` (padding = digits typed in Start Number). Strategies:
  allocation rules / random with rules / sequential by service, name, OT code. Rules:
  avoid same service consecutively, alternate gender (theatre rule stored, not applied).
  Regenerate rewrites the whole run. Excel import matches on Participant ID. Freeze locks
  the whole allocation, notifies OTs, **no unfreeze**.
- **Unique Participant Code** (`UniqueParticipantCodeController`, `coe_participant_codes`):
  anonymous code used instead of the roll number for evaluation (`UC-0324`, no
  separator, ordered by name). Freeze is per row; no unfreeze.
- **Roll No ↔ Unique Code Mapping** (`RollCodeMapping`): read-only, confidential (undoes
  the anonymity), COE only.

## Laptop and scriber

- **Laptop Availability Form** (`LaptopDeclarationController`): the OT's own one-shot form
  (OT resolved from the session, `user_category='S'`). Fields: laptop required + reason,
  has disability, scriber required + remarks.
- **OT Laptop Status** (admin): add one answer for many OTs of a course, or Excel import
  (laptop + reason only). OTs must hold a roll number.
- **Scriber only for PwBD OTs:** `LaptopDeclarationService::isPwbd(student_master.ph)`.
  Note the PH seating category comes from `has_disability` on the laptop response, a
  different signal.
- Saving scriber details for a course **unassigns** scribers from disabled OTs not in
  the submitted table (`assignScribers`).
- Table `coe_laptop_responses` (`source` 1 OT / 2 Admin, `disability_type` 1 Handicapped
  / 2 Blind).

## Venue and seat allocation

- Chain: `coe_seat_plans` (course + batch + component map + `candidate_category`
  0 All / 1 General / 2 PH) → `coe_seat_plan_venues` (`seating_mode` 1 system / 2 manual,
  `filled_by`) → `coe_seat_plan_venue_ots` (manual rooms) → `coe_seat_allocations`.
  Desk maps: `coe_venue_seat_layouts` (laptop/scriber seats JSON, per venue).
- Exam rooms (`ExamRooms`): Room Master joined to `venue_master` by
  `TRIM(room_name) = TRIM(venue_short_name)` (explicit collation); Classroom buildings
  only. Renaming either side breaks the join.
- `SeatAllocator` is pure and deterministic: manual seats kept, manual rooms seat only
  their listed OTs, laptop OTs first, spread service/cadre/gender, honour seat gap.
  Seat labels: blocks A=4, B=13, C=6 per row (`B-A05`). **`VenueAllocationController::SEAT_BLOCKS`
  and `public/js/coe-seat-map.js` must match.**
- Only drives that are not Closed **and** `cc_status = approved` (and whose course has roll
  numbers) can be seated; pending / rejected drives are neither listed nor accepted.
- **Change Room** (`VenueAllocationController::move`, `POST venue-allocation/{id}/move`, Edit
  Seating Plan only, not on a frozen plan): moves the ticked OTs of one room to a room from
  `moveRooms()` (the plan's other rooms with a free desk, then exam rooms no other plan holds
  on the paper's date; a new one is added as a Manual room). `freeDesk()` picks the first free
  desk: laptop desk if needed (else Room Master laptop count caps it), scriber desk for
  scriber OTs, seat gap first. The seat becomes `is_manual`; the OT's
  `coe_seat_plan_venue_ots` listing follows them. Other OTs are not re-seated.
- Saving is **blocked if any OT is left without a seat**; freeze (publish) also needs
  zero unseated. Manual rows hide OTs/rooms already picked in other rows (client-side,
  re-checked on the server). No unpublish.

## Results

All rules in `ResultMarksService` (RMS) and `MarksModeration`.

- **Result Entry (Faculty)** (`FacultyResultEntryController`, `coe_evaluation_marks`):
  open for drives that are Active **and** CC-approved, components whose exam date has
  passed. One column per drive:subject:component. Values: number ≤ component
  `max_marks` (2 decimals), `AB` = absent, blank clears. Status 1 Draft / 2 Approved;
  Approve needs every cell on the filtered sheet filled and **locks permanently**.
  Faculty see OTs per their assigned codes; COE staff (no faculty row) see everything.
  Excel import saves as draft only. `EvaluationMarksController` is an old mock (sample
  data, no table). The page opens on the first course; the Course / Subject / Component
  filters reload it, also when the sheet on screen is empty (fixed 2026-11-23: the handler
  used to sit after the script's "no Approve button" early return).
- **Marks Moderation** (`MarksModerationController`, Training section): SOP COE/11
  `Mi = (UMi − Xj) × σG/σj + μG` per column, across evaluators; absents excluded;
  clamp 0..max. Only when every assigned mark in the component is approved.
  Write-once into `moderated_marks`.
- **Result Compilation**: approved **raw** marks (not moderated), "Passing Average" is a
  mean score, Notify for Pending sends bell notifications.
- **Merit & Ranking**: an OT is ranked only when **every** held mark is final (approved
  and moderated, or absent). Uses moderated marks. Hard-coded `MERIT_SUBJECTS` columns,
  qualifying papers ICT/Hindi (not totalled), Director Assessment = exam type matching
  `/director/i`. Pass if Grand Total ≥ the student's service passing marks; fail if a
  qualifying paper is absent or below passing; undecided if the service has no passing
  marks. Ties share a rank (1, 2, 2, 4).

## Re-exam (`ReexamService`, `ReexamController`)

OTs who failed or were absent in a subject sit that subject again on a **re-exam drive**.
Built step by step: step 1 (COE creates it), step 2 (faculty dates, CC approval) and step 3
(question paper / Rajbhasha) are done.

- **Agreed rules:** a re-exam has **no moderation** (its marks count once approved, raw:
  `ReexamService::markIsFinal` / `markValue`; Marks Moderation never lists re-exam drives).
  The **re-exam marksheet shows only Pass / Fail, no marks**.

- **Tables (old ones untouched):** a re-exam drive is a normal `examination_drives` row
  (with its own faculty maps, component maps, question papers). `coe_reexams` links it:
  `main_drive_id` → `reexam_drive_id`, `reexam_no` 1, 2, 3. `main_drive_id` is always the
  **main** drive, also for Re-exam 2 (flat, no chain). `coe_reexam_candidates`: one row per
  re-exam + subject + OT, `reason` 1 Failed / 2 Absent, `score_percent` saved at creation.
  Deleting a re-exam drive cascades its link + candidates; a main drive with re-exams
  can't be deleted. Everything checks `ReexamService::enabled()` (tables exist).
- **Who is listed** (`ReexamService::sheet`): main drive Active/Closed, CC-approved, not
  Director Assessment, every exam over, **every participant's marksheet on the course
  published** (UDC → AAO → COE signed, `coe_marksheets.status = 2`; `marksheetRefusal`, agreed
  2026-11-23: no re-exam before the marksheets are out; a marksheet covers every drive of the
  course, so a pending drive elsewhere on the course holds the re-exam too), and **every
  participant's mark in every subject final** (`ResultMarksService::isFinal`: approved, and moderated when moderation is on),
  else "Results are not final yet" and no list. Per OT + subject: **Absent** if AB in any
  component, else **Failed** if any component is below `passingMarks()` for the OT's
  service (Merit rule). Score % = subject marks / max × 100.
- **Latest result:** an OT who sat the subject on an earlier re-exam is judged on that
  re-exam's marks (they replace the old ones). So Re-exam 2 lists who still failed, and
  waits until Re-exam 1's marks are final.
- **Drive list** (`ExaminationDriveDataTable`): the Re-exam button is rendered hidden on
  main drives that pass the cheap checks; after each draw the page calls
  `GET examination-drive/reexam-ready?ids[]=` once and shows the ready ones (the list
  itself reads no marks). Re-exam rows get a "Re-exam N" tag and Edit is disabled
  (server refuses `update` too).
- **Create** (`POST examination-drive/{id}/reexam`, JSON `keys` = "subject:student",
  `start_date`, `end_date`, optional `name`, default "{main} — Re-exam N"): under a lock the
  list is worked out again, unknown keys refused. Copies the drive (Active, `cc_status`
  null, CC fields cleared), the faculty maps and component maps of the ticked subjects
  (max marks, mode, type, sub-coordinators, assigned codes kept; exam dates and CC slots
  emptied). Bell "Re-exam Created" to the subjects' faculty (opens their setup page).
  Re-exam dates may be after the course end.
- Routes: `examination-drive/reexam-ready`, `examination-drive/{id}/reexam` (GET/POST),
  behind `menu.permission:coe_examination_drive,coe_coe_examination_drive`.
- **Kept out for now** (`ReexamService::excludeFrom`): `ResultMarksService::entryDrives` /
  `scopeDrives` (Result Entry, Compilation, Merit, Moderation, Marksheet),
  `MarksheetService::scopeOf`, Venue Allocation (`components`, mapping, `mappableDrives`).
  Question papers / Rajbhasha work on the re-exam drive unchanged (new paper per drive).
- **Faculty setup** (`facultySetup`, `saveComponents`): the view gets `$reexam`
  (no, main name); title "Map Faculty to Examination Drive Re-exam", every input disabled
  except the Tentative Exam Range, no add / remove / Add Component, an undated range starts
  empty. The server ignores everything but the range: `ReexamService::setupRows()` rebuilds
  the rows from the stored ones (refuses a component without a range), then the normal
  checks run (inside the drive dates = the re-exam dates, slot clash, ...). `assignFaculty`
  is refused. A subject counts as set up when all its components are dated
  (`ReexamService::datedSubjects`, `subjectsSetUp`, faculty list `setupCompleted`), so the
  drive goes to the CC once every subject is dated.
- **CC approve:** a re-exam's slots must be inside the re-exam drive dates (may be after
  the course end) instead of the course dates; the CC list sends those dates as
  `data-cc-course-start/end` with `data-cc-range-what="re-exam dates"`.
- **Question paper / Rajbhasha:** no code change; the re-exam drive's own faculty maps give
  a new paper (`question_papers` drive + subject) once the CC approves it.
- **Not built yet:** seating / result entry for candidates only, re-exam result and the
  re-exam marksheet (Pass / Fail only; planned as a new table, `coe_marksheets` untouched).

## Marksheets and signatures (`MarksheetService`)

- Generated **on GET** for every OT with a Pass/Fail result: `coe_marksheets`, number
  `MS/{COURSE}/{year}/{0001}`.
- Signing in strict order **UDC (Prepared By) → AAO (Compared By) → COE (Controller of
  Examination)**; signer = user's highest of the Spatie roles COE > AAO > UDC (Super Admin
  signs as COE). JSON `signed` column holds each signature.
- Agreed rule: UDC, AAO and COE must each have **exactly one Active** Signature Master row
  (designation `UDC` / `AAO` / `COE` or a name mapped to it, see below; image file present) or the marksheet
  can't be verified and published. Today the server enforces it on verify
  (`signatureProblem`); the Verify button still shows.
- COE's signature publishes the marksheet (status 2) and stores a `snapshot`; published
  sheets are read from the snapshot only.
- **Designation names → signatory:** Signature Master picks the designation from
  `designation_master` (full names, e.g. `UPPER DIVISION CLERK`), while signing works on
  `UDC` / `AAO` / `COE`. `MarksheetService::SIGNATORY_ALIASES` maps the accepted names
  (`UPPER DIVISION CLERK` → UDC, `ASSISTANT ADMINISTRATIVE OFFICER` → AAO, `COE-INCHARGE`
  → COE; the short names also work), matched case-insensitively by `signatoryOf()`.
  Group `activeSignatures()` rows with `signatoryOf()`, never by raw `designation_name`;
  query by `designationNamesOf()`. A designation not in the map still fails verify with
  "Upload the … signature…": add it to the map.
  `COE-Incharge` is a `designation_master` row (on the server; added by hand on the dev
  DB, pk 1998777339) held by the COE in-charge employee.
- No server PDF: `marksheet/show.blade.php` prints A4 via `window.print()`. The sheet
  itself doesn't print the marksheet number; under the table it has a blank
  "Remarks: ____" line (handwritten) next to Result. The LBSNAA logo
  (`images/lbsnaa_logo.jpg`) sits centred behind the sheet as a 12% opacity watermark
  (`.ms-watermark`, also printed).

## Notifications

In-app bell only (`NotificationService::createMultiple`, routes in
`config/notifications.php`), no mail. Types: `exam_drive`, `coe_question_paper`,
`coe_roll_number`, `coe_seat_allocation`, `coe_result_entry`, `coe_invigilation` (opens My
Invigilation Duty). Failures are logged and
never block the action. `coe_roll_number` has no entry in `config/notifications.php`, so clicking it falls back to the
dashboard; `coe_seat_allocation` opens My Exam Seat.

## Conventions

- Statuses are small ints with model constants (question papers use strings).
- Frozen / approved / published records can't be undone in the UI (roll numbers, UPC,
  seat plans, approved and moderated marks). Only question papers have Unfreeze.
- Some features check for columns at runtime (`Schema::hasColumn`) and quietly switch off
  when the column is missing: `coe_evaluation_marks.remarks` and
  `building_floor_room_mapping.faulty_laptop_count` (DBA DDL, no migration),
  `moderated_marks`, `coe_marksheets.signed`.
- Multi-row writes run in a transaction with `lockForUpdate` and re-check state;
  MySQL 1062 is turned into a "someone else saved" message.
- Active / Archived course scope: `CourseMaster::activeRunning()` /
  `archived()`; individual screens differ on NULL `end_date`.
- OT codes sort naturally with `compareOtCodes` (A2 < A10).
- Big grids post JSON to avoid `max_input_vars`.

## Known gaps (don't "fix" unasked)

- Route-level permission missing on many COE screens (see Access control).
- Signature Master designations outside `SIGNATORY_ALIASES` can't sign (see Marksheets).
- Service Passing Marks shown as % but used as raw marks; Grade Master unused.
- `weightage` stored but unused in results.
- Compilation totals (raw) differ from merit/marksheet totals (moderated).
- System seating doesn't enforce Room Master laptop count when a room has no seat layout.
- Same-date room clash check ignores start time.
- OMR template and some pages load assets from external URLs/CDNs.
- Rajbhasha's Translate Paper page has no OTP, though a first translation starts from
  (so shows) the faculty's original; only View Original Paper is OTP-gated.
