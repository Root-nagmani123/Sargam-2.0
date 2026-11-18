# UX4G / WCAG / GIGW Audit Checklist — SARGAM

Ticked = done **and verified** in the running app (Chrome, axe-core 4.10.3, keyboard walk) on: `/dashboard`, `/admin/roles`, `/admin/setup/member`, `/faculty/create`, plus the shared shell. Unticked = not done or not verified. Evidence: `UX4G-MIGRATION-REPORT.md` §7–13.

### Foundation
- [x] UX4G tokens (3.3.2 self-hosted; SARGAM overrides only via official token names)
- [x] Colour (LBSNAA primary scale; contrast-failing upstream status tokens corrected)
- [x] Typography (Noto Sans / Noto Sans Display / Devanagari)
- [x] Spacing (`--ds-space-1..4` → UX4G scale; 32/48 px documented as SARGAM)
- [x] Elevation (`--ds-shadow*` → UX4G l1/l3)
- [ ] Icons — shell on Material; Bootstrap Icons/Iconify/Font Awesome remain in modules

### Components
- [ ] Buttons — breadcrumb default only
- [ ] Inputs — focus ring only
- [ ] Selects
- [ ] Checkbox
- [ ] Radio
- [ ] Alerts
- [ ] Badges
- [ ] Cards
- [ ] Tables — pagination tokens only
- [ ] Modal
- [ ] Tabs
- [x] Breadcrumb (UX4G Breadcrumb in `<x-breadcrum>`, 349 views)
- [ ] Pagination — tokenised, not UX4G `ux4g-pagination` markup
- [ ] Dropdown
- [ ] Date picker
- [ ] File upload
- [ ] Search

### Accessibility
- [ ] WCAG 2.1 AA — 3 of 4 tested pages at 0 axe violations; dashboard has 7 exempt nodes; **app-wide not verified**
- [x] Keyboard — shell + 3 pages: all of the first 60 tab stops reachable; skip link works; notice modal Enter/Esc
- [x] Focus — visible on every tested tab stop; focus returns from notice dialog
- [ ] Screen reader — semantics fixed per axe; not tested with NVDA/JAWS
- [x] Contrast — tested pages (secondary text token fixed app-wide)
- [ ] Forms — labels present on `/faculty/create`; error summary / `aria-describedby` not audited
- [ ] Error handling — not audited
- [ ] Reduced motion — not audited (breadcrumb hover motion removed)

### GIGW
- [x] Navigation — landmarks, skip link, valid nav semantics (desktop + mobile)
- [ ] Content — not audited
- [ ] Accessibility — partial (see above)
- [x] Responsive — 320–1920 px, no horizontal overflow on tested pages
- [ ] Metadata — `<title>` present; `lang`, description not audited
- [ ] Forms — not audited
- [ ] Links — breadcrumb links OK; "Finance" bottom-bar links are `href="#"` placeholders
- [ ] Images — not audited (`ashoka emblem` alt present)

### Not yet run
- [ ] Firefox, Edge
- [ ] Lighthouse
- [ ] Other roles (Officer Trainee, Faculty, Employee)
- [ ] PHPUnit / Playwright suites
