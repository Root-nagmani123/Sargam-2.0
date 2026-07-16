# Design System — `sargam-app.css`

The single consolidated custom stylesheet for the ERP:
`public/css/sargam-app.css`. It is loaded **last** in every layout (after
Bootstrap and the admin theme `styles.css`), so its rules win on cascade order
without needing `!important`.

> **One file only.** All bespoke, app-wide UI styling lives here. Do **not**
> create new global stylesheets — edit this file. Page-specific tweaks belong in
> a page-scoped `<style>` inside that view's `@push('styles')` block, built on
> the `--ds-*` tokens below (see `student_medical_exemption/index.blade.php` for
> the reference pattern).

The file is organised into three layers.

---

## Layer A — Design tokens (`:root`)

CSS custom properties consumed by every component and every page-scoped style.
Use the token, never the raw value, so the whole app re-themes from one place.

### Spacing (4px scale)
| Token | Value | Notes |
|---|---|---|
| `--ds-space-1` | 0.25rem | 4px |
| `--ds-space-2` | 0.5rem | 8px |
| `--ds-space-3` | 1rem | 16px — **default** page/gap unit |
| `--ds-space-4` | 1.5rem | 24px — between sections |
| `--ds-space-5` | 2rem | 32px — between major blocks |
| `--ds-space-6` | 3rem | 48px |

### Radius
| Token | Value | Use |
|---|---|---|
| `--ds-radius-1` / `--ds-radius` | 4px | **default** — buttons, inputs, badges, chips |
| `--ds-radius-2` / `--ds-radius-card` | 8px | cards, modals, panels |
| `--ds-radius-0` | 0 | square |

### Shadow
| Token | Use |
|---|---|
| `--ds-shadow-sm` | hairline lift (hover chips, buttons) |
| `--ds-shadow` | default card elevation |
| `--ds-shadow-lg` | modals / popovers |

### Colour
| Token | Value | Meaning |
|---|---|---|
| `--ds-primary` | `var(--bs-primary, #004a93)` | brand blue |
| `--ds-secondary` | `#b12923` | brand red |
| `--ds-ink` | `#1f2937` | primary text |
| `--ds-ink-muted` | `#667085` | secondary text |
| `--ds-line` | `#e5e7eb` | hairline borders |
| `--ds-surface` | `#ffffff` | card surface |
| `--ds-surface-2` | `#f8fafc` | subtle fill / table headers |
| `--ds-canvas` | `#f1f4f9` | page background |

### Controls
| Token | Value | Use |
|---|---|---|
| `--ds-control-h` | 2.5rem (40px) | consistent input/button height |
| `--ds-control-h-sm` | 2rem (32px) | compact controls |
| `--ds-focus-ring` | `0 0 0 0.2rem rgba(0 74 147 / .20)` | keyboard focus ring |

---

## Layer B — Gentle app-wide refinements (visual only)

Conservative rules applied to **~400+ existing views**, so they are deliberately
narrow: no layout shifts, no broad `!important`, nothing that changes behaviour.

- **Type rendering** — antialiasing + `optimizeLegibility` on `body`.
- **Focus ring** — `:focus-visible` on `.btn`, `.form-control`, `.form-select`,
  `.page-link`, `a.dropdown-item` (keyboard only; mouse unaffected).
- **Table headers** — quieter enterprise look (weight/colour/spacing only, no
  size or text-transform overrides).
- **Control heights** — `min-height` (not `height`) on form controls.
- **Select2 hide rule** — ships the canonical `.select2-hidden-accessible`
  clip rule that the theme omits, preventing the "two dropdowns" bug.
- **Project-wide page padding** — the theme's flat `padding:10px` on every
  `.container-fluid` is realigned to `--ds-space-3` (16px; 8px on phones),
  scoped under `.page-wrapper` so login/public pages are untouched.
- **DataTables sort arrows** — re-shown (the theme hides them) and the active
  direction highlighted in brand blue.

You normally don't touch Layer B; it's the baseline that makes legacy screens
look consistent for free.

---

## Layer C — Opt-in components (`.ds-*`)

Inert until a page adds the class. Use these when **modernizing a screen**.

| Class | What it is |
|---|---|
| `.ds-page-header` / `.ds-page-title` / `.ds-page-subtitle` | page heading row (flex, space-between) |
| `.ds-card` + `.ds-card-header` / `.ds-card-body` | surface card (8px radius, shadow) |
| `.ds-card--accent` | card with a brand-coloured accent |
| `.ds-toolbar` / `.ds-toolbar-spacer` | filter/action toolbar row |
| `.ds-stat-card` (`.ds-stat-label`/`.ds-stat-value`/`.ds-stat-icon`) | KPI tile |
| `.ds-table-wrap` / `.ds-table-sticky` | scroll wrapper + sticky header |
| `.ds-actions` | row-action button cluster |
| `.ds-form-section` / `.ds-form-section-title` | grouped form block |
| `.ds-stepper` (`.ds-step`, `.ds-step-index`, `.is-active`/`.is-done`) | multi-step progress |
| `.ds-empty-state` | empty/zero-record placeholder |
| `.ds-stack-2/3/4` | vertical rhythm (`* + *` margin) |
| `.ds-section` / `.ds-block` | section (24px) / block (32px) bottom gaps |

---

## Usage rules

1. **Edit only this file** for global custom UI. New global CSS files fragment
   the system.
2. **Always use `--ds-*` tokens** in page-scoped styles — never hard-code
   `#004a93`, `16px`, etc.
3. **Layer C is opt-in.** Adding a `.ds-*` class is safe; it changes nothing
   until applied.
4. **Page-scoped styles** go in `@push('styles')` (rendered by the master
   layout's `@stack('styles')` in `<head>`), and should express only what
   Bootstrap utilities + `.ds-*` can't.
5. **Load order matters** — this file must remain the last stylesheet so it
   overrides the theme without `!important`.

See also: [master.md](master.md) for how this stylesheet and page-scoped
`@push('styles')`/`@push('scripts')` blocks are wired into the layout.
