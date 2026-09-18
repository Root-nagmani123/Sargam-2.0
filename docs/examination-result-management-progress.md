# Examination & Result Management System — Progress Handoff

Reference: infographic "Examination & Result Management System" (System Workflow strip).
Note: the strip's own numbering has a typo — two stages are labelled "8" and "Online/Offline
Examination" appears twice (8 and 9). The table below merges those as "9-10", so its numbering
runs to 16 while the strip shows 15 badges.
Convention note: this module reuses the app's existing "programme-dt" theme (Blade + Yajra DataTables + Bootstrap 5 modals), NOT a new design system.

## Workflow status

| # | Stage (per infographic) | Status |
|---|---|---|
| 1 | Course Setup | Pre-existing (not part of this work) |
| 2 | Participants & Roll No. | Not started |
| 3 | Examination Drive | ✅ Done |
| 4 | Subjects & Components | ✅ Done |
| 5 | Data Source Mapping | ✅ Done — folded into stage 4's grid (`Source` column). No separate screen/table; see note below. |
| 6 | Faculty Mapping | ✅ Done |
| 7 | Venue & Laptop Allocation | Not started (next) |
| 8 | Question Paper & Translation | Not started |
| 9-10 | Online/Offline Examination | Not started |
| 11 | Evaluation & Marks Fetch | Not started |
| 12 | Moderation & Conversion | Not started |
| 13 | Result Compilation | Not started |
| 14 | Marksheet & Certificate | Not started |
| 15 | Awards & Medals | Not started |
| 16 | Reports & Analytics | Not started |

## Core conventions used throughout

