# Session-feedback query optimisation

Measured on the development database (`topic_feedback` 30,785 rows, `course_student_attendance`
39,794, `timetable` 653, `faculty_master` 538, MySQL 8.0.39, `ONLY_FULL_GROUP_BY` on).

Two areas, verified independently:

| Area | Result |
|---|---|
| **Admin reports** (`FeedbackController`, DataTable, portal service) | 104 calls, 14,855 ms → 8,427 ms (**43% faster**) |
| **Feedback Database** — walking all 20 pages | 15,574 ms → 4,196 ms (**73% faster**) |
| **Feedback Details** | 403 ms → 251 ms unfiltered (**38% faster**); 25–45% across all filters |
| **Pending Feedback** | query work 348–935 ms → 185–504 ms (**45–62% less**), 5 queries → 4 |
| **Student feedback page** (`CalendarController`) | 144 ms → 25 ms average over 300 trainees (**82% faster**) |

**No report or page returns different data**: 103/106 admin cases and 1202/1202 student cases are
byte-identical. All three exceptions are the paging fix — two reports went from silently losing a
row to reaching every row exactly once.

---

## Root causes

### 1. GROUP BY listed every selected column instead of the keys that determine them

Eight query sites grouped by their whole select list. Columns functionally dependent on a
primary key already in the key cannot split a group — they only widen the sort key. Several
are TEXT / long VARCHAR (`timetable.subject_topic`, `faculty_master.Permanent_Address`,
`course_master.course_name`), which pushed MySQL out of a plain key sort into a **"Sort row
IDs"** pass — a row-ID sort that re-reads every row.

`EXPLAIN ANALYZE` of the Feedback Database grid, before:

```
join (25,246 rows)             53 ms
temporary table               108 ms
sort on 8-column key      →   335 ms   ← +220 ms, the dominant cost
group aggregate           →   371 ms
2nd temp table + filesort →   375 ms
```

Grouping on `(f.pk, t.pk)` alone: **375 ms → 175 ms**, identical 484 groups.

### 2. Pagination counts re-ran the entire aggregation

`SELECT COUNT(*) FROM (<entire grouped query>) sub` built all 484 groups — every
`GROUP_CONCAT` of remarks, both `AVG`s, the `COUNT(DISTINCT)`, both sorts — then discarded
the data, on **every page change**. `COUNT(DISTINCT f.pk, t.pk)` over the same joins:
**267 ms → 59 ms**, same answer.

The same anti-pattern cost ~390 ms in the pending-students report.

### 3. Dropdowns scanned the fact table

`getDatabaseFaculties` scanned all 30k `topic_feedback` rows, joined and de-duplicated, to
populate a list of at most 538 faculty. Driving from `faculty_master` with `WHERE EXISTS`
short-circuits at the first match per faculty: **139 ms → 86 ms**.

### 4. The student feedback page cross-joined every faculty against every session

`CalendarController::studentFeedback()` — the page each trainee opens to see what feedback they
still owe — built its pending list with two queries that joined `faculty_master` on a JSON
predicate rather than a key:

```sql
JOIN faculty_master f ON (
     (JSON_VALID(t.faculty_master) AND JSON_CONTAINS(t.faculty_master, JSON_QUOTE(CAST(f.pk AS CHAR))))
  OR (NOT JSON_VALID(t.faculty_master) AND CAST(t.faculty_master AS CHAR) = CAST(f.pk AS CHAR)))
```

With no key to join on, MySQL formed the full product: **38,198 rows built to keep 81.**

Rewriting the predicate was not enough. Expressed as a correlated `JSON_TABLE` in the join, the
optimiser still put `faculty_master` in an upstream hash join with no condition and re-evaluated
the table function once per row (`loops=38198`) — the query got *slower*, 125 ms → 262 ms.

What worked was making the expansion a **self-contained derived table** with no correlation to
the outer query, so it is materialised once (~700 rows) and joined on `timetable_pk`:

```sql
JOIN (SELECT tt.pk AS timetable_pk, CAST(jt.faculty_txt AS UNSIGNED) AS faculty_pk
      FROM timetable tt CROSS JOIN JSON_TABLE(...) jt WHERE ...) fj ON fj.timetable_pk = t.pk
JOIN faculty_master f ON f.pk = fj.faculty_pk
```

