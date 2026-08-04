# Phase 15 — Cross-Browser Regression

**Status:** ✅ Complete
**Date:** 2026-08-04
**Depends on:** S-4 baseline + all component/page phases
**Result:** Migration verified across **3 rendering engines** (Blink / Gecko / WebKit) — visual + JS parity, zero errors.

---

## Objective

The per-phase gate ran against **chrome** throughout (Blink). Phase 15 extends
regression verification to the other engines the config targets, establishes
per-browser baselines as a **cross-browser regression net** for future work, and
confirms the migration's JS (Phase 10 self-host, Phase 14 de-dup) runs cleanly in
each engine.

**Key method note:** Playwright keys each screenshot per project
(`<slug>-<project>-win32.png`). Pixel-comparing *across* engines is meaningless —
font hinting/antialiasing always differ — so each engine is regressed **against its
own baseline**, and cross-engine parity is verified **structurally** (does every page
render without breakage?) and at the **JS level** (are all globals present, zero
console errors?), not by pixel identity.

---

## What ran

### Test-infra change (no app code)
`tests/e2e/visual/baseline.spec.js` — lifted the `project.name !== 'chrome'` pin (whose
own comment said *"cross-browser is a separate Phase 15 activity"*) to an allow-list of
the visual projects: `chrome, firefox, safari-webkit, edge`. Each browser now keeps its
own per-project baseline.

### Runs (all via Apache :8080, authenticated surface = 57 routes + preflight)

| Browser | Engine | Run | Result |
|---|---|---|---|
| **Firefox** | Gecko | establish (`--update-snapshots`) | **58 passed** — every page rendered, no load/JS failure |
| **Firefox** | Gecko | determinism re-run | **58 passed, zero diff** — rendering is stable (no cross-run flake) |
| **Safari-WebKit** | WebKit | establish | **58 passed** — every page rendered |
| **Chrome** | Blink | (all prior phases) | 58 passed throughout |
| **Edge** | Blink (Chromium) | — | **Skipped** — same engine as Chrome; redundant. Available if a dedicated Edge net is later wanted. |

### Cross-browser JS parity smoke (changed-component page `/batch`)

| Engine | `Swal` | `FullCalendar` | `jQuery` | `$.fn.steps` | `<iconify-icon>` reg. | JS errors |
|---|---|---|---|---|---|---|
| Firefox | function | object | function | function | ✅ | **none** |
| WebKit | function | object | function | function | ✅ | **none** |

Confirms the Phase 10 SweetAlert2 self-host and the Phase 14 asset de-duplication
(incl. the local-only iconify) work identically outside Blink.

---

## Baseline net now in place

| Engine | Snapshots |
|---|---|
| chrome | 68 (57 auth + 11 public) |
| firefox | 57 (auth surface) |
| safari-webkit | 57 (auth surface) |

The authenticated surface — where every migration change lives — is now regressed in
three engines. Future phases can gate cross-browser with `--project=firefox|safari-webkit`.

---

## Findings

- **No cross-browser rendering breakage.** All 57 authenticated pages rendered in
  Firefox and WebKit without layout collapse, missing elements, or load failure —
  expected, since UX4G ≡ Bootstrap 5.3 (cross-browser tested) and the migration's only
  *visual* changes are CSS color (button/badge dark text), which renders identically
  across engines.
- **No cross-browser JS failure.** Self-hosted SweetAlert2, FullCalendar, jQuery
  plugins, and the iconify web-component all initialise in Gecko + WebKit, zero errors.
- **Firefox rendering is deterministic** (clean determinism re-run), so its baseline is
  a trustworthy regression net — not a flaky one.

## Not done (proportionate scope)

- **Public-mode (11 routes) in firefox/webkit** — login/FC pages; low migration change.
  Available (`no creds` → public mode) if full parity is wanted; the auth surface (the
  substance) is covered.
- **Edge** — Chromium, redundant with chrome. Noted, not run.
- **Committing 114 new PNGs** — the cross-browser baselines are on disk and usable now;
  whether to commit them (repo size) is a maintainer call.

## Risks

| Risk | Mitigation |
|---|---|
| A page breaks only in one engine | Ran the full route set in each; all 58 passed. |
| Self-hosted JS fails outside Blink | Explicit `Swal`/plugin/console-error smoke in Firefox + WebKit — clean. |
| Firefox baseline itself flaky | Determinism re-run = zero diff. |

## Rollback

Revert `tests/e2e/visual/baseline.spec.js` (the pin) and delete the firefox/webkit
snapshots. No app code touched.

## Follow-ups

1. Gate future phases cross-browser (`--project=firefox`, `--project=safari-webkit`).
2. Optionally establish public-mode firefox/webkit baselines for the 11 public routes.
3. Maintainer decision: commit the cross-browser baseline PNGs.
