# UX4G Design Tokens — SARGAM

Source of truth: UX4G 3.3.2 (`public/vendor/ux4g/3.3.2/ux4g.css`, ~1,485 `--ux4g-*` custom properties).
SARGAM sets token **values** only in `public/css/sargam-app.css` → "LAYER 0". Never invent new `--ux4g-*` names.

Use in new code, in this order: UX4G class (`ux4g-btn-primary`) → UX4G semantic token (`var(--ux4g-text-neutral-secondary)`) → `--ds-*` alias (legacy pages) → raw value (never).

## 1. UX4G official tokens SARGAM overrides (with reason)

| Token | UX4G default | SARGAM | Reason |
|---|---|---|---|
| `--ux4g-color-primary-50 … 950` | purple scale (`600` = `#4a2bc2`) | LBSNAA blue scale, `600` = `#004a93` | Brand. Same mechanism as UX4G's own Neel theme. 600 is the step UX4G uses for filled buttons, links, strong border, focus ring. `600` on white 8.75:1. |
| `--ux4g-text-info-default` | cyan-600 `#13c2c2` (2.21:1) | `var(--ux4g-color-cyan-800)` 6.10:1 | WCAG 1.4.3 |
| `--ux4g-text-warning-default` | orange-600 `#fa8c16` (2.38:1) | `var(--ux4g-color-orange-800)` 5.43:1 | WCAG 1.4.3 |
| `--ux4g-text-success-default` | green-600 (4.51:1, fails on tints) | `var(--ux4g-color-green-700)` 6.57:1 | WCAG 1.4.3 |
| `--ux4g-text-error-default` | red-600 (4.57:1) | `var(--ux4g-color-red-700)` 6.56:1 | WCAG 1.4.3 on tinted fills |
| `--ux4g-font-family-base` | `"Noto Sans", system-ui` | adds `"Noto Sans Devanagari"` | Hindi content |
| `--ux4g-font-family-display` | `"Noto Sans Display", "Noto Sans"` | adds `"Noto Sans Devanagari"` | Hindi headings |

Primary scale (all values): 50 `#f2f6fa` · 100 `#e0e9f2` · 200 `#bfd2e4` · 300 `#94b3d2` · 400 `#618fbc` · 500 `#2e6ba6` · **600 `#004a93`** · 700 `#003d79` · 800 `#003161` · 900 `#00254a` · 950 `#001932`.
Note: 300 is 2.18:1 on white; UX4G uses it only for decorative primary borders (upstream purple-300 is similar). Do not use it for text or input borders.

Dark mode: overrides are on `:root`; UX4G's dark values sit on higher-specificity dark selectors and are unaffected. The app currently forces light mode.

## 2. Bootstrap variables re-pointed at UX4G (light mode only)

| Variable | Was | Now |
|---|---|---|
| `--bs-secondary-color` | `rgba(152,164,174,.75)` ≈ 1.9:1 | `var(--ux4g-text-neutral-secondary)` (#404040, 10.4:1) |
| `--bs-tertiary-color` | `rgba(152,164,174,.5)` | `var(--ux4g-text-neutral-tertiary)` (#737373, 4.74:1) |
| `--bs-font-sans-serif` (custom.css) | Noto stack | `var(--ux4g-font-family-base)` |

Rule: a `--bs-*` variable may only be pointed **at** a UX4G token, never given a new raw value.

## 3. Semantic tokens to use

| Need | Token |
|---|---|
| Primary text / secondary / tertiary | `--ux4g-text-neutral-primary` / `-secondary` / `-tertiary` |
| Brand text, links | `--ux4g-text-brand-primary-default`, `--ux4g-text-link-default-default` |
| Status text | `--ux4g-text-{success,warning,error,info}-default` |
| Surfaces | `--ux4g-bg-neutral-elevated` (cards), `--ux4g-bg-neutral-soft` (subtle fill) |
| Primary fills | `--ux4g-bg-primary` (tint), `--ux4g-bg-primary-strong` (filled), `--ux4g-bg-primary-strong-hover` |
| Borders | `--ux4g-border-color-neutral-subtle` (hairline), `-default` (inputs), `--ux4g-border-color-primary-strong` |
| Status fills | `--ux4g-bg-{success,warning,error,info}` (tint), `-strong` (filled) |

## 4. Typography

Fonts loaded by `<x-fonts-sargam />`: Noto Sans 400–700 (+ italic 400), Noto Sans Display 500–700, Noto Sans Devanagari 400–700.
Sizes: `--ux4g-font-size-{11,12,14,16,18,20,24,28,32,36,40,48}`; weights `--ux4g-font-weight-{regular,medium,semibold,bold}`; line heights `--ux4g-line-height-*`.
Headings use `--sargam-font-heading` → `--ux4g-font-family-display`.

## 5. Spacing, radius, elevation

| `--ds-*` alias | UX4G token | Value |
|---|---|---|
| `--ds-space-1` | `--ux4g-space-2` | 4px |
| `--ds-space-2` | `--ux4g-space-4` | 8px |
| `--ds-space-3` | `--ux4g-space-6` | 16px |
| `--ds-space-4` | `--ux4g-space-8` | 24px |
| `--ds-space-5` | *SARGAM* | 32px (UX4G scale has no 32px step: 24 → 40) |
| `--ds-space-6` | *SARGAM* | 48px (no UX4G step) |
| `--ds-radius-1` | `--ux4g-radius-sm` | 4px — controls |
| `--ds-radius-2` | `--ux4g-radius-md` | 8px — cards, modals |
| `--ds-shadow-sm`, `--ds-shadow` | `--ux4g-shadow-l1` | |
| `--ds-shadow-lg` | `--ux4g-shadow-l3` | |

## 6. Focus

`:where(:focus-visible)` and `.btn/.form-control/.form-select/.form-check-input/.nav-link/.page-link/a.dropdown-item:focus-visible`:
`outline: var(--ux4g-border-width-md) solid var(--ux4g-border-color-primary-strong); outline-offset: 2px;` (UX4G focus.css values). On dark surfaces use `--ux4g-color-neutral-0`. Never use `box-shadow` alone for focus (`.shadow-none` erases it).

## 7. Iconography

UX4G specifies Material icons. SARGAM uses the local **Material Symbols Rounded** font (`material-icons material-symbols-rounded`) because the UX4G "Material Icons" font lacks names already used in menus (e.g. `user_attributes`, verified in Chrome). Decorative icons must carry `aria-hidden="true"`; icon-only controls need `aria-label`. Do not add Bootstrap Icons / Iconify / Font Awesome in new code.

## 8. SARGAM custom tokens (no UX4G equivalent)

| Token | Value | Note |
|---|---|---|
| `--ds-secondary`, `--ds-secondary-rgb` | `#b12923` | LBSNAA red. UX4G secondary tokens do not drive components. |
| `--ds-accent` | `#2563eb` | additive "add row" control — pending review |
| `--ds-canvas` | `#f1f4f9` | page background matching the theme shell |
| `--ds-control-h`, `--ds-control-h-sm` | 40px / 32px | match UX4G m / s control heights |