125 ms → 13 ms for that query; the whole page 218 ms → 45 ms. See
`CalendarController::OLD_FACULTY_JSON_TABLE` and `TEACHING_FACULTY_JSON_TABLE`.

`studentFeedback_url()` and `studentFacultyFeedback()` were measured and left alone — they build
a leaner query already (~15 ms) and were confirmed unchanged by interleaved A/B.

### 5. Feedback Details sorted 20,808 joined rows to return 10

`feedbackDetails()` already paginates to 10 rows a page, so its ~290 ms was surprising. It is
almost entirely SQL (289 ms of 292 ms), split between two queries:

| Query | Before |
|---|---|
| Fetch one page (10 rows) | 195 ms |
| `COUNT(*)` for the pager | 94 ms |

The report orders by columns from **three different tables** — `timetable.START_DATE`,
`faculty_master.full_name`, `student_master.first_name` — so no index can serve the order.
MySQL joins every matching row and sorts the lot to hand back ten:

```
Limit: 10                                                    275 ms
  Sort: tt.START_DATE DESC, fm.full_name, sm.first_name       275 ms
    Stream results                             rows=20,808    257 ms
      Nested loop inner join x4                rows=20,808    190 ms
        Index lookup on tf using idx_is_submitted rows=32,132  62 ms
```

**Fix — deferred join.** Sort a narrow projection (primary key only), take ten keys, then
re-read the full rows for just those ten. The join and sort are unchanged; the wide payload —
including the `remark` TEXT column and the concatenated student name — is no longer carried
through them. The page fetch drops **195 ms → 2.1 ms**. See
`FeedbackController::feedbackDetailsPage()`.

The count cannot shed joins: they are **not** lossless. Of 32,132 feedback rows, the
`course_master` join drops 6,302 and the `student_master` join drops 15, so removing either
would change the number. Verified before assuming otherwise.

Net: **403 ms → 251 ms** unfiltered, 25–45% faster across every filter combination.

### 6. Pending Feedback ran the same aggregate twice per request

`pendingStudentsGroupedData()` built the page and its row count from two separate queries, each
re-running the expensive base aggregate (timetable x enrolment x student, an attendance `EXISTS`
and a feedback sub-aggregate). The count could not be cheapened the way the Database grid's was,
because its `HAVING` depends on the aggregates.

**Fix — one pass with a window count.** `COUNT(*) OVER ()` is evaluated across the whole filtered
set before `ORDER BY` and `LIMIT`, so it returns the same total the separate query did while the
aggregate runs once. Queries per request drop from **5 to 4**.

Measured at SQL level, minimum of 15 repetitions (the box was too loaded for stable wall-clock):

| Filter | Count query | Page query | Before | After (one query) | Saved |
|---|---|---|---|---|---|
| course 65 | 253 ms | 294 ms | 547 ms | 210 ms | **62%** |
| course 48 | 207 ms | 264 ms | 471 ms | 185 ms | **61%** |
| course 41 | 169 ms | 179 ms | 348 ms | 193 ms | **45%** |
| archive (all) | 430 ms | 505 ms | 935 ms | 504 ms | **46%** |

**Why a window function and not a temporary table or a CTE.** All three were considered; only this
one is read-only. A temporary table needs `CREATE TEMPORARY TABLES`, writes DDL on every page load,
leaves per-connection state and cannot run on a read replica. A CTE is read-only but MySQL 8 may
inline it and re-run the aggregate anyway, delivering nothing. The window function needs no
privileges, creates nothing (`Created_tmp_tables` measured at 0, same as before), and provably
makes one pass.

**The edge case that had to be preserved.** The old code counted first, then clamped the page, so
asking for page 999 returned the *last* page rather than an empty list. A window count cannot
report a total for a page it did not return, so an empty result falls back to the count query,
clamps, and re-reads. Page 999 still lands on page 4 with 11 students and a total of 71, exactly
as before — pinned by snapshot cases for page 999, page 0, negative pages and a no-match search.

### 7. `ORDER BY` was not a total order — a real, user-visible bug

