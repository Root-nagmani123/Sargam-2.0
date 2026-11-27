# Guest TA Claim module for Sargam 2.0

A Laravel module (nwidart/laravel-modules layout) with Bootstrap 5 views, matching the clickable prototype.

**Workflow:** TA & Training selects course and dates → reviews the faculty-wise guest timetable and edits sessions → fills the claim form → forwards it → the course coordinator approves or returns it with a remark → Accounts opens the request details and marks it paid (remark and amount optional) → TA & Training sees it as *Approved & paid* and generates the claim report. Every list screen has Print and Export CSV.

## Requirements

- PHP 8.1+ and Laravel 9 or later (the module uses PHP enums and enum casts)
- nwidart/laravel-modules, with the root `composer.json` mapping `"Modules\\": "Modules/"` (Sargam’s existing setup)
- MariaDB 10.6+ or MySQL 8
- barryvdh/laravel-dompdf (already in Sargam) adds a PDF download to the claim report

## Install

1. Copy the `GuestTaClaim` folder into `Modules/`, so you have `Modules/GuestTaClaim/Providers/...`.
2. Run `composer dump-autoload`. There should be no “does not comply with psr-4” lines for GuestTaClaim.
3. Enable the module: `php artisan module:enable GuestTaClaim`, then `php artisan optimize:clear`.
4. Create the tables, either:
   - `php artisan migrate`, or
   - run `Database/sql/guest_ta_claim_mariadb.sql` directly on MariaDB (don’t do both).
5. Give the first administrator all permissions:
   `php artisan module:seed GuestTaClaim` (grants user id 1; set `GTC_SEED_USER_IDS` for others),
   or set `GTC_SUPER_ADMIN_IDS` in `.env`.
6. Open `/guest-ta-claim` and use **Assign permission** to map TA & Training, course coordinators and Accounts staff.

## Configuration (`.env`)

| Key | Default | Purpose |
|---|---|---|
| `GTC_TIMETABLE_PROVIDER` | `demo` | `demo` uses sample data; `database` reads the real Sargam timetable |
| `GTC_LAYOUT` | `guesttaclaim::layouts.app` | Layout to extend. Use your Sargam layout once it has `@yield('content')`, `@stack('styles')` and `@stack('scripts')` |
| `GTC_SUPER_ADMIN_IDS` | `1` | Comma-separated user ids with every permission |
| `GTC_RESTRICT_TO_CC` | `true` | Only the course’s coordinator can approve. Set `false` while testing with the demo data |
| `GTC_ROUTE_PREFIX` | `guest-ta-claim` | URL prefix |
| `GTC_USER_MODEL` | `App\Models\User` | Host user model |
| `GTC_USER_DESIGNATION_COLUMN` | empty | Users column shown under names on the permission screen |
| `GTC_DISK` | `local` | Storage disk for tickets and bills (keep it private) |

Publish the config to change rates, travel modes and table mapping:
`php artisan vendor:publish --tag=guest-ta-claim-config`

**Honorarium and DA rates in `config.php` are placeholders.** Replace them with the Academy’s notified rates before use.

## Connecting to the Sargam timetable

Set `GTC_TIMETABLE_PROVIDER=database` and map your tables in `config('guest-ta-claim.timetable')`:

- `courses`: id, name, coordinator user id (the coordinator is copied onto each claim when it is created)
- `sessions`: id, course id, faculty id, date, start, end, topic, venue
- `faculty`: id, name, designation, organisation, category, city, plus the column and value that mark guest faculty

If your data needs joins the mapping can’t express, write your own class implementing `Modules\GuestTaClaim\Contracts\TimetableProvider` and bind it in the service provider.

## Permissions

| Key | Label | Typical role |
|---|---|---|
| `claim.create` | Create claim | TA & Training |
| `claim.edit` | Edit session details | TA & Training |
| `claim.forward` | Forward to course coordinator | TA & Training (senior) |
| `claim.report` | Generate report | TA & Training |
| `cc.approve` | Approve or return claims | Course coordinator |
| `acc.view` | View payment requests | Accounts |
| `acc.pay` | Mark claim as paid | Accounts officer |
| `export` | Print and export | Everyone who needs it |
| `perm.manage` | Assign permission | Administrator |

Checked on routes with `gtc.can:<key>` middleware (`|` means any of) and in views with `@can('gtc.<key>')`.

## Status flow

`draft → pending_cc → pending_accounts → paid`, with `pending_cc → returned → pending_cc`. All changes go through `ClaimWorkflowService` inside a transaction with a row lock, and every change writes a line to `gtc_claim_histories`. Claim numbers are `GTA/<year>/<0001>` from `gtc_claim_sequences`.

## Structure

Same layout as Sargam’s other modules (namespace `Modules\GuestTaClaim\` maps to the module folder):

```
Modules/GuestTaClaim/
├── module.json, composer.json, README.md
├── Config/config.php
├── Routes/web.php
├── Contracts/TimetableProvider.php
├── Enums/ClaimStatus.php, Permission.php
├── Http/Controllers/   Dashboard, Claim, Approval, Payment, Report, Export, Attachment, Permission
├── Http/Middleware/EnsureGtcPermission.php
├── Http/Requests/      Search, Prepare, StoreClaim, Decision, Pay, UpdatePermissions, SessionRules
├── Models/             Claim, ClaimSession, ClaimHistory, ClaimAttachment, UserPermission
├── Providers/GuestTaClaimServiceProvider.php
├── Services/           ClaimWorkflowService, ClaimCalculator, ClaimNumberGenerator, PermissionService, CsvExporter
├── Support/            DemoTimetableProvider, DatabaseTimetableProvider, Format
├── View/Composers/SidebarComposer.php
├── Database/Migrations, Database/Seeders, Database/sql (MariaDB)
├── Resources/views/    layouts, partials, dashboard, claims, approvals, payments, reports, permissions
├── Resources/assets/   css/gtc.css, js/gtc.js (inlined by the module layout, no build step)
└── Tests/Unit/ClaimCalculatorTest.php
```

## Routes (prefix `/guest-ta-claim`, names `guest-ta-claim.*`)

| Method | URI | Name |
|---|---|---|
| GET | `/` | dashboard |
| GET | `claims/create` | claims.create (find timetable) |
| POST | `claims/prepare` | claims.prepare |
| GET | `claims/form` | claims.form |
| POST | `claims` | claims.store |
| GET | `claims` | claims.index |
| GET | `claims/{claim}` | claims.show |
| GET, PUT, DELETE | `claims/{claim}/edit`, `claims/{claim}` | claims.edit, claims.update, claims.destroy (drafts only) |
| POST | `claims/{claim}/forward` | claims.forward |
| GET | `approvals` | approvals.index |
| POST | `claims/{claim}/approve`, `claims/{claim}/return` | claims.approve, claims.return |
| GET | `payments` | payments.index |
| POST | `claims/{claim}/pay` | claims.pay |
| GET | `reports` | reports.index |
| GET | `claims/{claim}/report` | claims.report (`?download=html` or `pdf`) |
| GET | `export/{type}` | export (claims, approvals, payments, reports, timetable, permissions, claim) |
| GET | `attachments/{attachment}` | attachments.download |
| GET, PUT | `permissions`, `permissions/{userId}` | permissions.index, permissions.update |

## Before going live

- Replace placeholder rates; confirm the DA and conveyance rules with Accounts.
- Keep attachments on a private disk; they are only downloadable by users who can see the claim.
- Exported CSVs are protected against spreadsheet formula injection.
- Add AI-assistance disclosure in project documentation as per programme guidelines.
