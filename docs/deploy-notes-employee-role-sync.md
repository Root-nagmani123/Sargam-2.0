# Deploy notes — Employee role sync: Users screen ↔ Employee wizard (PR #354)

Branch `employee-role-sync-issue`, PR #354. No migration. One **data step** is required
(section 3): the PR ships a one-off reconcile command, `members:reconcile-role-mappings`,
which changes `employee_role_mapping`.

Last updated: 26 Nov 2026 (after PR #354 review round 14; F-003, F-006, F-017, F-018).

---

## 1. Why there is a data step

Before this PR, roles saved on **Role & Permission › Users** went only into Spatie
(`model_has_roles`). The employee show/print pages, and several notification lookups, read
`employee_role_mapping` instead. After the deploy:

- Step 3 of the Employee wizard ticks the roles the login **holds** (Spatie).
- The show/print pages and notifications still read `employee_role_mapping`.

Until each employee is re-saved, those two disagree for every employee whose roles were
changed on the Users screen. On the local copy `sargam_8nov`, that was **1,214 of 1,229**
employee logins. The reconcile command makes `employee_role_mapping` match the held roles
in one pass.

## 2. Notification routing does not change (F-006)

Product owner position (relayed on the PR, 25 Nov 2026): notification routing must remain
unchanged. The code now enforces that.

`NotificationReceiverService` picks recipients by option name from `employee_role_mapping`:

| Notification | Options read | How |
| --- | --- | --- |
| Discipline memo, memo/notice management | Super Admin, Training Induction Admin, Training-Induction | mapping **+** Spatie role of the same name (union) |
| Estate request approvers | Super Admin, Estate Admin | union |
| Issue reports | Super Admin | union |
| Estate HAC | HAC Person | mapping only |
| `getAdminUserId()` | Admin | mapping only, first row only |

These six options are the **routing options** (`EmployeeRoleMapping::routingOptionNames()`).
Neither Role & Permission › Users nor `members:reconcile-role-mappings` ever adds or removes
a mapping row for a routing option. Their rows stay exactly as the Member wizard (or the
Users create/edit form) last wrote them, as before this PR. So:

| Action | Routing options | Every other offered option |
| --- | --- | --- |
| Assign or remove a role on Role & Permission › Users (employee login) | Mapping unchanged, so **notifications unchanged** | Mapping mirrored to the held roles |
| `members:reconcile-role-mappings --apply` | Mapping unchanged | Mapping mirrored |
| Member wizard save | Written from the ticks the administrator saves (as before this PR) | Same |

Trade-off, accepted by this design: for a routing option, Step 3 (which ticks held Spatie
roles) and the show/print pages (which read the mapping) can still differ for an employee
until an administrator saves that employee in the Member wizard.

**Open point for the Product owner:** a Member wizard save writes the routing options' rows
from the ticks on screen, and Step 3 now ticks held Spatie roles. Saving an employee "as
displayed" can therefore add or remove a routing row (for example a HAC Person row for a login
that holds the HAC Person role but had no row). Confirm in section 4 that a wizard save is
an explicit administrator choice and may change routing, or ask for this to change too.

The dry run's **By option** table now lists only non-routing options, so no row in it has a
notification effect.

## 3. Deploy steps

### 3.1 On staging (a copy of live), before production

```bash
php artisan members:reconcile-role-mappings          # dry run, writes nothing
php artisan members:reconcile-role-mappings -v       # same, plus one line per login
```

Record both tables from the output in section 4: the totals, and **By option**. Keep the
`-v` output as the record of what will change. Then:

```bash
php artisan members:reconcile-role-mappings --apply -v > reconcile-apply-staging.log
php artisan members:reconcile-role-mappings          # must report 0 logins to change
```

Spot-check one changed employee on staging: Step 3 ticks, the show page and the Users list
show the same roles.

### 3.2 On production

Only after section 4 is filled in and the decision allows it:

1. Deploy the code.
2. `php artisan members:reconcile-role-mappings -v > reconcile-dryrun-prod.log`, and compare
   its totals with the staging ones.
3. `php artisan members:reconcile-role-mappings --apply -v > reconcile-apply-prod.log`
4. `php artisan members:reconcile-role-mappings`, which must report 0 logins to change.
5. Keep both log files with the release record. They are the rollback record (section 5).

Notes:

- Logins whose employee has more than one login are **skipped**, deliberately (F-011). The
  wizard is the only writer for them.
- Options Step 3 does not offer (HR-only tags such as Staff, inactive options) are never
  added or removed.
- The command changes only `employee_role_mapping`. Spatie roles and permissions are read,
  never written.
- Each login is written in its own transaction, so it is safe to re-run after an
  interruption.
- With `--apply`, each login's row is locked and its roles are read **after** the lock
  (F-017). A Users-screen role save made while the command runs is never undone, so role
  edits do not need to be frozen during the run. The `-v` line for each login is printed
  after its write and lists the rows actually `added` / `removed`.
- If step 4's second dry run reports any login, re-run `--apply --user=<pk>` for it.

## 4. Sign-off (fill in before production)

| Item | Value | Owner | Date |
| --- | --- | --- | --- |
| Staging dry run: logins checked / skipped / to change / rows add / rows remove | *pending* | DBA | |
| Staging dry run: **By option** table (paste) | *pending* | DBA | |
| Second dry run after `--apply` on staging reports 0 | *pending* | DBA | |
| Decision: run `--apply` at deploy (section 3.2), **or** let records converge as employees are re-saved | *pending* | Product owner | |
| Confirm: notification routing stays unchanged (section 2), as implemented | *pending* | Product owner | |
| Decision: a Member wizard save may change routing rows (section 2, open point) | *pending* | Product owner | |

If the decision is **converge on save**: skip section 3.2 steps 2–4. Step 3 and the
show/print pages will disagree for unsaved employees until each is re-saved. Notifications
are unaffected either way.

## 5. Rollback

- Code: revert the PR's commits. There is no migration to roll back.
- Data: a code revert does **not** restore `employee_role_mapping` rows written by `--apply`,
  by Users-screen saves, or by wizard saves. To undo `--apply`, use
  `reconcile-apply-prod.log`. Each line reads
  `user_credentials.pk N: added [..] removed [..]` and is printed after that login's
  transaction commits, so it lists only rows that were really written. Delete the added
  rows and re-insert the removed ones for those logins.

## 6. Post-deploy checks

1. Open Step 3 for an employee whose roles were set on the Users screen. The ticks match the
   Users list and the show page.
2. On the Users screen, remove a non-routing role (e.g. Doctor) from an employee. Step 3 and
   the show page drop it. Remove HAC Person from an employee who has it: Step 3 drops it, but
   the show page keeps it and the employee still receives HAC notifications (section 2).
3. From a Step 3 page loaded before the deploy, save. A role granted on the Users screen in
   the meantime is kept, and a "was NOT removed" warning is shown.
4. After a wizard save that unticks a role, Role & Permission › Users shows the change on the
   next load.
5. For an employee with more than one login, a Users-screen role save leaves Step 3's
   options unchanged and says so in the flash message.
6. While a Member wizard save of an employee is in progress, a Users-screen role grant for
   the same login waits for it and is then kept (F-018). This needs two sessions at once;
   it is the overlapping-save probe the review asks a human to run.