Both the Feedback Database grid and Feedback Details sort on columns that are not unique — the
grid on `t.START_DATE, <session start time>` (tied for every faculty in the same slot), Details
on `tt.START_DATE, fm.full_name, sm.first_name`. With ties, `OFFSET`/`LIMIT` is undefined.

Verified on the **unmodified** code:

| Report | Rows reported | Rows actually reachable | Duplicated |
|---|---|---|---|
| Feedback Database | 484 | 483 | 1 |
| Feedback Details (busiest course) | 4,708 | 4,707 | 1 |

In both, one row appears on two pages and another can never be seen at all. Fixed with a unique
tie-break: `t.pk, f.pk` on the grid, `tf.pk` on Details.

**This defect is plan-dependent, which matters for how it is tested.** Running `ANALYZE TABLE`
part-way through this work was enough to stop the Details duplicate from manifesting, while the
ambiguous ORDER BY was still there — a functional walk passed against code that was still wrong.
The regression test therefore asserts the invariant rather than the symptom: it first proves the
sort columns are ambiguous on live data, then asserts the SQL the report actually runs carries a
unique tie-break.

---

## Changes

| File | Change |
|---|---|
| `app/Support/FeedbackReportGrouping.php` | **New.** The three minimal GROUP BY key sets, with the functional-dependency reasoning for each. Single source of truth for the 8 sites that previously duplicated the lists. |
| `app/Support/FeedbackReportCache.php` | **New.** Generation-stamped cache over the project's `RedisBackedCache`. |
| `FeedbackController::databaseQueryFoundation()` | **New.** Joins + filters with no SELECT/GROUP BY/ORDER BY, so the count path can reuse them without paying for aggregation it discards. |
| `FeedbackController::databaseRowCount()` | **New.** `COUNT(DISTINCT f.pk, t.pk)`; falls back to the materialised count when the average-based `HAVING` filter is active (it needs the aggregates). |
| `FeedbackController::databaseFacultyOptions()` | **New.** `EXISTS`-driven faculty dropdown. |
| `FeedbackController::pendingStudentsPage()` | **New.** Returns a page of pending students with `COUNT(*) OVER ()` as `pending_total`, so the page and its total come from one pass. |
| `FeedbackController::feedbackDetailsPage()` | **New.** Deferred join: sorts a primary-key-only projection, then re-reads the full rows for just that page. |
| `FeedbackController::pendingStudentsTotal()` | **New.** Counts students without building names, emails or the course-name `GROUP_CONCAT`; falls back to the full aggregate when the search box is used (it matches columns the lean query omits). |
| 8 GROUP BY sites | Now reference `FeedbackReportGrouping` constants. |
| `baseDatabaseQuery`, `FeedbackDatabaseDataTable::query` | Deterministic ORDER BY tie-break. |
| `CalendarController::submitFeedback` | Busts the report cache after inserting feedback. |
| `CalendarController::OLD_FACULTY_JSON_TABLE` | **New.** Derived table expanding `timetable.faculty_master` into (session, faculty) pairs. |
| `CalendarController::TEACHING_FACULTY_JSON_TABLE` | **New.** Same for `faculty_details`, filtered to `role = 'Teaching'`. |
| `CalendarController::studentFeedback` | Both pending queries join the expansions instead of cross-joining `faculty_master`. |

---

## Latent bug found, preserved, and NOT fixed here

**15 sessions collected no feedback at all, and no one was told. All of them closed in
December 2025, so nothing can be recovered — see "Scale and current status" below before
planning any fix.**

`timetable.faculty_master` holds a JSON array of id *strings* (`["92"]`) for 606 rows, but a
bare JSON *number* (`3`, `8`, `5`) for 47 rows. For those 47:

- `JSON_VALID('3')` is **true**, so the first branch of the predicate applies;
- `JSON_CONTAINS(3, '"3"')` is **false**, because a JSON number never equals a JSON string;
- the `NOT JSON_VALID(...)` fallback therefore never runs.

So no faculty matches, the session does not appear in a trainee's pending list, and feedback for
it is not collected. This predates the optimisation.

### Scale and current status

Measured 2026-09-01. The three counts are different questions and are easy to conflate:

