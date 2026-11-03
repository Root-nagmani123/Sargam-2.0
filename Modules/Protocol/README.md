# Protocol Module

**No external package required.** This module is a plain, self-contained
Laravel module (folder-based, same pattern as your other Sargam 2.0
modules) for **Guest House**, **Vehicle Pass**, and **Ticket** requests,
with a two-step approval workflow. It does NOT depend on
`nwidart/laravel-modules` — everything is wired up via one service
provider registered directly in `config/app.php` (Laravel 9 style).

```
Employee raises request
        │
        ▼
Protocol Staff reviews  ──► Approve ──► Done (logged)
        │
        └────────────────► Recommend to Manager/Staff
                                   │
                                   ▼
                          Manager reviews ──► Approve / Reject (logged)
```

Every step (raise / recommend / approve / reject) is written to
`protocol_approval_logs`, which is what powers the **History Log** —
who acted, in what role, and on what date.

---

## 1. Add the Modules namespace to Composer autoloading

Open `composer.json` in your project root, find `"autoload" -> "psr-4"`,
and add `Modules\\`:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Modules\\": "Modules/"
    }
}
```

## 2. Drop in the module

Copy the `Modules/Protocol` folder from this zip into your project's
`Modules/` directory, then:

```bash
composer dump-autoload
```

## 3. Register the service provider (Laravel 9 style)

Open `config/app.php`, find the `'providers'` array, and add this line
alongside your other application providers:

```php
Modules\Protocol\Providers\ProtocolServiceProvider::class,
```

## 4. Publish the config (optional)

```bash
php artisan vendor:publish --tag=config
```

Copy `Modules/Protocol/Resources/assets/sass/protocol.css` to
`public/modules/protocol/css/protocol.css` (or pull its contents into
your existing Sass/Vite build — it's plain CSS with a handful of
custom properties at the top, no preprocessing required).

## 5. Database tables

The module ships no migrations. The DBA creates and maintains the Protocol
tables on every environment (production gets them before the release tag is
deployed). The code expects these 11 tables:

- `protocol_request_batches`, `protocol_request_guests` — one combined-form submission and its guests
- `protocol_requests` — the master table (polymorphic link to the type table, status, current handler)
- `protocol_guest_houses`, `protocol_vehicle_passes`, `protocol_vehicle_legs`, `protocol_tickets`, `protocol_ticket_journeys` — type-specific fields
- `protocol_approval_logs` — the audit trail (action, actor, role, remarks, timestamp)
- `protocol_driver_masters`, `protocol_vehicle_masters` — admin masters

Permission rows are not tables: every route is gated with `can:protocol.*`, and a
missing row denies everyone. Run this once on each environment as a deploy step
(it creates rows only, grants nothing, and is safe to re-run):

```bash
php artisan db:seed --class="Modules\Protocol\Database\Seeders\ProtocolPermissionsSeeder"
```

Sidebar menus are added from `/sidebar/menus`.

## 6. Roles and access

Access is role-based. Roles carry Spatie permissions (assigned at `/protocol/permissions`
or the app's role screen), and every route group in `routes/web.php` is gated with
`can:<permission>`:

| Permission | Gives |
| --- | --- |
| `protocol.my_requests` | Raise requests; view and edit **own** requests only (`ProtocolRequestAccess`) |
| `protocol.protocol_requests` | All Requests list, the staff queue, review and decide |
| `protocol.guest_house_booking` / `vehicle_booking` / `ticket_booking` | Which request types appear in the All Requests list |
| `protocol.all_booking` | Every request type in the All Requests list |
| `protocol.recommended_booking` | Recommended Bookings list |
| `Protocol Approver` role | Appears in the "Recommend to" list; decides requests recommended to them |
| `protocol.driver_master` / `vehicle_master` / `permissions` | The master and permission screens |

**Decision scope (PR #348 F-002):** whoever holds
`protocol.protocol_requests` may review and decide any pending request from the staff
queue, of any booking type, including one they raised themselves. The booking-type
permissions narrow the All Requests list only; they are not a limit on deciding.
Who may decide is controlled by which roles carry `protocol.protocol_requests`. Narrowing
this (per-type deciding, or blocking self-approval) is a policy change to make in code
deliberately, not a bug fix.

`ProtocolRolesSeeder` and `config('protocol.roles')` still exist for the sidebar partial's
`@role(...)` checks; the routes themselves do not use `role:` middleware.

## 7. Integrating into your existing UI (important)

`Resources/views/layouts/app.blade.php` is a **complete standalone
layout** (its own `<html>`, sidebar, topbar) so the module works
out-of-the-box on its own. Since you're integrating this into existing
software, you have two options:

**Option A — quick:** leave it as-is. The module will render as its
own full page with its own sidebar, reachable at `/protocol`. Good for
a first integration pass / demo to stakeholders.

**Option B — proper integration (recommended):** open every Blade
file under `Resources/views/` and change:
```blade
@extends('protocol::layouts.app')
```
to your existing app's master layout, e.g.:
```blade
@extends('layouts.master')
```
Then delete `Resources/views/partials/sidebar.blade.php`'s markup and
instead add the module's nav links (Dashboard, New Request, Approval
Queue, etc.) into your existing sidebar/menu partial, guarded by the
same `@role(...)` checks used here. The controllers, models and routes
do not need any changes for either option — only the
Blade views' `@extends` line and the nav menu.

## 8. Employee model relation

The models reference the logged-in user via
`config('auth.providers.users.model')`, so they automatically pick up
whatever `User` model your app already uses — no changes needed unless
your user table's primary key isn't named `id`.

If you track `department` on your `users` table under a different
column name, update this one line in
`ProtocolRequestController@createRequest`:
```php
'employee_department' => Auth::user()->department ?? null,
```

## 9. Routes reference

| Route name                      | URL                                   | Role            |
|---------------------------------|---------------------------------------|-----------------|
| `protocol.dashboard`            | `/protocol`                           | any             |
| `protocol.requests.create`      | `/protocol/new`                       | employee        |
| `protocol.requests.my`          | `/protocol/my-requests`               | employee        |
| `protocol.guest-house.store`    | `POST /protocol/guest-house`          | employee        |
| `protocol.vehicle-pass.store`   | `POST /protocol/vehicle-pass`         | employee        |
| `protocol.ticket.store`         | `POST /protocol/ticket`               | employee        |
| `protocol.approval.queue`       | `/protocol/approval/queue`            | protocol-staff  |
| `protocol.approval.review`      | `/protocol/approval/queue/{id}`       | protocol-staff  |
| `protocol.approval.decide`      | `POST .../{id}/decide`                | protocol-staff  |
| `protocol.manager.queue`        | `/protocol/manager/queue`             | protocol-manager|
| `protocol.manager.review`       | `/protocol/manager/queue/{id}`        | protocol-manager|
| `protocol.manager.decide`       | `POST .../{id}/decide`                | protocol-manager|
| `protocol.history.index`        | `/protocol/history`                   | any             |  
| `protocol.requests.show`        | `/protocol/requests/{id}`             | any (shared)    |

## 10. What's intentionally left for you to plug in

- **Guest House / Vehicle master data** — the dropdowns in the create
  forms are hardcoded sample options (`Yamuna Guest House`, `Innova`,
  etc.). Swap these for a real `guest_house_masters` /
  `vehicle_masters` table + dropdown if you want admin-managed lists,
  using the `guest_house_master_id` / `vehicle_master_id` nullable
  columns already present on the migrations.
- **Notifications** — no email/SMS is wired up. The obvious hook point
  is right after each `->logs()->create(...)` call inside
  `ProtocolRequest.php` (approveDirectly / recommendTo / approveByManager
  / rejectByManager) — fire a Laravel Notification there.
- **Manager selection list** — `ApprovalController@review` currently
  pulls everyone with the `protocol-manager` role. If recommendation
  should only go to a specific person's actual line manager, replace
  that query with your existing org-hierarchy lookup.
