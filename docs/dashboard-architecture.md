# Dashboard Architecture & Configuration

## Overview

The Sargam 2.0 dashboard implements a **role-based, configuration-driven card system**. Cards are NOT hard-coded into the dashboard view; instead, they are:

1. **Defined** in the `dashboard_cards` database table
2. **Assigned** to a role *on a header module* (Home, Setup, Communications, Academics — the
   `sidebar_categories` rows) via the `Assign Dashboard` page
3. **Rendered dynamically** on that module's dashboard (`/dashboard?category=<module id>`;
   plain `/dashboard` is Home) based on the user's role assignments for that module

This ensures that:
- Different roles can have different cards
- Each header module has its own dashboard, and one card can be on several modules
- Cards can be reordered, enabled/disabled per-role
- Cards can be created/edited via the UI without code changes
- The dashboard view only renders what's been configured

---

## Architecture Flow

```
┌─────────────────┐
│  Header/Module  │
│ (sidebar_       │
│  categories)    │
└────────┬────────┘
         │
         ▼
┌──────────────────────┐
│  Dashboard Config    │
│   (Assign Dashboard  │
│    Management Page)  │
└────────┬─────────────┘
         │
         ▼
┌────────────────────────────┐
│  Assigned Dashboard Cards  │
│  (Per Role + Module)       │
│  - role_dashboard_cards    │
│    (mapping table)         │
└────────┬───────────────────┘
         │
         ▼
┌────────────────────────────┐
│  Card Order + Visibility   │
│  + Per-Card Configuration  │
│  (sort_order, enabled)     │
└────────┬───────────────────┘
         │
         ▼
┌────────────────────────────┐
│    Actual Dashboard View   │
│  (Renders Based on Config) │
└────────────────────────────┘
```

---

## Database Schema

### 1. `dashboard_cards` Table

Stores all available dashboard cards.

```sql
CREATE TABLE dashboard_cards (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(255) UNIQUE,           -- Unique identifier (e.g., 'my_groups', 'discipline_marks_deducted')
    label VARCHAR(255),                -- Display name (e.g., 'My Groups')
    icon VARCHAR(255),                 -- Material Symbols icon name (e.g., 'groups')
    color_class VARCHAR(255),          -- CSS class for icon color (e.g., 'stat-icon-green')
    sort_order INT,                    -- Display order on dashboard
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Example Rows:**
```sql
INSERT INTO dashboard_cards VALUES
  (1, 'my_groups', 'My Groups', 'groups', 'stat-icon-green', 1, now(), now()),
  (2, 'total_students', 'Total Students', 'groups', 'stat-icon-blue', 2, now(), now()),
  (3, 'pending_feedback', 'Pending Feedback', 'assignment', 'stat-icon-rose', 3, now(), now());
