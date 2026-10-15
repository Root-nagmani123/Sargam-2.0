# Release runbook - Notice audience dependency and rollback safety (F-018 / F-055)

## Forward-fix-only decision (F-055)

The Product/release owner has chosen **forward-fix only** for notice audience
targeting. Retain both audience migrations, the map data and notice audience
columns. Repair failures with an audience-aware forward release; **do not roll
back the audience-targeting migration or backfill**.

An application-only rollback to an audience-unaware release is also unsafe:
the old feed does not read `notice_audience_map`. Retaining the table does not
prevent it from showing targeted/Personal notices to unintended staff.
**Do not switch to `main` or a previous artifact as routine recovery.**
Any exceptional code rollback requires renewed Product/release approval and
every safety gate in section 5a **before the old code runs**.

### F-055 approval record

For release candidate
`5a2d06ae0fcd0818d2ceb4ce93ea775b6668db28`, the operator supplied an approval
from Nibhash Mishra for forward-fix-only recovery at 10:12 on 2026-10-14.
Routine rollback to audience-unaware code is not permitted; retain notice
audience data and recover by deploying an audience-aware forward fix. This
records the recovery-policy decision only; it is not evidence that release
activation or the exceptional rollback procedure has been executed.

### F-069 no-category trainee notice policy

A no-category Officer Trainee login without a verified trainee identity receives
only notices addressed to `All`; it does not receive course-, group- or
individually-targeted Office trainee notices. The Product Owner supplied this
policy decision for F-069.

The operator-provided production count result for `testsargam2` on server
`b79f809f3072` showed 6 email matches (6 logins, 6 students); the returned
result contained no nonmatch group. This is a count snapshot, not a substitute
for repeating the query against the target database when required.

## Mandatory release gate

**Do not activate or serve the new notice feed code until the target database
has `notice_audience_map`, the notice audience columns, and both migrations
below completed.** Uploading an inactive release artifact is permitted;
switching the document root/release pointer, updating a serving checkout,
restarting workers onto the new release, or reopening traffic is not.

The feed directly queries the audience map. A code-first release can return
HTTP 500 on the dashboard. An empty audience map is not proof of a completed
backfill, and a migration-history entry alone is not proof of an intact schema.
There is no deployment pipeline in this checkout enforcing this sequence:
the Release owner must enforce these stop gates in the deployment system or
the signed operator checklist. Documentation alone does not close F-018.

Required migrations, in this order:

1. `2026_09_28_100000_add_audience_targeting_to_notices_notification`
2. `2026_09_28_120000_backfill_notice_audience_map_scalars`

The first creates the map and notice columns. The second backfills existing
scalar audiences. Its `up()` returns without backfilling if the prerequisite
tables are absent; therefore do not accept a "Ran" status without the physical
schema checks below. A previously skipped backfill requires DBA-supervised
reconciliation before activation; do not edit migration history blindly.

## 1. Production-like rehearsal (before release approval)

Release owner and DBA:

- Identify the exact candidate SHA and a designated production-like MySQL
  database with representative, appropriately protected notice/audience data.
  Record the database identity and migration baseline, not credentials.
- Back up the database and prove restore availability. Review all pending
  release migrations; MySQL DDL is not protected by a test transaction.
- Keep the candidate artifact inactive. Run its migrations with the intended
  database connection, then run the schema checks in section 3.
- Run the application smoke tests from section 4 against this migrated
  rehearsal database. Confirm intended recipient inclusion and exclusion for
  representative legacy, course, group, department and individual audiences.
- Record results, skips, errors, duration, SHA and operator approvals.
  Missing fixtures, a skipped test, or a local-development pass is not approval.

## 2. Production execution: migration BEFORE code activation

Release owner:

1. Confirm DBA and release approval, the backup, the database target and the
   inactive candidate artifact. Do not pull new code into a serving checkout.
2. Drain traffic and suspend scheduled jobs/queue consumers if using a
   maintenance-window release. Keep the old artifact available for recovery.
   Do not start candidate web processes or workers.
