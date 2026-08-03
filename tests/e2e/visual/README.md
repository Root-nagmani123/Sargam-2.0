# Visual Regression Baseline — Stage 0 / S-4

The safety net for the UX4G migration. Every Stage 0 step and Phase 1 must produce a
**zero diff** here; later phases produce diffs deliberately, which are then reviewed
and re-approved.

**Nothing in this folder is loaded by the application.** These are test assets only.

---

## Files

| File | Role |
|---|---|
| `_helpers.js` | Login, page stabilisation, snapshot naming, mask selectors |
| `stabilize.css` | Injected at screenshot time — kills animation, caret, scrollbars, spinners |
| `discover-routes.spec.js` | Harvests sidebar routes → writes `routes.json` (guarded by `E2E_DISCOVER=1`) |
| `baseline.spec.js` | Screenshots every route in `routes.json` and diffs it |
| `routes.json` | **Committed.** The pinned route list the baseline captures |
| `baseline.spec.js-snapshots/` | **Committed.** The reference PNGs |

---

## One-time setup

```bash
export E2E_BASE_URL=http://localhost:8000     # app confirmed serving here
export E2E_USERNAME='<admin username>'
export E2E_PASSWORD='<admin password>'
```

PowerShell:

```powershell
$env:E2E_BASE_URL='http://localhost:8000'
$env:E2E_USERNAME='<admin username>'
$env:E2E_PASSWORD='<admin password>'
```

> Use an account with **broad RBAC permissions**. Route discovery reads the sidebar,
> so a narrowly-permissioned account yields a narrow baseline. Never commit credentials.

---

## 1. Generate the route list (run once, then only when navigation changes)

```bash
E2E_DISCOVER=1 npx playwright test tests/e2e/visual/discover-routes.spec.js --project=chrome
```

Writes `routes.json`: up to 60 routes (`E2E_MAX_ROUTES` to change), spread
round-robin across modules so coverage is broad rather than 60 pages of one module.

Excluded automatically: `logout`, `delete`, `destroy`, `export`, `download`,
`print`, `pdf`/`excel`/`csv`, and any path containing a record id (`/1234/`) —
record pages make unstable baselines because the record can change.

**Commit `routes.json`.** The baseline is pinned to it.

## 2. Capture the baseline (run once, on pre-migration code)

```bash
npx playwright test tests/e2e/visual/baseline.spec.js --project=chrome
```

First run writes `baseline.spec.js-snapshots/*.png`. **Commit them.**
This must happen on the current code, *before* any Stage 0 or UX4G change.

## 3. Verify a change (run after every step)

```bash
npx playwright test tests/e2e/visual/baseline.spec.js --project=chrome
```

- **All green** → zero visual change. Gate passed.
- **Failures** → open `playwright-report/` for side-by-side actual/expected/diff.

## 4. Accept an intended change

Only after reviewing every diff image:

```bash
npx playwright test tests/e2e/visual/baseline.spec.js --project=chrome --update-snapshots
```

Commit the updated PNGs **in the same commit as the change that caused them**, so
`git log` explains every baseline movement.

---

## Gate criteria per Stage 0 step

| Step | Change | Expected result |
|---|---|---|
| S-4 | test files only | baseline captured |
| S-1 | restore local jQuery | **zero diff** |
| S-2 | unify Bootstrap version | **zero diff** + modal/dropdown/tab smoke |
| S-3 | icon triage | zero diff, or icons visibly improve |
| S-5 | remove third-party CDN refs | **zero diff** |
| S-6 | delete unreferenced libraries | **zero diff** + no console 404s |
| Phase 1 | self-host UX4G under existing CSS | **zero diff** |

---

## Design decisions

**Chrome only.** Cross-browser is a distinct Phase 15 activity. Baselining four
engines would quadruple runtime and produce diffs from font rasterisation
differences rather than from our changes.

**Serial, one login.** `test.describe.configure({ mode: 'serial' })` with a single
`beforeAll` login. Re-authenticating per route triples runtime and introduces a
redirect that can race the screenshot.

**Mask, don't hide.** Volatile *content* (row counts, server time) is masked —
painted over — so layout and box-model regressions in those regions are still
caught. Hiding them would blind the baseline to real breakage.

**`maxDiffPixelRatio: 0.001`.** ~0.1 % absorbs sub-pixel antialiasing noise while
still catching a shifted button or a changed colour.

**Session-loss assertion.** If a route bounces to `/login`, the test fails rather
than baselining 60 copies of the login page.

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `routes.json missing` | Discovery not run | Step 1 |
| All tests skipped | Credentials unset | Export `E2E_USERNAME` / `E2E_PASSWORD` |
| `Login failed — still on /login` | Wrong credentials, or account locked | Verify manually in a browser |
| `Session lost while loading <route>` | Session timeout mid-run | Raise session lifetime on the test env, or shorten the route list |
| One page diffs on every run | Undetected volatile content | Add its selector to `MASK_SELECTORS` in `_helpers.js` |
| Everything diffs by a few pixels | Different OS/display scaling | Baselines are machine-specific — capture and verify on the same machine, or move to CI |
