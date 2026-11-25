# Deploy notes — Employee role sync: Users screen ↔ Employee wizard (PR #354)

Branch `employee-role-sync-issue`, PR #354. No migration. One **data step** is required
(section 3): the PR ships a one-off reconcile command, `members:reconcile-role-mappings`,
which changes `employee_role_mapping`.

Last updated: 25 Nov 2026 (after PR #354 review round 11; F-003, F-006, F-014, F-015).

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

## 2. Notification routing changes (F-006)

`NotificationReceiverService` picks recipients by option name from `employee_role_mapping`,
not from Spatie roles:

| Notification | Options read | How (the command's "Read by" column) |
| --- | --- | --- |
| Discipline memo, memo/notice management | Super Admin, Training Induction Admin, Training-Induction | mapping **+** Spatie role of the same name (union) |
| Estate request approvers | Super Admin, Estate Admin | mapping + Spatie role (union) |
| Issue reports | Super Admin | mapping + Spatie role (union) |
| Estate HAC | HAC Person | mapping only |
| `getAdminUserId()` | Admin | mapping only, **first row only** |

So, after this PR:

| Action | Mapping-only options (HAC Person, Admin) | Union options |
| --- | --- | --- |
| Assign a role on Role & Permission › Users (employee login) | The login **starts** receiving those notifications (Admin: only if its row is the first one) | No change: the login already receives them through the Spatie role, provided the role name equals the option name |
| Remove a role on Role & Permission › Users | The login **stops** receiving them (before this PR it kept receiving them) | The login **stops** receiving them, unless another role in the same list still qualifies |
| Users-screen save of a login whose employee has more than one login | Unchanged: the mapping is not touched | Unchanged |
| `members:reconcile-role-mappings --apply` | Same as above, for every drifted login at once | Same as above |

When reading the dry run's **By option** table, only the "Logins losing it" column of a
union option changes routing. For a mapping-only option, both columns do. Before relying on
the union rows, the DBA confirms on staging that `roles.name` equals
`user_role_master.user_role_name` for Super Admin, Training Induction Admin,
Training-Induction and Estate Admin (F-015).

The logins at risk are those with **mapped-only** options: the mapping says they hold an
option, but the login doesn't hold the matching role. The command removes those rows, and
the login stops receiving that option's notifications. On `sargam_8nov` there were 6 such
rows (Training-Induction 4, Doctor 2). The command now prints this breakdown per option.

**Needs a Product owner decision before production**, in section 4: either accept the
change, or name the options whose mapped-only rows must be kept.

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

## 4. Sign-off (fill in before production)

| Item | Value | Owner | Date |
| --- | --- | --- | --- |
| Staging dry run: logins checked / skipped / to change / rows add / rows remove | *pending* | DBA | |
| Staging dry run: **By option** table (paste) | *pending* | DBA | |
| Second dry run after `--apply` on staging reports 0 | *pending* | DBA | |
| Decision: run `--apply` at deploy (section 3.2), **or** let records converge as employees are re-saved | *pending* | Product owner | |
| Decision: notification routing change in section 2 accepted, or the options to protect are listed | *pending* | Product owner | |

If the decision is **converge on save**: skip section 3.2 steps 2–4. Step 3 and the
show/print pages will disagree for unsaved employees until each is re-saved, and
notifications keep following the old mapping for them.

## 5. Rollback

- Code: revert the PR's commits. There is no migration to roll back.
- Data: a code revert does **not** restore `employee_role_mapping` rows written by `--apply`,
  by Users-screen saves, or by wizard saves. To undo `--apply`, use
  `reconcile-apply-prod.log`. Each line reads
  `user_credentials.pk N: add [..] remove [..]`. Delete the added rows and re-insert the
  removed ones for those logins.

## 6. Post-deploy checks

1. Open Step 3 for an employee whose roles were set on the Users screen. The ticks match the
   Users list and the show page.
2. On the Users screen, remove a role from an employee. Step 3 and the show page drop it.
   The employee no longer receives that option's notifications (section 2).
3. From a Step 3 page loaded before the deploy, save. A role granted on the Users screen in
   the meantime is kept, and a "was NOT removed" warning is shown.
4. After a wizard save that unticks a role, Role & Permission › Users shows the change on the
   next load.
5. For an employee with more than one login, a Users-screen role save leaves Step 3's
   options unchanged and says so in the flash message.