3. DBA runs the approved migrations from the **inactive candidate directory**
   using the production connection. Ensure its resolved configuration targets
   the intended database; copied/stale config caches must not redirect it.
   Execute serially and stop immediately on a nonzero exit.

   ```powershell
   php artisan migrate --force --path=database\migrations\2026_09_28_100000_add_audience_targeting_to_notices_notification.php
   if ($LASTEXITCODE -ne 0) { throw 'STOP: notice audience schema migration failed' }
   php artisan migrate --force --path=database\migrations\2026_09_28_120000_backfill_notice_audience_map_scalars.php
   if ($LASTEXITCODE -ne 0) { throw 'STOP: notice audience backfill failed' }
   ```

   These are the two notice prerequisites, **not** a substitute for the release's
   other approved migrations. If either is already recorded, Laravel skips it;
   section 3 must still pass and the DBA must confirm backfill completion.
4. Run section 3 on the database used by the web application (and each
   independently configured feed read connection, if applicable). **STOP if any
   check fails or backfill completion is unverified. No code activation.**
5. Only after the DBA records PASS, activate the exact rehearsed candidate,
   rebuild its normal deployment caches, restart workers, and perform section 4
   using controlled authenticated accounts before reopening traffic.
6. Reopen traffic only after the Release owner signs the checklist. Monitor
   dashboard/feed errors, especially missing-table SQL errors.

## 3. Required read-only database evidence

Run in the approved SQL client against the actual application database. Record
the results with the candidate SHA. Never paste connection credentials.

```sql
SELECT DATABASE() AS database_name, VERSION() AS database_version;

SELECT migration, batch
FROM migrations
WHERE migration IN (
  '2026_09_28_100000_add_audience_targeting_to_notices_notification',
  '2026_09_28_120000_backfill_notice_audience_map_scalars'
)
ORDER BY migration;

SELECT TABLE_NAME, COLUMN_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND (
    (TABLE_NAME = 'notice_audience_map' AND COLUMN_NAME IN (
      'pk', 'notices_notification_pk', 'audience_type', 'reference_pk',
      'active_inactive', 'created_date'
    ))
    OR
    (TABLE_NAME = 'notices_notification' AND COLUMN_NAME IN (
      'department_master_pk', 'group_type_map_pk', 'audience_mode'
    ))
  )
ORDER BY TABLE_NAME, COLUMN_NAME;

SHOW INDEX FROM notice_audience_map;
SHOW INDEX FROM notices_notification WHERE Key_name = 'idx_nn_department';
```

PASS requires the intended database identity, both migration rows, all six map
columns, all three notice columns, and the primary key plus `idx_nam_notice`,
`idx_nam_reference`, and `idx_nn_department` with the definitions in the schema
migration. DBA must also confirm representative legacy scalar audiences were
backfilled to C/G/D rows; table existence alone cannot establish that.

## 4. Application verification

On the **production-like rehearsal database only**, run:

```powershell
php vendor\bin\phpunit --filter 'Notice(DeploymentReadiness|Audience|LegacyOfficeTraineeAudience|OtAudienceCourseMembership)Test'
if ($LASTEXITCODE -ne 0) { throw 'STOP: notice rehearsal tests failed' }
```

These tests read reference data and some create rolled-back fixtures. Do not
run the PHPUnit suite against production. Inspect the output: skipped tests
remain gaps, even when the process exits zero.

Before reopening production traffic, use controlled staff/faculty and Officer
Trainee accounts to verify:

- `/dashboard` returns HTTP 200 and renders completely.
- `/dashboard/feed?tab=notices` returns HTTP 200 with notice content or the
  normal empty state.
- `/dashboard/feed?tab=notices&notice_scope=archive` also returns HTTP 200.
- Known targeted notices reach intended recipients and not outsiders.
- A no-category Officer Trainee login without a verified trainee identity receives
  only notices addressed to `All`, not course-, group- or individually-targeted
  Office trainee notices.
- No missing-table exception appears in the application log.

A redirect to login, HTTP 403, or a successful health endpoint that does not
query the notice feed does not satisfy this gate.

## 5. Failure and recovery

- Failure before activation: keep the candidate inactive and traffic drained;
  DBA diagnoses schema/backfill failures and reruns only the approved,
  reconciled migration steps. Do not bypass the gate to restore availability.
- Failure after activation: drain traffic, suspend notice writers and consumers,
  and deploy an audience-aware forward fix. Do not restore an audience-unaware
  previous artifact unless the exceptional procedure in section 5a is approved
  and completed first.
- Do **not** run `migrate:rollback`, `migrate:reset`, `migrate:refresh`,
  `migrate:fresh`, or either notice migration's `down()` as recovery, even after
  switching application code. The schema migration's `down()` drops
  `notice_audience_map` and destroys audience data; the backfill's no-op `down()`
  does not make application rollback safe.
