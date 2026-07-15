# Admin Master Layout — `master.blade.php`

`resources/views/admin/layouts/master.blade.php` is the shell every admin page
extends via `@extends('admin.layouts.master')`. It owns the `<head>`, the app
chrome (preloader, header, sidebar), the tab-pane content area, and the global
script/style stacks.

---

## Page skeleton

```
<html data-bs-theme="light" data-layout="vertical">
  <head>
    (inline theme-restore script)
    @include('admin.layouts.pre_header')   ← CSS incl. sargam-app.css (loaded LAST)
    <title>@yield('title') … Sargam 2.0</title>
    @section('css') … inline base styles … @show
    @stack('styles')                       ← page-specific <style> / <link>
  </head>
  <body class="has-dynamic-sidebar …">
    #sargamLoader                           ← preloader
    #main-wrapper
      @include('admin.layouts.header_new')
      .page-wrapper
        @include('admin.layouts.sidebar_new')
        .body-wrapper > main#main-content
          .tab-content  ← 5 tab panes (see below)
    @include('admin.layouts.footer')
    (global scripts, sidebar bootstrap, theme lock)
    @stack('scripts')   +   @yield('scripts')   +   @yield('script')
  </body>
</html>
```

---

## Tab-pane content resolution (important)

The main area holds **five** tab panes, one per top-nav tab:

| Tab hash | Pane id | `@section` name |
|---|---|---|
| `#home` | `home` | `content` |
| `#tab-setup` | `tab-setup` | `setup_content` |
| `#tab-communications` | `tab-communications` | `communications_content` |
| `#tab-academics` | `tab-academics` | `academics_content` |
| `#tab-material-management` | `tab-material-management` | `material_management_content` |

**A page's content renders into exactly one pane — the tab its sidebar `<li>`
resolves to** — regardless of which section name the view used. The `@php` block
at the top:

1. Resolves the active tab from the matched menu's own category
   (`SidebarNavResolver`).
2. Detects the single non-empty content section the view actually defined
   (`$resolvedPaneSection`), preferring the one that matches the active tab.
3. `@yield`s that section **only** into the active pane; inactive panes stay
   empty.

Why it matters: rendering the page into more than one pane would duplicate
element IDs and break DataTables. And a page that declares `@section('content')`
while its menu lives under Setup would otherwise activate the Setup tab but show
blank (its markup stuck in the hidden Home pane). This resolver decouples the
`@section` name from the tab.

> Practical rule: you can keep using `@section('setup_content')` (or whichever
> matches the page's tab) — but even a mismatched name will still render in the
> correct tab thanks to this logic.

---

## Scripts & styles stacks

The layout exposes several injection points. **Use the push/stack ones.**

| Page writes | Rendered by layout | Status |
|---|---|---|
| `@push('styles')` | `@stack('styles')` in `<head>` | ✅ page CSS |
| `@push('scripts')` | `@stack('scripts')` before `</body>` | ✅ **preferred for page JS** |
| `@section('scripts')` | `@yield('scripts')` | ✅ supported (added deliberately) |
| `@yield('script')` (singular) | present near end of body | legacy |

> ⚠️ **Gotcha:** historically only `@stack('scripts')` and the singular
> `@yield('script')` existed, so `@section('scripts')` (plural) was **silently
> dropped**. The layout now also renders `@yield('scripts')`, so both work — but
> **prefer `@push('scripts')`** for admin pages. No page should use both
> `@push('scripts')` and `@section('scripts')`, or the JS renders twice.

Global libraries are already loaded by the layout/footer — do **not** re-include
them per page:

- jQuery, Bootstrap, DataTables, **Select2 (`select2.full.min.js`)** — via footer.
- **SweetAlert2** — `swal` CDN, loaded here.
- Sidebar/nav JS: `sidebar-navigation-fixed.js`, `sidebar-panel-accordion.js`,
  `tab-persistence.js`, `nav-state.js`, `sidebar-dynamic-toggle.js`.

---

## Sidebar / navigation bootstrap

The dynamic sidebar is data-driven and hydrated client-side:

- `SidebarNavResolver` (server) resolves active tab/category/group from the
  matched route; `MenuRouteMatcher` resolves each menu's href.
- On load, the inline script restores the active **category** → fetches
  **groups** (`route('sidebar.groups')`) → fetches **menus**
  (`route('sidebar.menu')`) for the active/last-visited group.
- Active-link highlighting is delegated to `window.SargamNavState`
  (`nav-state.js`). Tab/group continuity persists across pages via
  `tab-persistence.js` + `SargamNavState`.
- `body.has-dynamic-sidebar` gates the dynamic behaviour; the collapse toggle
  (`#headerCollapse`) calls `window.toggleDynamicSidebarMenu()`.

(See the RBAC sidebar architecture note for the full active-state resolution.)

---

## Theming

- Initial theme is read from `localStorage.bsTheme` **before paint** to avoid a
  flash, set on `<html data-bs-theme>`.
- A `window.load` safeguard currently **forces light mode** (removes `dark`
  classes, pins `--bs-body-bg`/`--bs-body-color`). The app is effectively
  light-only today; account for this before building dark-mode UI.

---

## Other shell behaviour

- **Preloader** (`#sargamLoader`) hides on `window.load` (and via safety timeouts
  at 300ms/8s); `window.hideSargamLoader()` is exposed.
- **Mess module** (`admin.mess.*` routes) gets a body class
  `admin-mess-module`, a "data may be outdated" stale hint, and its own
  smooth-scroll + column-manager partials.
- **Faculty feedback bell** — for `@auth` users, polls
  `feedback.faculty.pendingCount` and shows a once-per-day SweetAlert prompt.

---

See also: [design.md](design.md) for the `sargam-app.css` token/component system
that page-scoped `@push('styles')` blocks build on.