| Measure | Count |
| --- | --- |
| Rows with a scalar (non-array) `faculty_master` | 47 |
| …of those, with `feedback_checkbox = 1` (i.e. that ask for feedback) | 28 |
| …of those, that collected **nothing at all** — the actual loss | **15** |

The other 13 of the 28 did collect feedback (25 submitted rows) despite the broken predicate.
Why they succeeded is **not established** — some other write path evidently reaches them. Do not
assume the predicate is the only route to `topic_feedback`.

**The window is closed.** Scalar rows cover sessions from 2025-12-03 to 2025-12-26 only; every
row before and since holds a proper array (2006-02-04 .. 2026-07-10). Whatever wrote the scalar
form stopped in December 2025. All 28 checkbox-on sessions have ended, and **none sits on a
still-active course**, so no fix — data or code — recovers any feedback.

The rewrite **reproduces this exactly** rather than fixing it, and
`StudentFeedbackFacultyExpansionTest::test_scalar_json_faculty_master_still_matches_no_faculty`
asserts it stays reproduced. Fixing it here would have changed reported history as a side effect
of a performance change — that is a product decision, not a refactor.

**To fix it deliberately**, normalise the data rather than loosening the query:

```sql
-- inspect first
SELECT pk, faculty_master FROM timetable
WHERE JSON_VALID(faculty_master) AND JSON_TYPE(faculty_master) <> 'ARRAY';

-- then, once confirmed
UPDATE timetable SET faculty_master = JSON_ARRAY(CAST(faculty_master AS CHAR))
WHERE JSON_VALID(faculty_master) AND JSON_TYPE(faculty_master) <> 'ARRAY';
```

After that the 47 rows behave like every other legacy session, and the test above will need its
expectation updated to match the decision.

**The reason to run this is not feedback recovery** — those sessions are closed. It is that seven
other files read `timetable.faculty_master` with the same `JSON_CONTAINS` / `FIND_IN_SET` pattern
(`DashboardController`, `UserController` at three sites, `TimetableReportController`,
`CourseAttendanceNoticeMapController`, `CalendarController`, `FacultyFeedbackReportService`), and
each of them silently under-reports these 47 sessions in historical views. One `UPDATE` corrects
all of them; changing any single query corrects one.

---

## Known limitations of this change

Things review raised that are **recorded rather than fixed**, each with the reason.

| Limitation | Accepted by | Review by |
| --- | --- | --- |
| Cache staleness on the three lookup caches | _unassigned — Feedback module owner to sign_ | next change to these reports |
| showFacultyAverage all-programs filter divergence | _unassigned — Feedback module owner to sign_ | when any course carries feedback while `active_inactive <> 1` |
| Derived tables not bounded by `feedback_checkbox` | _unassigned — Feedback module owner to sign_ | when `timetable` grows by an order of magnitude |

Each is Low or Advisory, each was measured, and each fix would change behaviour for no present
benefit. The accepter column is deliberately blank rather than filled with a guess: an accepted
risk with no name against it is a note, not a decision.

### Cache staleness on the three lookup caches

`db_faculties`, `topics:course:{id}` and `faculty_suggestions` are invalidated only by
`FeedbackReportCache::bust()`, which is called from one place — after a successful `topic_feedback`
insert in `CalendarController::submitFeedback()`. The lookups themselves derive from `timetable`
and `faculty_master`, and writes to those tables do **not** bust the generation.

**Consequence:** a newly added session topic, or a renamed faculty member, can be missing from the
report dropdowns for up to the TTL — 900 s for the two lookups, 600 s for the typeahead. Before
this change those lists were always fresh.

**Accepted, not fixed.** Adding `bust()` to the timetable and faculty write paths means changing
cache-invalidation behaviour in controllers outside this work. The staleness is bounded and
self-healing, and no wrong data is served — only a briefly incomplete dropdown. Revisit if users
report missing options rather than pre-emptively.

### showFacultyAverage's all-programs path is filtered slightly differently from its exports

On the all-programs path (no single programme selected), `showFacultyAverage()` restricts the query
with `whereIn('cm.pk', $programs->keys())`. `$programs` is built from `course_master` with
`active_inactive = 1` **plus** the course-type date test, whereas the main query's own course-type
filter tests `cm.end_date` only. The `whereIn` therefore carries an implicit `active_inactive = 1`
that the query never had — and `exportExcel()`, `exportPdf()` and `printFacultyAverage()` did not
receive the same `whereIn`.

