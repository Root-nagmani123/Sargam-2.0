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

## 5. Run the migrations

```bash
php artisan migrate
```

This creates:
- `protocol_requests` — the master table (polymorphic link to the type table, status, current handler)
- `protocol_guest_houses`, `protocol_vehicle_passes`, `protocol_tickets` — type-specific fields
- `protocol_approval_logs` — the audit trail (action, actor, role, remarks, timestamp)

## 6. Roles

The routes are gated with `role:employee`, `role:protocol-staff`, and
`role:protocol-manager` — this uses the `role:` middleware from
**Spatie's `laravel-permission` package**. Check first whether that
package is already in your `composer.json`:

```bash
composer show spatie/laravel-permission
```

- **If it's installed already** (likely, if your other Sargam modules
  do role-based access), just run the seeder below to add the three
  new role rows.
- **If it's NOT installed**, either install it (`composer require spatie/laravel-permission`
  — test on a branch first, since your project already hit one PHP
  8.3 / dependency conflict with `openspout`), or swap the `role:...`
  middleware in `Modules/Protocol/routes/web.php` for whatever access
  control your app already uses (a custom middleware, a gate, or a
  simple role check inside each controller method). The workflow logic
  itself doesn't care how access is gated — only the route file
  references `role:`.

Run the seeder to create the three role rows if they don't already exist:

```bash
php artisan db:seed --class="Modules\Protocol\Database\Seeders\ProtocolDatabaseSeeder"
```

Then assign these roles to the relevant users the way you already do
elsewhere (via your existing user/role management screen, or `php artisan tinker`).

If your app uses different role names, just edit
`Modules/Protocol/Config/config.php` → `roles` array — nothing else needs
to change, the whole module reads role names from that config.

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
same `@role(...)` checks used here. The controllers, models, routes,
and migrations do not need any changes for either option — only the
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
