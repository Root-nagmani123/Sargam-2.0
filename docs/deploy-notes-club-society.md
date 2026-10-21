# Deploy notes — Club / Society module (`main_society`, PR #340)

Fourteen migrations in a **subfolder**, seven new screens under
Communications → Club/ Society, and one database version requirement.

---

## 0. Decisions

### Recorded

Relayed by the operator (devRishabh) on 2026-10-21, in the PR #340 fix session.

| # | Decision | Decided by | Effect |
|---|---|---|---|
| 1 | **The seven `*_sidebar_menu.php` migrations ship** as written; the menus and permissions are created by `migrate --path` (§1), not added by hand at `/sidebar/menus`. | DBA (Shailendra) | No code change. §3 no longer applies. |
| 2 | **Tie at the last seat: random draw.** *(Supersedes "keep the current rule", same day.)* On the result screen, before the result is published, a permission holder presses **Pick at random** on a tied post. The winner is drawn once with a CSPRNG (`random_int`) and saved in `election_drive_tie_pick` with who drew it and when, so it never changes on reload. If no draw is run, tied nominees fall back to the lower student record number. A tie can't be drawn after the result is published. | Club / Society module owner (relayed by the operator) | New table `election_drive_tie_pick` (migration `2026_09_07_000014`, **DDL for DBA approval**). New route `POST master/election-drive/result/{id}/draw-tie`, gated on `create_election_drive` and audit-logged as `club_society.election_drive.draw_tie`. View Result now opens once the election is live (pre-publish preview). |
| 3 | **No withdrawal once the result is published.** `withdrawNomination()` refuses with 422 and the Withdraw button is hidden while an active election on the drive has a published result. To withdraw, unpublish the result first. This keeps a published Officer Bearer from changing underneath the election. | Club / Society module owner | Code: `NominationDriveController::resultPublished()`; tests `test_no_withdrawal_after_the_result_is_published`, `test_withdrawal_works_again_once_the_result_is_unpublished`. |

### Still open

| # | Decision | Owner |
|---|---|---|
| 3 | The production database supports window functions (`ROW_NUMBER()`): **MySQL 8.0+ or MariaDB 10.2+**. Officer Bearers and View Result fail without them. Check with `SELECT VERSION();`. | DBA |
| 4 | What counts as a vote (today: one per distinct nominator with an accepted nomination) and what a role with no posts configured elects (today: nobody, and the result screen says so). | Club / Society module owner |
| 5 | Staging run: `migrate:status --path=database/migrations/society` shows all 14 as Ran. | DBA, with the Release / deploy owner |

## 1. The migrations are not on the default path

`database/migrations/society/` is **not** registered with Laravel, the same as
`database/migrations/mess/` and `database/migrations/FC/`. A bare
`php artisan migrate` does **not** run these, and `migrate:status` does not list them.
Every screen fails with "table doesn't exist" until they have run.

Run them explicitly, after the code is deployed and before the screens are used:

```bash
php artisan migrate:status --path=database/migrations/society   # expect 14 "Pending"
php artisan migrate --path=database/migrations/society
php artisan migrate:status --path=database/migrations/society   # expect 14 "Ran"
```

Order inside the folder is by file name and is safe as shipped: each table
migration runs before the menu migration for its screen.

The table migrations skip a table that already exists. If the DBA pre-creates
any of the ten tables, they are left untouched.

## 2. Permissions after deploy

Every Club / Society route is gated on its screen's permission. Super Admin
always passes; **everyone else gets 403 until a role is granted the permission**:

| Screen | Permission |
|---|---|
| Define Club/ Society | `define_club_society` |
| Define Club/ Society Role | `define_club_society_role` |
| Club/ Society Programme Mapping | `club_society_programme_mapping` |
| Club/ Society Role Programme Mapping | `club_society_role_programme_mapping` |
| Create Nomination Drive | `create_nomination_drive` |
| Create Election Drive | `create_election_drive` |
| Officer Bearers | `officer_bearers` |

OT / trainee logins must not be granted any of these.

## 3. If the menu migrations are not shipped (not the recorded decision — kept for reference)

Delete or skip the seven `2026_09_07_0000{02,04,06,08,10,12,13}_*_sidebar_menu.php`
files and, at `/sidebar/menus`, add each screen under Communications →
Club/ Society with the route `master/<screen>` and the permission from §2.
The routes check the permission **name**, so it must exist exactly as spelled
above.

## 4. Rollback

```bash
php artisan migrate:rollback --path=database/migrations/society
```

Then revert the code. Both rollback steps are guarded:

- **Table migrations refuse to drop a table that holds rows.** The rollback stops
  with "Refusing to roll back: table … has rows" *before* dropping anything in
  that migration. Back the data up and empty the tables with the DBA first, or
  leave the tables in place: without the code they are inert.
- **Menu migrations keep a permission that has been granted** to any role or user;
  only the menu row and an unused permission are removed.

## 5. Post-deploy checks

1. `migrate:status --path=database/migrations/society` shows all 14 as Ran.
2. An OT login gets **403** on `/master/election-drive` and on
   `POST /master/election-drive/publish/{id}`.
3. A holder of each permission opens its screen.
4. Watch `storage/logs/laravel.log` for 24 h for `doesn't exist`, and for the
   `club_society.*` audit lines every publish, toggle, withdraw, save and delete
   now writes.
