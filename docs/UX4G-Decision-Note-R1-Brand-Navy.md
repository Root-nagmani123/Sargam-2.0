# Decision Note R-1 (Follow-up) — The Canonical Brand Navy

**For:** LBSNAA management + design authority
**From:** UX4G migration team
**Date:** 2026-08-04
**Decision needed:** Which single navy is the official Sargam 2.0 brand colour?
**Status:** ✅ **DECIDED & EXECUTED (2026-08-04) — `#004384` is canonical** (Option B).
Re-colour shipped: **211 files, ~1,119 occurrences** (`#004a93` hex ×910 **and**
`rgb(0,74,147)` ×209) → `#004384` / `rgb(0,67,132)`. **0** old-navy references remain.
Rendering verified (spot-checked visually — clean), and the visual baseline was
**re-approved across all three engines** (chrome 68, firefox 57, webkit 57).

> Execution note: done as a **literal** hex/rgb replacement (not `var(--bs-primary)`),
> because ~200 uses are in **DomPDF** PDF/print templates where CSS custom properties are
> unreliable — a literal is safe in browser *and* PDF. True `var()` tokenisation (browser
> templates only, excluding PDFs) remains an optional future refactor; the *value* is now
> uniform and correct everywhere.

---

## TL;DR

Sargam 2.0 currently ships **three different "brand navies"** used interchangeably.
This is not a bug and nothing is broken — **all three pass accessibility at the highest
level (WCAG AAA)** — but the fragmentation means the codebase has *no single source of
truth* for its primary colour, which blocks tokenisation, and two accessibility fixes.

We need **one** decision: pick the canonical navy. There is a low-friction option
(match what is already deployed) and a brand-purist option (enforce the spec value).
**We recommend the low-friction option** unless brand guidelines mandate otherwise.

---

## The finding

| Colour | Where it's used | Times | White-text contrast | WCAG |
|---|---|---:|---:|---|
| **`#004a93`** | Hardcoded across views + page CSS — the **de-facto** brand colour | **910** | **8.75 : 1** | AA **+ AAA** ✅ |
| `#1a3c6e` | A darker navy (step indicators, a few components) | 61 | 10.96 : 1 | AA + AAA ✅ |
| `#004384` | The **R-1 design token** (`--bs-primary`) — currently *dormant* | 3 | 9.83 : 1 | AA + AAA ✅ |
| `#0d6efd` | Bootstrap's default blue (legacy leftovers) | 65 | 4.50 : 1 | AA only |
| `#613AF5` | UX4G's stock violet (in the vendored library, unused in our views) | 41 | 6.12 : 1 | AA only |

*(Contrast measured with the WCAG 2.1 relative-luminance formula; white text on the
colour, as used on buttons/headers. Sanity-checked: black-on-white = 21.00.)*

**Two facts drive the decision:**
1. **Accessibility does not decide this.** `#004a93`, `#1a3c6e`, and `#004384` all
   clear AAA (7:1) comfortably. Any is a safe choice. (The two legacy blues, `#0d6efd`
   and the UX4G violet, only reach AA — neither is a candidate for the brand primary.)
2. **The deployed reality (`#004a93`, 910×) is *not* the spec token (`#004384`, 3×).**
   The token we set in R-1 was never made globally active, and the pages were built with
   `#004a93`. So today the portal *looks like* `#004a93` everywhere.

The two navies are visually very close — `#004a93` = rgb(0, 74, 147), `#004384` =
rgb(0, 67, 132); `#004a93` is a hair brighter. Side by side they are hard to tell apart.

---

## Why this blocks other work

A single canonical token is the prerequisite for three queued changes:

- **Tokenisation pass** — replacing the ~4,781 hardcoded colour literals with the
  `--bs-*` token. Can't proceed until we know the token's value.
- **A11Y-1 — input border contrast** (currently 1.26 : 1, needs 3 : 1). The remediated
  border colour should derive from the brand token.
- **A11Y-2 — focus-ring strength.** Same: the focus ring should use the brand token.

Until the navy is fixed, all three stay parked.

---

## The options

### Option A (recommended) — adopt **`#004a93`** as the canonical token
Make `#004a93` the official value; point `--bs-primary` at it; tokenise the 910
existing uses to `var(--bs-primary)`.

- ✅ **Zero visible change** — the portal already renders `#004a93`, so the tokenisation
  pass becomes a **safe, gate-passing refactor** (no re-baselining, no design review of
  910 screens).
- ✅ Passes AAA (8.75 : 1).
- ✅ Matches production the officers already see.
- ⚠️ Requires updating the R-1 token from `#004384` → `#004a93` (a one-line change).
- ⚠️ Only valid if `#004a93` is acceptable to the brand authority.

### Option B — enforce **`#004384`** (the current spec token)
Keep the R-1 spec value; re-colour the 910 `#004a93` uses to `#004384`.

- ✅ Honours the "official LBSNAA navy" chosen in the original R-1 note; best contrast
  (9.83 : 1, AAA).
- ⚠️ **Is a visible change** on ~910 elements (subtle hue shift). Requires design
  sign-off **and** a visual-baseline re-approval — more time, more risk.
- ⚠️ The change is real but so subtle most users won't perceive it — high effort, low
  perceptible payoff.

### Not options
`#1a3c6e` (darker, only 61 uses — would be the *largest* visual change), `#0d6efd`,
and `#613AF5` (both only AA; violet is off-brand for LBSNAA).

---

## Recommendation

**Adopt `#004a93` (Option A)** — *unless* the LBSNAA brand guideline specifies `#004384`
(or another exact value), in which case brand compliance wins and we take Option B.

Rationale: both are AAA-accessible and nearly identical to the eye, so the tie-breaker is
**risk and effort**. Option A makes the whole tokenisation effort a zero-visual, fully
gated refactor and matches what the portal already looks like. Option B buys a marginally
higher contrast and spec-fidelity at the cost of a reviewed visual change across 910
elements.

**The one thing we need from the brand authority:** confirm whether an official hex value
exists. If yes → that value is canonical (Option B mechanics). If no → Option A.

---

## Once decided (what we do next)

1. Set `--bs-primary` (and link/focus/border derivations) to the chosen navy.
2. Run the tokenisation pass — replace hardcoded navies with `var(--bs-primary)`
   (Option A: expect **zero** visual diff; Option B: reviewed diff + re-baseline).
3. Ship A11Y-1 (input border) and A11Y-2 (focus ring) deriving from the token — gated.
4. Retire the legacy `#0d6efd` (×65) and any stray `#1a3c6e` into the token.

---

*Companion to `UX4G-Decision-Note-R1-R2-R3.md` (navy-vs-violet, version pinning,
self-hosting). This note resolves the **which navy** question that the Phase 12 page
audit surfaced.*