- Lookup "_master" tables use custom PK column `pk` (not `id`), `bigIncrements('pk')`.
- Transactional tables (e.g. `examination_drives`, mapping tables) use standard `id`.
- FK columns to a master table are named `<table>_pk` (e.g. `subject_master_pk`).
- Masters have `active_inactive` (tinyint, default 1) + `scopeActive()` on the model.
- Timestamps: `created_date` / `modified_date` (not Laravel's default `created_at`/`updated_at`); models set `public $timestamps = false;`.
- All admin master routes live in `routes/master.php` under `Route::prefix('master')->name('master.')->middleware('auth')`.
- Modal-based CRUD masters: `index()` (Yajra render), `store()` (create+update via hidden `pk`/`id`, encrypted), `destroy()`. No unused create/edit blade routes.
- Status toggle switches use one shared generic endpoint: `POST /admin/toggle-status` (`table`, `column`, `id`, `status` params) — **known pre-existing security gap**: no allow-list on `table`/`column`, out of scope unless asked to fix.
- Sidebar "Setup/Master" panel is a static Blade partial: `resources/views/components/menu/setup_mappings.blade.php` (NOT DB-driven), gated by `hasRole('Admin') || hasRole('Training-Induction')`.
- Course-level access control: any listing **and every export that feeds off it** must apply
  `get_Role_by_course()` to the *query* (`whereIn('course_master_pk', $scope)`), not just to the filter
  dropdown's options. Empty array = unrestricted (Admin / Super Admin / PA); `[-1]` = fail-closed.
  See `CourseMasterDataTable::query()` for the canonical shape.
- Exports: Maatwebsite Excel `FromCollection` classes under `app/Exports/` + Barryvdh DomPDF branded templates (base64 LBSNAA/Ashoka logos) — NOT the hidden/unused Yajra `Button::make()` exports (those are CSS-hidden project-wide).

## Database tables created this session

1. **`examination_type_master`** — `pk`, `exam_type_name` (unique), `active_inactive`, `created_date`, `modified_date`.
2. **`term_master`** — `pk`, `term_name` (unique), `active_inactive`, `created_date`, `modified_date`.
3. **`examination_drives`** — `id`, `examination_type_master_pk` (FK), `term_master_pk` (FK), `course_master_pk` (FK to `course_master.pk`), `phase` (varchar, fixed list — no master table), `academic_session` (year, plain input), `start_date`, `end_date`, `status` (tinyint: 0=Draft,1=Published,2=Closed), `created_by`, `created_date`, `modified_date`. Indexes: `academic_session`, `status`, composite `(course_master_pk, academic_session)`.
4. **`component_master`** — `pk`, `component_name` (unique), `active_inactive`, `created_date`, `modified_date`.
5. **`examination_drive_component_maps`** — `id`, `examination_drive_id` (FK cascade), `subject_master_pk` (FK to `subject_master.pk`), `component_master_pk` (FK), `max_marks` (decimal 6,2), `passing_marks` (decimal 6,2), `source` (varchar 30: Faculty/System/Manual Entry), `weightage` (decimal 5,2), `created_date`, `modified_date`. Unique `(examination_drive_id, subject_master_pk, component_master_pk)`. **Business rule enforced in controller**: passing_marks ≤ max_marks per row, and SUM(weightage) must equal 100 per drive — ⚠️ the 100% rule is **unverified against the spec**, see Open findings #2.
6. **`examination_drive_faculty_maps`** — `id`, `examination_drive_id` (FK cascade), `subject_master_pk` (FK), `faculty_master_pk` (FK to existing `faculty_master.pk`), `created_date`, `modified_date`. Unique `(examination_drive_id, subject_master_pk)` — one faculty per subject per drive.

Pre-existing tables reused (not modified): `subject_master` (pk, `subject_name`, `active_inactive`), `faculty_master` (pk, `full_name`, `active_inactive`), `course_master`.

## Models

- `App\Models\ExaminationTypeMaster`, `App\Models\TermMaster`, `App\Models\ComponentMaster` — plain lookup models, `scopeActive()`.
- `App\Models\ExaminationDrive` — constants `STATUS_DRAFT/PUBLISHED/CLOSED`, `STATUS_LABELS`, `PHASES`; relations `examinationType()`, `term()`, `course()`.
- `App\Models\ExaminationDriveComponentMap` — const `SOURCES`; relations `drive()`, `subject()`, `component()`.
- `App\Models\ExaminationDriveFacultyMap` — relations `drive()`, `subject()`, `faculty()`.

## Controllers & routes (all under `routes/master.php`, prefix `master.`)

| Route name prefix | Controller | Notes |
|---|---|---|
| `examination.type.*` | `Admin\Master\ExaminationTypeMasterController` | index/store/destroy |
| `term.*` | `Admin\Master\TermMasterController` | index/store/destroy |
| `component.*` | `Admin\Master\ComponentMasterController` | index/store/destroy |
| `examination.drive.*` | `Admin\ExaminationDriveController` | index/store/destroy/get-courses-by-status/export (print,pdf,excel) |
| `examination.drive.components.*` | `Admin\ExaminationDriveComponentMapController` | `show($driveId)` / `store($driveId)` — bulk-replace rows per drive |
| `examination.drive.faculty.*` | `Admin\ExaminationDriveFacultyMapController` | `show($driveId)` / `store($driveId)` — upsert per subject |

All `{driveId}` params are `encrypt()`ed IDs (decrypted in controller via `decrypt($driveId)`).

## Views

- `resources/views/admin/master/examination_type/index.blade.php`
- `resources/views/admin/master/term/index.blade.php`
- `resources/views/admin/master/component/index.blade.php`
- `resources/views/admin/examination_drive/index.blade.php` — main listing, Active/Archived tabs, course filter (Choices.js), Create/Edit modal, Print/PDF/Excel export toolbar.
- `resources/views/admin/examination_drive/components.blade.php` — Subject & Component Mapping (spreadsheet-style add/remove rows, live weightage total).
- `resources/views/admin/examination_drive/faculty_mapping.blade.php` — one row per mapped subject, Faculty dropdown.
- Action icons on the Examination Drive listing row: Edit (pencil), Map Subjects & Components (`bi-diagram-3`), Faculty Mapping (`bi-person-badge`), Delete (trash).

## Sidebar

`resources/views/components/menu/setup_mappings.blade.php` → "General Master" collapse group has links to Examination Type Master, Term Master, Examination Drive, Component Master.

## Data seeded for manual QA (dummy data only, NO migration/seeder files created)

All seeded via temporary `/tmp/*.php` scripts run through `php artisan tinker --execute="require '...'"`, then deleted immediately — **repeat this same disposable-script pattern for any further test data**, do not add permanent seeders unless asked.

Row counts as of 17-09-2026 (seeded + rows added during manual QA):

- `examination_type_master`: 25
- `term_master`: 25
- `examination_drives`: 26 (uses existing `course_master` PKs)
- `component_master`: 23
- `examination_drive_component_maps`: 105 (weightage sums to 100 per drive)
- `examination_drive_faculty_maps`: 105

## Changes made after the initial build (17-09-2026)

1. **Course-level access control applied to the drive listing and exports.**
   `get_Role_by_course()` was being applied only to the course *dropdown options* in
   `ExaminationDriveController::index()`, so a course-restricted user still saw every drive in the
   grid and in the Print/PDF/Excel exports. Now applied to the query in both
   `ExaminationDriveDataTable::query()` and `ExaminationDriveController::filteredDrivesQuery()`.
   Verified across all 18 roles: Super Admin / PA → all 26 drives, course-restricted roles → their
   subset, roles with no course mapping → 0 (the `[-1]` fail-closed sentinel).

2. **Data Source Mapping removed** — see the dedicated section below.

## Open findings (not yet fixed)

Ordered by priority. Items 1-2 are decisions, not code.

### P1 — decide before building stage 7+

1. **The "weightage must total 100%" rule is unverified.** The controller hard-rejects any save whose
   weightages don't sum to exactly 100 *per drive*. Infographic panel 6 shows weightages summing to
   **70**, mirroring its Max Marks column — i.e. weightage there reads as *marks contributed*, not a
   percentage pool. Per-drive pooling also means adding a subject silently invalidates every existing
   row. Confirm the intended rule with the spec owner.

2. **Per-drive vs per-subject weightage.** Related to the above. Panel 6 has a single
   `Component / Subject` column and a flat row list; the build splits it into separate Subject +
   Component dropdowns. That split is arguably better, but it's what makes "sum to 100 per drive"
   ambiguous — with N subjects in a drive they share one 100-point pool.

### P2 — defects that will affect users

3. **Duplicate rows return a 500.** Nothing stops adding the same subject+component twice in the
   Subject & Component grid; the insert violates `ed_component_map_unique` and throws a
   `QueryException` instead of the 422 that every other rule in that controller returns.
   `ExaminationDriveComponentMapController::store()`.

4. **Permission scoping is incomplete.** The listing and exports are fixed (see above), but
   `ExaminationDriveController::store()` / `destroy()` and the `show()` methods of both mapping
   controllers still resolve a drive by id with no course-scope check. Encrypted ids make casual
   exploitation hard, but the same ciphertext is valid for any authenticated user — it is not a
   security boundary. Needs one shared "can this user touch this drive" guard.

5. **Re-saving the component map orphans faculty rows.** Stage 4 does delete-all-then-reinsert;
   `examination_drive_faculty_maps` is untouched. Remove a subject and its faculty row survives —
   hidden by the UI (the list is derived from the component map) but still in the table, and
   resurrected if that subject is ever re-mapped.

6. **Mapping stores aren't drive-scoped.** `ExaminationDriveFacultyMapController::store()` validates
   only that the subject/faculty exist in their masters, not that the subject belongs to this drive.

### P3 — hardening and polish

7. **`decrypt()` is unguarded everywhere** — a tampered `{driveId}` throws `DecryptException` and
   returns a 500 rather than a 404.

8. **No status guard on the mapping pages.** A drive with status **Closed** is still fully editable
   via both mapping screens.

9. **No unique constraint on `examination_drives`** — the same
   (type + term + course + phase + academic_session) can be created any number of times.

10. **Exports don't record which filter produced them.** An Active export and an Archived export
    produce identical headers. Both `export_pdf.blade.php` and `export_print.blade.php` already
    receive the request, so adding a "Filter: Active · Course: All" line under "Generated:" is small.

11. **"Active/Archived" and "Status" are unrelated notions of state.** The tabs are derived from
    `end_date` vs today; `status` is set by hand in the create modal. They never interact, so as of
    15-09-2026 the Active tab held 4 **Closed** drives and the Archived tab held 3 **Published** ones.
    Either rename the tabs to something time-based (Current / Past), make the tab respect `status`,
    or at minimum state the filter's meaning in the export header (see #10).

12. **Raw PKs in the DOM** — `setRowId('id')` on the drive grid and `data-id="<pk>"` on the master
    status toggles expose unencrypted primary keys, while every URL in the module encrypts them.

13. **Excel export omits the S. No. column** that both the PDF and Print templates include.

## Data Source Mapping — resolved (removed)

Stage 5 originally shipped as its own page + `data_source_mappings` table, storing `source` per
(drive, component). That duplicated `examination_drive_component_maps.source`, which stores it per
(drive, subject, component). Nothing synced them and they drifted apart in live data.

The reference infographic has **no** Data Source Mapping screen — `Source` is a column inside the
Subject & Component Mapping grid (panel 6). The separate page existed only to tick a workflow-strip
stage the design already covered.

Removed: `DataSourceMappingController`, `App\Models\DataSourceMapping`,
`data_source_mapping.blade.php`, the `examination.drive.data.source.*` routes, the
`bi-hdd-network` row icon, and the `data_source_mappings` table + its migration and ledger row.

**`examination_drive_component_maps.source` is now the single owner of data source.** Stage 11
(Evaluation & Marks Fetch) must read it from there. Do not reintroduce a second source column.

## Migration ledger note

This module's migrations (`2026_09_15_000001/000002/000003`, `2026_09_16_000001/000002/000004`) were run manually via `Schema::create()` calls inside `tinker` (not `php artisan migrate`) because an **unrelated pre-existing broken migration** (`2025_05_27_061836_create_fc_exemption_master_table` — table already exists error) blocks a full `php artisan migrate` run. Each new migration was still inserted into the `migrations` table manually to keep the ledger consistent. **Do not run a blanket `php artisan migrate`** until that pre-existing issue is fixed or skipped; run new migration files individually instead (`(require database_path('migrations/xxx.php'))->up();` via tinker, then insert its record into `migrations`).

`2026_09_16_000003` is intentionally absent — it created `data_source_mappings`, which was removed
(see "Data Source Mapping — resolved"). The file, the table and its ledger row are all gone. The gap
in the numbering is expected; do not recreate it.

## Next step

Per the workflow strip: **Step 7 — Venue & Laptop Allocation** (not yet scoped/started).

Before starting it, settle **Open findings #1 and #2** (the weightage rule). Both are spec questions
rather than code, and stage 12 (Moderation & Conversion) and stage 13 (Result Compilation) will be
built directly on whatever answer they get — changing it afterwards means migrating live marks.