**Consequence in principle:** a deactivated course whose `end_date` still passes the course-type
test would appear in the exports but not on screen.

**Consequence in practice: none today.** Measured 2026-09-01 — courses with `active_inactive <> 1`
that carry submitted feedback: **0**; submitted feedback rows on them: **0**. There is nothing for
the filter to exclude.

**Accepted, not fixed.** Aligning it is a query-behaviour change, and a behaviour change whose only
effect today is nil is a poor trade inside a performance PR. Fix it deliberately if inactive
courses ever start carrying feedback — the check is the two counts above.

### The derived tables are not bounded by feedback_checkbox

`OLD_FACULTY_JSON_TABLE` and `TEACHING_FACULTY_JSON_TABLE` expand every `timetable` row on each
student-feedback page load, though only sessions with `feedback_checkbox = 1` can survive the
consumers' join — 653 rows expanded for the 613 that matter, about 6% waste today.

Adding `WHERE tt.feedback_checkbox = 1` inside each derived table looks free, and measured against
the consumers' join it is: 677 (session, faculty) pairs either way, 21 fewer rows expanded.
**It was tried and reverted.** `StudentFeedbackFacultyExpansionTest` pins these derived tables as
reproducing the original `JSON_CONTAINS` predicate *exactly* — unconditionally, not merely after
the consumer filters — and the narrowed version fails that assertion, along with the eight
synthetic-shape cases whose fixture has no `feedback_checkbox` column.

**Accepted, not fixed.** The equivalence contract is worth more than 6% of an expansion that is
cheap at this scale. Revisit together with the tests if `timetable` grows by an order of magnitude.

### The cache is off unless the store is configured

See the deployment note in `app/Support/FeedbackReportCache.php`. Without
`REDIS_BACKED_CACHE_STORE` (or `APP_REDIS_CACHE_STORE`), the chain resolves to `redis` and the
documented `cache.default` fallback does **not** engage.

**Production is fine:** it sets `CACHE_DRIVER=redis`, so the resolved store and the fallback are
the same store and the gap cannot bite. **Developer and CI boxes are the exposure** — with no
`.env`, `cache.default` is `file` while the chain still asks for `redis`, so every cache call
throws, is reported, and recomputes. Set `REDIS_BACKED_CACHE_STORE=file` there. This is a
deployment setting, not a code defect, and it governs the Estate, Mess and DataTable caches too.

---

### Not changed, deliberately

- **No index migration.** Both candidate indexes were measured and made things **worse**:
  `topic_feedback (faculty_pk, is_submitted)` 86 → 121 ms;
  `student_master_course__map (active_inactive, course_master_pk, student_master_pk)` 727 → 855 ms.
  Every win here is query shape. `student_master_course__map` still has no index but `PRIMARY`
  — at 3,483 rows that is currently faster than maintaining one; revisit if it grows.
- **The non-sargable session-time predicate** —
  `TIMESTAMP(t.END_DATE, STR_TO_DATE(TRIM(SUBSTRING_INDEX(t.class_session,'-',-1)),'%h:%i %p'))`
  — parses a time out of a string per row and can never use an index. Cheap at 653 timetable
  rows, but it scales linearly. Fixing it means a real `session_end_at DATETIME` column;
  out of scope here.
- **`tt_facultyfeedbackdtls`** — 782,752 rows / 192 MB, the largest table in the schema, no
  index on `FACULTY_CD` / `COURSE_CD` / `SESSION_DATE` / `SESSION_ID` / `OT_CODE`, and **zero
  references** anywhere in `app/`, `resources/` or `database/`. Looks like a dead legacy import.
  Confirm before anyone writes a report against it.

---

## Caching

`App\Support\FeedbackReportCache` wraps the project's existing `App\Support\RedisBackedCache`,
so it uses Redis wherever that is configured and falls back to `cache.default` otherwise.

