# Deploy notes — Employee wizard "Role Assignment" follows the Roles page (PR #343)

Branch `role-permission-feedback-october`, PR #343. Commits on top of `main` (merge-base
`84ecdc9d1`): `a028ef89c`, `658b3776e`, `dce26e3ea`, `1d1adbc4c`, plus the review fixes
`d27b57def` (round 1), `4ca5f02eb` (round 2), `91f317d12` and `ad4ec3a84` (these deploy
notes), and the round 4 fix for F-009 and F-010 (the commit that adds this line).

One data migration (insert-only) and code changes. Test on preprod before production.

Last updated: 26 Oct 2026 (after PR #343 review rounds 1–4).

---

## What this change is for

The Employee wizard's step 3, **Role Assignment**, should offer exactly the roles listed on
**Role & Permission › Roles**.

The two screens read different tables:

| Screen | Table |
| --- | --- |
| Role & Permission › Roles | `roles` (Spatie) |
| Employee › Add/Edit › Role Assignment | `user_role_master`, via `UserRoleMaster::getUserRoleList()` |

Problems this fixes:

1. **A role added on the Roles page didn't appear in Role Assignment**, and a renamed role's
   option kept the old name.
2. **Counts didn't match.** Role Assignment also listed old HR-only options with no matching
   role (e.g. *Staff*), and on preprod some roles were listed twice.

## 1. What changes for users

- **Role Assignment lists one option per role on the Roles page.** HR-only options (Staff,
  Admin, Internal Faculty, Guest Faculty, …) are no longer offered. Duplicate spellings of a
  role show once (the oldest row).
- **Existing HR-only mappings are kept.** On the edit form, a member who holds a hidden
  option sees *"Also assigned, not managed here (kept when you save): Staff"*. Saving does not
  remove it.
- **A member who holds a hidden option can be saved without ticking a role.** For example, a
  Staff-only employee shows nothing ticked in step 3; an administrator can still save other
  changes, and no role is granted. A member with no hidden option must still pick a role.
- **The module owner's decision on the two points above is pending**: remediation register row
  PR343-DECISION-1. Do not deploy to production until that row reads *Decided*.
- **Add Role** creates the Role Assignment option in the same transaction. **Edit Role**
  renames the option in place, so employees keep it, and a deactivated option stays
  deactivated.
- **Refused names, with a message:**
  - a name that matches an option employees already hold (e.g. "Staff" → *"held by 1,197
    employees"*), because the new role would be granted to all of them on their next save;
  - renaming a role onto the name of another existing option, because the role's own option
    would be left behind under the old name.
- **Role names are limited to 100 characters** (was 255): `user_role_master` name columns are
  `varchar(100)`.
- The save message now says an active hidden option *"is not managed from the Member wizard"*
  instead of calling it *"no longer an active role"*.

## 2. Files in this change

| File | Why |
| --- | --- |
| `app/Models/UserRoleMaster.php` | `getUserRoleList()` filter and one-per-role; `ensureOptionForRole()`, `renameOptionForRole()`, `mappedOptionConflict()`, `renameOptionConflict()` |
| `app/Http/Controllers/RoleController.php` | `store()` / `update()` keep the option in step and refuse unsafe names |
| `app/Http/Controllers/Admin/MemberController.php` | `withoutInactiveRoles()` message wording; `memberHoldsHiddenRoleOption()` relaxes Step 3's required role for a member who keeps a hidden option (`update()`, `updateValidateStep()`) |
| `resources/views/admin/member/edit_steps/step3.blade.php` | Lists held options that are not offered |
| `database/migrations/2026_10_21_000001_backfill_user_role_master_options_for_roles.php` | Option for every role created before this change |
| `tests/Feature/RoleOptionSyncTest.php`, `tests/Feature/MemberWizardPayrollAndStepsTest.php` | Tests only |

## 3. Prerequisite

The PR #319 migration `2026_09_10_000001_sync_user_role_master_with_roles.php` must already
have run on the server (it is on `main`). Check:

```bash
php artisan migrate:status --path=database/migrations/2026_09_10_000001_sync_user_role_master_with_roles.php
```

If it shows **Pending**, run it first, by path, before this release.

## 4. The backfill migration

`2026_10_21_000001_backfill_user_role_master_options_for_roles.php`

- Creates an option for every role that has **no** `user_role_master` row of any status.
- **Insert-only**: no existing row is changed. A role whose option was deactivated
  (`active_inactive = 0`) is skipped, so a deliberate deactivation isn't undone.
- A role name longer than 100 characters is skipped and logged, rather than failing the deploy.
- Writes with `DB::table()` directly, not through the model, so later model changes can't
  change what it inserts.
- Safe to run again. On `sargam_12102026` (a copy of live) it adds 0 rows.
- `down()` does nothing: the options belong to existing roles and employees may be mapped to
  them. See section 7 to remove one by hand.

## 5. Deployment order (test → preprod → production)

1. Back up `user_role_master`:
   ```bash
   mysqldump -u <user> -p <database> user_role_master > user_role_master_backup.sql
   ```
2. Deploy the code.
3. Run the migration **by path only**:
   ```bash
   php artisan migrate --path=database/migrations/2026_10_21_000001_backfill_user_role_master_options_for_roles.php
   ```
   **Do not run a plain `php artisan migrate`.** On the live copy it lists dozens of unrelated
   migrations (from 2025 onward) as pending, and they would all run.
4. Clear caches:
   ```bash
   php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear
   ```
   **Do not use `php artisan optimize:clear`.** It also runs `clear-compiled`, which deletes
   `bootstrap/cache/packages.php` and `services.php`. Both are tracked in git, so a server
   deployed from git is left with two deleted tracked files. If it was run by mistake, restore
   them with `git checkout -- bootstrap/cache`.
5. Run the checks in section 6. `git status` on the server should be clean.

## 6. Checks after deploy

1. **Employee › Edit › Role Assignment** lists the same roles as **Role & Permission › Roles**,
   and the counts match.
2. **Add Role** "Test Role Oct" → it appears in Role Assignment straight away.
3. Rename it to "Test Role Oct 2" → Role Assignment shows the new name; an employee who had it
   still has it ticked.
4. **Add Role** "Staff" → refused with the employee count. No role is created.
5. Rename any role to the name of an existing option (e.g. "Guest Faculty") → refused.
6. Open a Staff-tagged employee → step 3 shows *"Also assigned, not managed here … Staff"*.
   Save → their Staff row is still in `employee_role_mapping`.
7. Open a Staff-only employee → step 3 has nothing ticked and shows Staff as "Also assigned".
   Change another field and save without ticking anything → it saves, Staff is kept, and on
   **Role & Permission › Users** the employee has no new role.
8. Delete "Test Role Oct 2" afterwards.

SQL check for roles missing from Role Assignment (should return 0 rows):

```sql
SELECT r.id, r.name FROM roles r
WHERE NOT EXISTS (
  SELECT 1 FROM user_role_master u
  WHERE (u.active_inactive = 1 OR u.active_inactive IS NULL)
    AND LOWER(TRIM(REGEXP_REPLACE(u.user_role_display_name, '[ _-]+', ' ')))
      = LOWER(TRIM(REGEXP_REPLACE(r.name, '[ _-]+', ' ')))
);
```

A row returned is either a role whose option was deactivated on purpose (expected), or one the
backfill should have covered (rerun it).

## 7. Rollback

- Revert the PR's commits and redeploy, then clear caches as in section 5 step 4 (not
  `optimize:clear`).
- The migration's `down()` does nothing. Any row it inserted can stay: it is an option for a
  role that exists. To remove one anyway, the DBA first checks
  `SELECT COUNT(*) FROM employee_role_mapping WHERE user_role_master_pk = <pk>` returns 0, then
  deletes it by pk.

## 8. Known effects

- **"Admin" can't be assigned from the wizard any more.** `NotificationReceiverService`
  (`getAdminUserId()`, line 132) finds the notification recipient by the "Admin" option. The
  existing Admin mappings keep working; new ones can't be added from this screen.
- **Super Admin is still listed** because it's on the Roles page. As before, the wizard won't
  grant it and shows a warning.

## Change log

| Date | What happened |
| --- | --- |
| 12 Oct 2026 | Ran the PR #319 sync migration locally on `sargam_12102026` (batch 68); it added 17 options. |
| 13 Oct 2026 | Confirmed the role migrations in PR #319 and wrote the deployment steps. |
| 15 Oct 2026 | Found why Staff appears in Role Assignment. Applied the `getUserRoleList()` filter. Committed as `a028ef89c`. |
| 16 Oct 2026 | New and renamed roles keep their Role Assignment option (`RoleController` store/update). Committed as `658b3776e`. |
| 21 Oct 2026 | Preprod count mismatch: duplicate options shown once (`dce26e3ea`); backfill migration `2026_10_21_000001` for roles created before the fix (`1d1adbc4c`). |
| 26 Oct 2026 | PR #343 review round 1 fixes (`d27b57def`): refuse role names employees already hold; options matched at any status; held hidden options listed on the edit form; message wording; migration inserts inline. |
| 26 Oct 2026 | PR #343 review round 2 fix (`4ca5f02eb`): refuse renaming a role onto another existing option's name. |
| 26 Oct 2026 | Notes brought up to date with the review fixes and committed to the repository (`ad4ec3a84`, review finding F-007). |
| 26 Oct 2026 | PR #343 review round 4 fixes. F-009: a member who holds a hidden option can be saved without ticking, so granting, a role. F-010: decision marked pending; `optimize:clear` replaced with targeted cache clears; commit list updated. |