- Any data restoration/removal requires DBA approval and the backup. Record
  remaining risks rather than marking release execution complete.

## 5a. Exceptional code rollback: deactivation BEFORE old-code activation

This is a contingency gate, **not authorization to override forward-fix only**.
If a gate cannot be proven, keep traffic closed and forward-fix.

1. **Product owner / Release owner:** approve the exception, exact rollback SHA,
   notice unavailability and recovery plan. Rehearse the exact old artifact on
   a migrated production-like copy; a moving `main` branch is not a rollback
   target. Retain the schema and map data throughout.
2. **Release owner:** drain all traffic to affected instances and stop notice
   creation/edit/status changes, queues, scheduled jobs and other notice writers.
   Keep this write freeze through verification. No old-code instance may start
   serving before the deactivation transaction is committed and checked.
3. **DBA / Module owner:** export affected notices, their prior
   `active_inactive` values and all audience rows to approved protected storage.
   Record an approved notice-PK manifest, row counts, database identity and
   rollback SHA. Include active **and inactive**, expired and future notices:
   reactivation or expiry changes must not later expose one on old code.
4. Inventory at least the following conservative candidate set:

   ```sql
   SELECT n.pk, n.notice_type, n.target_audience, n.audience_mode,
          n.active_inactive, n.display_date, n.expiry_date
   FROM notices_notification AS n
   WHERE n.audience_mode IS NOT NULL
      OR LOWER(TRIM(n.notice_type)) = 'personal'
      OR EXISTS (
        SELECT 1 FROM notice_audience_map AS a
        WHERE a.notices_notification_pk = n.pk
      )
   ORDER BY n.pk;
   ```

   This deliberately includes all targeting-era modes (even `all`) and every
   mapped notice (even inactive map rows and legacy backfills). Hiding some
   broadly addressed notices is an availability cost the Product owner must
   accept, not a reason to omit a potentially restricted notice. Do not narrow
   to `audience_mode = 'individual'`, current expiry dates, Staff/Faculty only,
   or active map rows. The Module owner must identify any additional restricted
   legacy notices not represented by these fields. If the safe set cannot be
   established, **STOP the rollback**.
5. **DBA:** after approval, deactivate the full approved manifest by setting
   `notices_notification.active_inactive = 0`; do not delete notices, alter
   recipients, or erase the map. Execute in a transaction, reconcile the selected
   PKs with the manifest, verify every manifest row has status 0, and commit.
   The following is the conservative candidate-set operation, not a command to
   execute without the approvals/export above. Additional legacy PKs from step 4
   must be included in the same transaction using the approved manifest.

   ```sql
   START TRANSACTION;

   UPDATE notices_notification AS n
   SET n.active_inactive = 0
   WHERE n.audience_mode IS NOT NULL
      OR LOWER(TRIM(n.notice_type)) = 'personal'
      OR EXISTS (
        SELECT 1 FROM notice_audience_map AS a
        WHERE a.notices_notification_pk = n.pk
      );

   SELECT COUNT(*) AS unsafe_active_candidates
   FROM notices_notification AS n
   WHERE n.active_inactive = 1
     AND (
       n.audience_mode IS NOT NULL
       OR LOWER(TRIM(n.notice_type)) = 'personal'
       OR EXISTS (
         SELECT 1 FROM notice_audience_map AS a
         WHERE a.notices_notification_pk = n.pk
       )
     );
   ```

   The count must be **0**, and manifest reconciliation must also pass.
   DBA issues `COMMIT` only after both checks; otherwise `ROLLBACK` and keep
   traffic closed. Recheck on the application read connection after commit,
   waiting for any read replicas to apply it. A check inside an uncommitted
   transaction is not an activation gate.
6. **Release owner / QA owner:** only after the committed deactivation gate,
   activate the approved old SHA behind closed traffic. Verify complete HTTP 200
   dashboard/feed responses and, using outsider as well as intended-recipient
   accounts, that **none of the manifest notices appears**. Exercise all
   supported live/archive/list/detail/download surfaces on that exact artifact.
   If any surface ignores inactive status or bypasses visibility, keep it
   inaccessible and **STOP**; feed-only absence is not sufficient. Inspect
   cached responses and invalidate any notice caches before reopening traffic.
7. **Product owner / Release owner:** sign the visibility evidence before traffic
   resumes. Keep affected notice creation/edit/reactivation disabled while the
   audience-unaware artifact is active. If the old UI/API cannot enforce that,
   block those routes for every account at the deployment layer; otherwise do
   not reopen traffic. Monitor attempts/errors; do not rely on an operator
   promise not to click Activate.