| Cached | Key | TTL |
|---|---|---|
| Feedback-database faculty dropdown | `db_faculties:course:{id|all}` | 900 s (`TTL_LOOKUP`) |
| Topic dropdown for a course | `topics:course:{id}` | 900 s (`TTL_LOOKUP`) |
| Faculty typeahead suggestions | `faculty_suggestions:{types}:{term}` | 600 s (`TTL_SUGGESTIONS`) |
| Pending-feedback stats | `pending_stats:{course|all}` | 300 s (`TTL_STATS`, the pre-existing value) |

**Invalidation is by generation counter, not key deletion.** Entries are namespaced
`feedback_reports:v{N}:...`; `FeedbackReportCache::bust()` increments `N`, retiring every entry
at once. Key-by-key deletion is not possible here — the keys are keyed by arbitrary filter
combinations — and cache tags are unavailable on the file store this project falls back to.
`bust()` runs after `CalendarController::submitFeedback` inserts feedback.

**Only viewer-independent values are cached.** Several feedback queries are scoped by role
(see `ScopesSessionFeedbackReports`); caching those without the viewer in the key would leak
one user's rows into another's report. The four entries above are not role-scoped — access is
asserted before the lookup, not filtered inside it. Keep that property when adding entries.

A cache-store failure is caught and falls through to computing the value, so a Redis outage
degrades performance but does not take a report down (covered by a test).

### Enabling Redis

Redis is **not installed on this development box** (no `phpredis`, no `predis`, no server), so
the Redis path is written against Laravel's cache contract but was **verified only on the file
store**. `config/cache.php` already defines a `redis` store and `config/database.php` a `redis`
connection. To turn it on in an environment that has Redis:

```ini
REDIS_BACKED_CACHE_STORE=redis    # currently "file" in .env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

plus `pecl install redis` (or `composer require predis/predis`). No code change is needed —
`RedisBackedCache::repositoryForStore()` resolves the store name and falls back safely if it
is not configured. Please smoke-test the four cached endpoints after switching.

---

## Verifying that report data is unchanged

Two snapshot harnesses were written for this work and **removed from the branch before
merge** — they were verification scaffolding, not shipping code. Restore them from git
history when a future change touches these queries:

```bash
git checkout 4b1d67655 -- scripts/feedback_report_snapshot.php \
                          scripts/student_feedback_snapshot.php \
                          scripts/feedback_snapshot_diff.py
```

Once restored, both are re-runnable:

```bash
# Admin reports: 120 report/filter combinations
php scripts/feedback_report_snapshot.php /tmp/before --timing
#   ...apply changes...
php scripts/feedback_report_snapshot.php /tmp/after --timing
python3 scripts/feedback_snapshot_diff.py /tmp/before /tmp/after   # exit 0 = no data change