```

### 2. `role_dashboard_cards` Table

Puts a card on one header module's dashboard for one role.

```sql
CREATE TABLE role_dashboard_cards (
    role_id BIGINT UNSIGNED,
    dashboard_card_id BIGINT UNSIGNED,
    sidebar_category_id BIGINT UNSIGNED NOT NULL DEFAULT <Home's id>,  -- the header module
    PRIMARY KEY (role_id, dashboard_card_id, sidebar_category_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (dashboard_card_id) REFERENCES dashboard_cards(id) ON DELETE CASCADE,
    FOREIGN KEY (sidebar_category_id) REFERENCES sidebar_categories(id) ON DELETE CASCADE
);
```

`sidebar_category_id` was added by `2026_11_13_000000_add_module_to_role_dashboard_cards`,
which put every existing assignment on Home. Its default is Home's id, so an insert that
leaves the column out (older migrations, seeders) still means Home.

**Example:** Role "Officer Trainee" (id=3) sees card 1 on Home and card 3 on Academics:
```sql
INSERT INTO role_dashboard_cards (role_id, dashboard_card_id, sidebar_category_id) VALUES
  (3, 1, 1),  -- "My Groups" on Home
  (3, 3, 4);  -- "Pending Feedback" on Academics
```

---

## Key Models

### `DashboardCard` Model
**File:** `app/Models/DashboardCard.php`

```php
class DashboardCard extends Model
{
    protected $fillable = ['key', 'label', 'icon', 'color_class', 'sort_order'];

    // Every role that has this card on ANY module's dashboard.
    public function roles() { ... }

    // Live header modules (active sidebar_categories), in header order.
    public static function modules(): Collection { ... }

    // The requested module id, or Home when missing / not scalar / not live.
    public static function resolveModule(mixed $requested): ?SidebarCategory { ... }

    // Cards any of $roleIds has on module $moduleId's dashboard.
    public function scopeAssignedTo($query, array $roleIds, int $moduleId) { ... }
}
```

---

## Key Controllers & Services

### 1. `RoleController`
**File:** `app/Http/Controllers/RoleController.php`

**Key Methods:**

#### `showDashboard(Request $request, $id)`
Displays the "Assign Dashboard" page for a role and one module (`?module=<id>`, default Home).
- Module pills at the top switch the module being configured
- Fetches all dashboard cards, and which are assigned to the role **on that module**
- Passes data to `assign-dashboard.blade.php`

#### `assignDashboardCard(Request $request, $id)`
API endpoint to enable/disable a card for a role on one module.
- Posts: `card_id`, `module`, `status` (1 = enable, 0 = disable)
- An unknown or non-live `module` is refused with 422 (never silently treated as Home)
- Only that module's row in `role_dashboard_cards` changes

#### `exportDashboardCards($id)`
Exports the role's card assignments for `?module=` (CSV/Excel/PDF/Print); the module is
named in the title, filename and filter line.

### 2. `RoleService`
**File:** `app/Services/RoleService.php`

**Key Methods:**

#### `dashboardCardRows($cards, $assignedCardIds): array`
Flattens all cards with their assignment status for a role.

```php
public function dashboardCardRows($cards, array $assignedCardIds): array
{
    $assigned = array_flip($assignedCardIds);
    $rows = [];
    
    foreach ($cards as $card) {
        $rows[] = [
            'id' => $card->id,
            'label' => $card->label,
            'icon' => $card->icon,
            'color_class' => $card->color_class,
            'sort_order' => $card->sort_order,
            'enabled' => isset($assigned[$card->id]),  // TRUE if assigned to role
        ];
    }
    return $rows;
}
```

#### `filterDashboardCardRows($rows, $search, $status): array`
Filters the card list for the UI (search + enabled/disabled status).

---

### 3. `UserController`
**File:** `app/Http/Controllers/Admin/UserController.php`

**Key Method:**

#### `dashboard(Request $request)` (lines 500-640)

This is where the actual dashboard is **built** for the logged-in user.

**Process:**

1. **Fetch user's roles:**
   ```php
   $userRoles = Auth::user()->roles ?? collect();
   $roleIds = $userRoles->pluck('id')->toArray();
   ```

2. **Fetch the cards enabled for this role on the requested module:**
   ```php
   // ?category= is also what selects the header tab and sidebar for the page.
   $dashboardModule = DashboardCard::resolveModule($request->query('category'));
   $enabledCards = DashboardCard::assignedTo($roleIds, $dashboardModule->id)
       ->orderBy('sort_order')  // one global order per card
       ->get();
   ```

3. **Build card data with counts and links:**
   ```php
   $cardDefinitions = [
       'my_groups' => ['count' => $myGroupsCount, 'link' => route('admin.dashboard.my-groups'), 'visible' => true],
       'pending_feedback' => ['count' => $feedbackCount, 'link' => route('feedback...'), 'visible' => $isOtUser],
       // ... many more cards
   ];
   ```

4. **Map cards to render data:**
   ```php
   $cardsToRender = $baseCards
       ->filter(fn ($c) => ! str_starts_with($c->key, 'widget_'))
       ->filter(function ($card) use ($cardDefinitions, $gatedCardKeys) {
           return ! in_array($card->key, $gatedCardKeys, true)
               || ($cardDefinitions[$card->key]['visible'] ?? true);
       })
       ->map(function ($card) use ($cardDefinitions, $cardCounts) {
           return [
               'key' => $card->key,
               'label' => $card->label,
               'icon' => $card->icon,
               'color_class' => $card->color_class,
               'link' => $cardDefinitions[$card->key]['link'] ?? null,
               'count' => $cardDefinitions[$card->key]['count'] ?? 0,
               'show_count' => $cardDefinitions[$card->key] !== null && array_key_exists('count', $def),
           ];
       })
       ->values();
   ```

5. **Pass to view:**
   ```php
   return view('admin.dashboard', compact('cardsToRender', 'enabledWidgetKeys', ...));
   ```

---

## Views

### 1. "Assign Dashboard" Management Page
**File:** `resources/views/roles-permissions/assign-dashboard.blade.php`

- **Route:** `GET /roles/{id}/dashboard` (name: `roles.dashboard`)
- **Purpose:** Manage which cards are assigned to a role
- **Features:**
  - Lists all dashboard cards in a data table
  - Checkboxes to enable/disable per-role
  - Filter by status (All / Enabled / Disabled)
  - Search by card name/icon
  - Export to CSV/Excel/PDF

**Key Section:**
```blade
@foreach($cardsToRender as $card)
    <div class="card stat-card">
        <i class="material-symbols-rounded">{{ $card['icon'] }}</i>
        <p>{{ $card['label'] }}</p>
        <input type="checkbox" ... data-card-id="{{ $card['id'] }}" />
    </div>
@endforeach
```

### 2. Actual Dashboard View
**File:** `resources/views/admin/dashboard.blade.php`

- **Route:** `GET /dashboard` (name: `admin.dashboard`)
- **Purpose:** Display the user's personalized dashboard
- **Dynamic Rendering:** Only renders cards assigned to the user's role

**Key Section:**
```blade
<div class="dashboard-stats-grid">
    @foreach($cardsToRender as $card)
        <div class="col">
            @if($card['link'])
                <a href="{{ $card['link'] }}">
            @endif
                <div class="card stat-card">
                    <i class="material-symbols-rounded">{{ $card['icon'] }}</i>
                    <p class="stat-title">{{ $card['label'] }}</p>
                    <p class="stat-value">{{ $card['count'] }}</p>
                </div>
            @if($card['link'])
                </a>
            @endif
        </div>
    @endforeach
</div>
```

---

## Routes

**Role-Based Dashboard Management:**
- `GET /roles/{id}/dashboard?module=<id>` → Show "Assign Dashboard" page for one module
- `POST /roles/{id}/dashboard` → Update card assignment on a module (AJAX; `card_id`, `module`, `status`)
- `GET /roles/{id}/dashboard/export?module=<id>` → Export that module's card assignments
- `POST /dashboard-cards` → Create new dashboard card; with `role_id` + `module` (the "Dashboard tab"
  field) it is also switched on for that role on that tab, in the same transaction
- `PUT /dashboard-cards/{id}` → Edit dashboard card (label/icon/colour/order are shared by every role);
  with `role_id`, `from_module` and a different `module` it moves that role's assignment to the new tab.
  An unknown tab is refused with 422 and nothing is saved
- `DELETE /dashboard-cards/{id}` → Delete dashboard card

**User Dashboard:**
- `GET /dashboard` → Home module's dashboard
- `GET /dashboard?category=<module id>` → that header module's dashboard

**Header tab landing** (`admin/layouts/master.blade.php`): a header tab whose module has at
least one card for one of the user's roles opens that module's dashboard; any other tab opens
its first sidebar menu. `nav-state.js` still prefers the last page visited in a tab.

---

## Cards Types

### 1. **Static Count Cards** (with `count` in definition)
- Show a number
- Example: "Total Students" (shows student count)
- Must have a `link` route

### 2. **Link-Only Cards** (without `count` in definition)
- Shows "Open" instead of a number
- Example: "Academic Timetable" (opens a calendar, nothing to count)
- Must have a `link` route

### 3. **Widget Cards** (key starts with `widget_`)
- Panels/widgets that appear below the stat grid
- Example: `widget_notices`, `widget_calendar`, `widget_house_performance`
- Controlled by `enabledWidgetKeys` array
- Rendered with `@if(in_array('widget_...', $enabledWidgetKeys))`

---

## Card Definitions

All available cards are defined in `UserController::dashboard()` method:

```php
$cardDefinitions = [
    'my_groups' => [
        'count' => $myGroupsCount,
        'link' => route('admin.dashboard.my-groups'),
        'visible' => true
    ],
    'discipline_marks_deducted' => [
        'count' => $disciplineMarksDeducted,
        'link' => route('memo.discipline.ot_marks'),
        'visible' => !$isSecurityRole && $isOtUser  // Gated by visibility
    ],
    // ... etc
];
```

**Card Definition Properties:**
- `count` - Number to display (optional; omit for link-only cards)
- `link` - Route URL to open when card is clicked
- `visible` - Boolean to gate the card (role/permission checks)

---

## Adding a New Dashboard Card

### Step 1: Create Database Migration
```php
// database/migrations/2026_11_13_000000_add_new_dashboard_card.php
return new class extends Migration {
    public function up(): void {
        $cardId = DB::table('dashboard_cards')->insertGetId([
            'key' => 'my_new_card',
            'label' => 'My New Card',
            'icon' => 'star',
            'color_class' => 'stat-icon-blue',
            'sort_order' => DB::table('dashboard_cards')->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Assign to a role on a module (leave sidebar_category_id out for Home)
        $roleId = DB::table('roles')->where('name', 'Officer Trainee')->value('id');
        DB::table('role_dashboard_cards')->insert([
            'role_id' => $roleId,
            'dashboard_card_id' => $cardId,
            'sidebar_category_id' => DB::table('sidebar_categories')->where('slug', 'academics')->value('id'),
        ]);
    }
};
```

### Step 2: Add Card Definition
In `UserController::dashboard()`, add to `$cardDefinitions`:
```php
'my_new_card' => [
    'count' => MyModel::where('...')->count(),
    'link' => route('admin.my.new.card'),
    'visible' => true,
],
```

### Step 3: Run Migration
```bash
php artisan migrate
```

### Step 4: Verify
- Go to Setup → Roles → [Role] → Dashboard, and pick the module
- Card should appear in the list
- Check the checkbox to enable for the role on that module
- Logout and login as that role to see the card on the dashboard

---

## Testing

### Key Test Files:
- `tests/Feature/DashboardCardVisibilityTest.php` - Tests card visibility per role
- `tests/Feature/DashboardCardKeyTest.php` - Tests card key consistency
- `tests/Feature/DashboardModuleAssignmentTest.php` - Per-module assignment, Home default, header landing, exports

### Running Tests:
```bash
php artisan test tests/Feature/DashboardCardVisibilityTest.php
php artisan test tests/Feature/DashboardCardKeyTest.php
```

---

## Important Notes

1. **Card Order:** Controlled by `dashboard_cards.sort_order` — one global order per card, the same on every module
2. **Modules:** A card shows on a module's dashboard only when assigned to the viewer's role *for that module*; panels (`widget_*`) follow the same rule
3. **Visibility Gates:** Some cards (OT-specific, faculty-specific) have `visible` flags that enforce permission checks beyond role assignment
4. **Widget Cards vs Stat Cards:** 
   - Stat cards = single tile with icon + number + link
   - Widget cards = panels/sections that appear below
5. **No Hard-Coding:** The dashboard view only iterates `$cardsToRender` and `$enabledWidgetKeys` — all cards must be in the database
6. **Sort Order Matters:** Cards render in `sort_order` sequence, so reordering is a 1-line update to the database

---

## Troubleshooting

### Cards not showing up?
1. Check `dashboard_cards` table has the card
2. Check `role_dashboard_cards` links the card to the user's role **on the module you are viewing** (`sidebar_category_id`)
3. Check card `visible` flag in `UserController::dashboard()` isn't gating it
4. Check `sort_order` is set

### New card not appearing after migration?
1. Run `php artisan migrate`
2. Clear cache: `php artisan cache:clear`
3. Check the card is assigned to at least one role

### Cards in wrong order?
- Update `dashboard_cards.sort_order` values in the database or via the Assign Dashboard page