8. **DBA / Module owner:** reactivation is a separate approved operation only
   after an audience-aware forward fix passes recipient inclusion/exclusion
   tests. Use the saved manifest and original statuses; do not set all rows to
   1 or restore a stale whole-table snapshot. Keep originally inactive rows
   inactive and re-evaluate expiry, audience changes and intended recipients.

No deactivation SQL, old-code activation or production-like rollback rehearsal
has been executed by this documentation task. Release owners must record their
execution evidence; the earlier F-018 development smoke does **not** verify
old-code rollback safety.

## Release sign-off (required for every target)

| Evidence / action | Owner | Required result |
| --- | --- | --- |
| Candidate SHA, target DB identity and backup/restore plan recorded | Release owner / DBA | Confirmed |
| Production-like migration and recipient rehearsal | DBA / QA owner | PASS, no unexplained skips |
| Production schema migration, then backfill, before activation | DBA | Completed |
| Physical schema/index and migration-history checks | DBA | PASS |
| Backfill reconciliation for representative legacy notices | DBA / Module owner | PASS |
| Exact rehearsed code activated only after DB gate | Release owner | Confirmed |
| Authenticated dashboard/feed smoke and error-log check | QA owner / Release owner | PASS |
| Traffic/worker resumption and release approval | Release owner | Signed |
| Forward-fix-only rule accepted; notice migrations/data retained | Product owner / Release owner / DBA | Confirmed |
| If exceptional code rollback: approved manifest/export, committed deactivation, read-connection checks, all-surface visibility smoke and write/reactivation blocks before traffic | Product owner / Release owner / DBA / QA owner | PASS, or rollback prohibited |

## Local rehearsal evidence - 2026-10-13

Target: `testsargam6`, a development database. The operator authorized a local
rehearsal only. `artisan migrate:status` reported the schema migration as Ran
(batch 88) and the backfill as Ran (batch 89). No migrations were executed during
this task. A read-only `SELECT DATABASE(), VERSION()` confirmed `testsargam6`
on MySQL `8.0.46`.

Baseline HEAD: `36e6378857cb9b215d9c75424c11c1ed53813bef`, with uncommitted
application changes already present. This is a working-tree rehearsal, not a
verified deployable release artifact.

The section 4 PHPUnit selector passed: **18 tests, 102 assertions, no skips**.
It checked both migration records, all required columns and index definitions,
the audience-query execution, complete HTTP 200 dashboard and live/archive
notice-feed pages for E/F/S logins, and existing audience inclusion/exclusion
regressions. All fixture writes were wrapped in rolled-back transactions.
The new deployment test also passed PHP syntax and Pint checks.

The schema test establishes availability, not complete production backfill
correctness. Representative production-like legacy-data reconciliation still
requires DBA and Module owner approval.

**Production-like rehearsal, production migration execution and release
sign-off remain pending. F-018 is not closed by this local verification.**

F-055 policy is documented, but release execution remains pending. The local
`main` inspected for the rollback rule was
`6737e946800f1945e25cc0d4d4570c93b795be19`: its base feed requires
`active_inactive = 1`, while its staff predicate uses only `All` or
`Staff/Faculty`, without map membership. This source inspection motivates the
deactivation gate; it is not executed compatibility/visibility evidence for a
future rollback artifact.

## Release-owner confirmation - 2026-10-14

For candidate `5a2d06ae0fcd0818d2ceb4ce93ea775b6668db28`, Release Owner Nibhash
Mishra supplied confirmation at **10:15 +05:30**, after the candidate commit at
10:12:15 +05:30. The Release Owner reports PASS for the production-like
rehearsal, target schema/migration checks, backfill/data-integrity checks,
authenticated QA smoke tests and release readiness, and approved activation of
this exact candidate. This records the owner attestation; it does not claim
that production activation has already occurred.

## Supplemental development-database check - 2026-10-14

On `testsargam6` (MySQL `8.0.46`), the `NoticeDeploymentReadinessTest` selector
ran 4 tests with 57 assertions and 1 failure. The faculty dashboard fixture
returned HTTP 500 because `timetable.faculty_master` contains a value that is
not valid JSON, causing `JSON_CONTAINS` to fail. This is development-database
evidence only and is separate from the Release Owner's reported
production-like PASS; it does not establish a production failure or pass.
