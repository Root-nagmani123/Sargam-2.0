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

The same seeder creates the empty `Protocol Approver` role. Assign it to the people who
should appear in the "Recommend to" list; until someone holds it, staff can only approve directly.

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

## 7. Layout

Every screen extends the app's `admin.layouts.master` and appears in the app sidebar
through the menus added at `/sidebar/menus`. `Resources/views/layouts/app.blade.php` and
`partials/sidebar.blade.php` are left over from the standalone version; no route renders them.

## 8. Employee identity

A signed-in actor (requester, staff, manager, approver) is identified with
`User::resolveEmployeePk()`: `employee_master.pk` first, else a unique `pk_old`
(`ProtocolDirectory::employeePkFor()`). A login that resolves to no single employee is
refused (422 when raising a request, 403 when deciding).

## 9. Routes reference

| Route name | URL | Gate |
| --- | --- | --- |
| `protocol.dashboard` | `/protocol` | `protocol.my_requests` |
| `protocol.requests.create` | `/protocol/new` | `protocol.my_requests` |
| `protocol.requests.store` | `POST /protocol/requests` | `protocol.my_requests` |
| `protocol.requests.my` | `/protocol/my-requests` | `protocol.my_requests` |
| `protocol.requests.show` / `edit` / `update` | `/protocol/requests/{id}` | `protocol.my_requests`, own requests only |
| `protocol.requests.all` | `/protocol/all-requests` | `protocol.protocol_requests` |
| `protocol.approval.queue` / `review` / `decide` | `/protocol/approval/queue/...` | `protocol.protocol_requests` |
| `protocol.recommended-booking` | `/protocol/recommended_booking` | `protocol.recommended_booking` |
| `protocol.manager.queue` / `review` / `decide` | `/protocol/manager/queue/...` | signed in; only the person the request is recommended to |
| `protocol.driver-master.*` | `/protocol/driver-master` | `protocol.driver_master` |
| `protocol.vehicle-master.*` | `/protocol/vehicle-master` | `protocol.vehicle_master` |
| `protocol.permissions` | `/protocol/permissions` | `protocol.permissions` |

`php artisan route:list --path=protocol` is the source of truth.

## 10. Not built yet

- **Notifications** — no email/SMS is sent. The hook point is each `logs()->create(...)`
  in `ProtocolRequest.php` (approveDirectly / recommendTo / approveByManager / rejectByManager).
- **Vehicle categories and types** — their routes are commented out in `routes/web.php`,
  and the DBA schema has no tables for them.