# Student pages: pending + submitted lists per trainee (default 12 students)
php scripts/student_feedback_snapshot.php /tmp/stu_before --timing --students=300
#   ...apply changes...
php scripts/student_feedback_snapshot.php /tmp/stu_after --timing --students=300
python3 scripts/feedback_snapshot_diff.py /tmp/stu_before /tmp/stu_after
```

To capture a true baseline, copy the modified files aside, `git checkout --` them, run the
harness, then copy back. Do **not** use `git stash` for this.

**Use `course_type=archived` in any manual check.** Every course carrying feedback in this
database has already ended, so the default "current" view reports on an empty set — an early
version of the harness compared nothing but page chrome for three reports until this was found.
The harness now drives Details, Average and View over archived data explicitly.

The harness covers the Feedback Database grid (filters, paging, conditional HAVING, exports),
Faculty Average, Faculty View, Feedback Details, pending-feedback grouped/stats/sessions, the
faculty-portal service, and the export data builders. It runs as a Super Admin so nothing is
scoped away, and flushes the cache first so both sides compute from cold.

Two normalisations, both deliberate:

- **Rows are sorted before hashing.** Ordering is asserted separately by the
  `PAGING_INTEGRITY` case, which is a stronger check than comparing to a baseline that was
  itself non-deterministic.
- **Remark bundles are compared as a case-folded set.** `GROUP_CONCAT(DISTINCT ...)` under
  `utf8mb4_general_ci` treats `Good` and `gOOD` as the same element and keeps whichever it
  meets first — already non-deterministic before these changes. Only fields named
  `all_comments` / `comments` / `remarks` / `remark` / `suggestions` are normalised this way;
  addresses also contain newlines and are compared exactly.

Final result: **81 of 83 cases byte-identical, 0 real differences.** The two that differ are
the paging cases, where `PAGING_INTEGRITY` goes **FAIL → PASS**.

### Regression tests

`tests/Feature/FeedbackReportOptimizationTest.php` — 11 tests, read-only, skips when fixture
data is absent (the suite runs against the database `.env` points at; never add
`RefreshDatabase` here). It asserts each narrow GROUP BY key produces the same number of
groups as the original wide key, that the cheap count matches the materialised count across
four filter sets including the `HAVING` fallback, that paging yields every row exactly once,
that the `EXISTS` dropdown matches the old `DISTINCT` join, and that cache busting and
cache-failure fallback work.

`tests/Feature/StudentFeedbackFacultyExpansionTest.php` — 12 tests covering the
`CalendarController` rewrite: both expansions produce exactly the same (session, faculty) pairs
as the original `JSON_CONTAINS` predicates over live data, plus eight synthetic JSON shapes
(arrays of strings, arrays of numbers, bare scalars, non-canonical `"012"`, invalid JSON) driven
as SQL literals. The synthetic cases matter because the live data exercises only two shapes, so
the guards inside the expansion are otherwise untested — removing them changes nothing today but
would change behaviour on a future import.

All of these were mutation-checked, and each mutation is caught:

| Mutation | Caught by |
|---|---|
| drop `t.pk` from `DATABASE_GRID` | 2 errors + 1 failure |
| drop `tf.topic_name` from `FACULTY_VIEW` | `484 !== 485` — confirms `topic_name` is genuinely not determined by `timetable_pk` |
| drop `role = 'Teaching'` from the expansion | extra pair `712:84` appears |
| drop the `JSON_TYPE(...) = 'STRING'` guard | synthetic `[1]` diverges |
| drop the canonical-text check | synthetic `["01"]` and `1x` diverge |

---

## Run ANALYZE TABLE

The feedback tables had **no optimizer statistics recorded at all** (`update_time` NULL on all
of them). Refreshing them cut the Feedback Details count query from 104 ms to 81 ms on its own,
with no code change, and made a `NO_INDEX_MERGE` hint unnecessary — the hint was measured, found
to be worth only ~7% once statistics were fresh, and deliberately not added.

```sql
ANALYZE TABLE topic_feedback, timetable, student_master, faculty_master,
              course_master, course_student_attendance, student_master_course__map;
```

Worth adding to routine maintenance; it is cheap and needs no downtime.

## Known remaining slow paths (not addressed)

Measured with baseline and candidate interleaved to cancel drift; these are **unchanged** by
this work and are the obvious next targets:

| Path | Time |
|---|---|
| `buildSummaryExportQuery` (pending-summary export) | ~380–430 ms |
| `getPendingStats` cold | ~150–230 ms (cached 300 s, so paid once per 5 min) |
| `pending.grouped.archive` after optimisation | ~800 ms (down from ~1,350 ms) |

### Where the pending-feedback time actually goes

An earlier draft of this document claimed the next significant win was
`expected_feedback_count_sql()` — the `JSON_VALID` + `JSON_SEARCH` + `JSON_LENGTH` expression
evaluated once per *joined* row when it depends only on the timetable row. **That was measured
and is wrong**: replacing the whole expression with a constant moved the aggregate only
165 ms → 148 ms, about 10%. Precomputing it per session is not worth the surface area.

The real remaining cost is structural. `pendingStudentsGroupedData()` runs the same expensive
base aggregate — timetable × enrolment × student, an attendance `EXISTS`, and a feedback
sub-aggregate — **three separate times** per request:

| Query | Share |
|---|---|
| Page fetch from the aggregate | largest |
| `COUNT(*)` for the pager (needs the HAVING, so it cannot skip aggregation) | second |
| Detail rows for the students on the page | third |

Computing that base set once and reusing it — a temporary table, a CTE, or restructuring so the
count and page come from a single pass — is the next real win, worth roughly a third to a half of
the pending-feedback cost. It is a structural change and needs the snapshot harness run over the
pending cases before and after.
