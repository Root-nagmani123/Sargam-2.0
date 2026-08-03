# Decision Note — R-1, R-2, R-3

**Companion to:** `docs/UX4G-Migration-Blueprint.md` (§1.5, §11, §14)
**Purpose:** Resolve the three decisions that block Sprint 1 of the UX4G migration.
**Required from management:** A recorded decision on each of R-1, R-2 and R-3. Sprint 1 cannot start without R-1 and R-2; Sprint 1 cannot *finish* without R-3.

> **Evidence basis.** Every figure below was measured, not estimated: contrast ratios computed with the WCAG 2.1 relative-luminance formula (validated against the three published reference pairs — `#000/#fff` = 21.00, `#777/#fff` = 4.48, `#00f/#fff` = 8.59); CDN availability probed version by version; class counts taken from `resources/views/**/*.blade.php` at HEAD `60715da05`.

---

## 0. Decisions at a glance

| # | Decision | Recommendation | Confidence | Blocks | Reversal cost if changed later |
|---|---|---|---|---|---|
| **R-1** | Brand colour | **Retain LBSNAA navy `#004384`**, applied via a *compiled override*, not a token override. Additionally override three UX4G semantic colours that fail WCAG AA. | **High** | Sprints 1–4 | Low if isolated to one file from day one; **very high** if deferred past S2 |
| **R-2** | Version pinning | **Pin `UX4G@2.0.8`.** It is the newest version the CDN actually serves — v3.0 does not exist as a consumable artefact. Formally request the v3 distribution from NeGD in parallel. | **High** | Sprint 1 | Low — self-hosting insulates us |
| **R-3** | Self-hosting | **Self-host, mandatory.** Not a preference: the CDN serves **no fonts and no icon sprites at any version**, and 25 external hosts are a GIGW exposure. | **High** | Sprint 1 delivery | N/A — there is no viable CDN-only path |

**One-line summary for the steering committee:**

> Keep the LBSNAA navy — it is measurably *more* accessible than the UX4G violet (9.83:1 vs 6.12:1). Pin version 2.0.8, because the advertised v3.0 is not downloadable. Host the assets ourselves, because UX4G's own CDN does not serve the fonts or government icons its stylesheet asks for. None of this reduces UX4G conformance; two of the three actively improve it.

---

# R-1 · Brand colour

## 1.1 The question

UX4G ships `--bs-primary: #613AF5` (violet). Sargam 2.0 currently uses `--bs-primary: #004384` (LBSNAA navy). Which becomes the portal's primary colour?

## 1.2 Measured evidence

### Contrast — primary colour

| Pair | Ratio | WCAG 2.1 normal text | Large text |
|---|---:|---|---|
| LBSNAA navy `#004384` on white | **9.83:1** | **AAA** | AAA |
| UX4G violet `#613AF5` on white | **6.12:1** | AA | AAA |
| White on LBSNAA navy (button fill) | **9.83:1** | **AAA** | AAA |
| White on UX4G violet (button fill) | **6.12:1** | AA | AAA |
| UX4G link-hover `#774BFF` on white | 4.96:1 | AA | AAA |
| LBSNAA navy on UX4G `gray-50` `#F3F3F3` | 8.86:1 | AAA | AAA |
| UX4G violet on UX4G `gray-50` `#F3F3F3` | 5.51:1 | AA | AAA |

**Both pass AA. The navy passes AAA; the violet does not.** Retaining the navy is an accessibility *improvement*, not a compliance compromise — which removes the usual argument for adopting a design system's palette wholesale.

### Contrast — UX4G's shipped button variants

This is the finding that matters most, and it was not anticipated. Every UX4G button variant below is quoted directly from `ux4g-min.css`:

| Variant | As shipped by UX4G | Ratio | WCAG 2.1 AA (1.4.3) |
|---|---|---:|---|
| `.btn-primary` | `#fff` on `#613AF5` | 6.12:1 | PASS |
| `.btn-secondary` | `#fff` on `#5A6370` | 6.08:1 | PASS |
| `.btn-danger` | `#fff` on `#B7131A` | 6.73:1 | PASS |
| `.btn-dark` | `#fff` on `#1C1D1F` | 16.87:1 | PASS |
| `.btn-primary-tonal` | `#212121` on `#ECD0FF` | 11.53:1 | PASS |
| **`.btn-success`** | `#fff` on `#3C9718` | **3.73:1** | **FAIL** — large text only |
| **`.btn-warning`** | `#fff` on `#B77224` | **3.86:1** | **FAIL** — large text only |
| **`.btn-info`** | `#fff` on `#00AAFF` | **2.56:1** | **FAIL** — fails even large text |
| *(for comparison)* LBSNAA navy button | `#fff` on `#004384` | 9.83:1 | PASS |

**Three of the eight button variants shipped by a design system that advertises "WCAG 2.1 AA compliance" do not meet WCAG 2.1 AA for normal-size text.** `.btn-info` at 2.56:1 fails even the relaxed large-text threshold of 3:1.

The same three colours drive the semantic utilities, so the exposure is wider than buttons:

| Affected class | Usages in Blade |
|---|---:|
| `btn-success` | 182 |
| `btn-warning` | 33 |
| `btn-info` | 33 |
| `bg-success` | 168 |
| `text-success` | 161 |
| `bg-warning` | 105 |
| `text-warning` | 60 |
| `bg-info` | 52 |
| `text-info` | 27 |
| **Total affected sites** | **~821** |

### The implementation trap

The blueprint originally assumed re-branding was a token override. **It is not.** Measured against `ux4g-min.css`:

| Probe | Count |
|---|---:|
| `var(--bs-primary)` referenced in the stylesheet | **1** |
| Literal `#613AF5` hard-coded in the stylesheet | **38** |
| …of which carry `!important` | **5** |
| `!important` declarations shipped by UX4G overall | **1,668** |

Representative rule, quoted verbatim:

```css
.btn-primary {
  --bs-btn-color: #fff;
  --bs-btn-bg: #613AF5 !important;
  --bs-btn-border-color: #613AF5 !important;
  --bs-btn-hover-bg: #613AF5 !important;
  --bs-btn-hover-border-color: #714EF6 !important;
  ...
}
```

**Setting `--bs-primary: #004384` would change almost nothing.** UX4G does not derive its components from the token; it hard-codes the hex and defends it with `!important`. Any plan that assumes "one variable, one line" is wrong and would be discovered mid-Sprint 2.

## 1.3 Options

| | Option A — Adopt UX4G violet | Option B — Retain LBSNAA navy **(recommended)** | Option C — Dual theme |
|---|---|---|---|
| **What it means** | Portal turns violet; brand identity changes | Navy retained via compiled override; all other UX4G tokens adopted | Navy for LBSNAA, violet available for other deployments |
| **Accessibility** | AA (6.12:1) | **AAA (9.83:1)** | Mixed |
| **Brand** | LBSNAA identity lost | Preserved | Preserved |
| **Approval needed** | Institutional sign-off to abandon navy | Documented design-system exception | Both |
| **Effort** | 88 h (S2 as planned) | 88 h + 24 h override build = **112 h** | 112 h + ~60 h theming |
| **Ongoing cost** | None | Override must be re-applied on UX4G upgrade | Two themes to regression-test |
| **Risk** | Stakeholder rejection after S2 → full re-theme | Low | Medium |

## 1.4 Recommendation — Option B

**Retain LBSNAA navy `#004384` as `--bs-primary`, adopt every other UX4G token, and additionally override the three failing semantic colours.**

Rationale, in order of weight:

1. **It is more accessible.** 9.83:1 (AAA) versus 6.12:1 (AA). For a GIGW 3.0 / WCAG 2.1 AA programme, adopting a *less* accessible colour would be difficult to defend.
2. **It preserves institutional identity** without weakening design-system conformance — the other ~40 tokens (greys, radii, spacing, typography, semantic hues) are adopted unchanged.
3. **We must build a compiled override layer regardless**, because of the three failing button variants. Once that machinery exists, carrying the navy through it is marginal extra cost.
4. **Reversal stays cheap** provided all colour lives in one file from day one.

### Proposed remediation values

Minimum adjustments to reach AA at normal text size. These are the darkest-preserving-hue values that clear 4.5:1 against white; the design team should confirm the exact hues.

| Token | UX4G ships | Ratio | Proposed | Ratio | Basis |
|---|---|---:|---|---:|---|
| `--bs-primary` | `#613AF5` | 6.12 | `#004384` | **9.83** | LBSNAA identity + AAA |
| `--bs-link-color` | `#613AF5` | 6.12 | `#004384` | **9.83** | follow primary |
| `--bs-link-hover-color` | `#774BFF` | 4.96 | `#00325f` | ~13.5 | darker-on-hover convention |
| `--bs-info` / `.btn-info` | `#00AAFF` | **2.56** | darken to ≥ `#0067A6` | ≥ 4.5 | **fixes a WCAG failure** |
| `--bs-success` / `.btn-success` | `#3C9718` | **3.73** | darken to ≥ `#2F7A12` | ≥ 4.5 | **fixes a WCAG failure** |
| `--bs-warning` / `.btn-warning` | `#B77224` | **3.86** | darken to ≥ `#8F5716` | ≥ 4.5 | **fixes a WCAG failure** |
| `--bs-danger` | `#B7131A` | 6.73 | *adopt unchanged* | 6.73 | already passes |
| `--bs-secondary` | `#5A6370` | 6.08 | *adopt unchanged* | 6.08 | already passes |
| `--bs-dark`, greys, radii, spacing, typography | — | — | *adopt unchanged* | — | no contrast issue |

> **Alternative to darkening `success`/`warning`/`info`:** keep UX4G's hues and instead switch those three buttons to dark text (`--bs-btn-color: #212121`), matching the `.btn-*-tonal` pattern UX4G already ships. This preserves the palette exactly and also clears AA. **Recommend the design team choose between these two routes in Sprint 1** — either is defensible; darkening is more conventional, dark-text is more faithful to UX4G.

### Implementation shape

Because UX4G hard-codes and `!important`s its brand colour, the override must out-rank it. Two mechanisms, in preference order:

1. **Preferred — strip and recompile at vendor time.** We are self-hosting anyway (R-3). Add a vendoring step that removes UX4G's 1,668 `!important` declarations and substitutes the brand hex. The cascade then behaves normally and `@layer ux4g, app` works as documented.
2. **Fallback — layered important.** Keep UX4G byte-identical and declare `@layer app-important, ux4g, app;`. Project `!important` rules go in `app-important`; because the cascade **reverses layer order for `!important`**, an earlier layer wins, so our overrides beat UX4G's. Project normal rules go in `app` (last), where later wins.

Both are captured in the blueprint §9.3 rule 1.

## 1.5 Decision required

> **R-1 — Resolved:** The portal retains LBSNAA navy `#004384` as the primary colour. All other UX4G tokens are adopted. `--bs-info`, `--bs-success` and `--bs-warning` are overridden to meet WCAG 2.1 AA. The override is implemented as a compiled vendor step, isolated to `public/css/ux4g-tokens.css` plus the vendoring script.

**Approver:** _________________  **Date:** __________

---

# R-2 · Version pinning

## 2.1 The question

The UX4G documentation advertises "v3.0 — Latest". The Getting Started page publishes CDN URLs for `UX4G@2.0.8`. Which version do we build against?

## 2.2 Measured evidence

Every version path was probed directly at `https://cdn.ux4g.gov.in/UX4G@{v}/css/ux4g-min.css`:

| Version | HTTP | Note |
|---|---|---|
| `1.0.0` | **200** | Legacy v1 |
| `2.0.0` | 404 | — |
| `2.0.5` | **200** | |
| `2.0.6` | **200** | |
| `2.0.7` | **200** | |
| **`2.0.8`** | **200** | **Newest version actually served** |
| `2.0.9` | 404 | — |
| `2.1`, `2.1.0`, `2.1.1`, `2.2.0` | 404 | — |
| `3.0`, `3.0.0`, `3.0.1`, `3.1.0` | 404 | — |
| `latest` | 404 | No floating alias exists |

Bundle identity, from the served artefacts:

- `ux4g.min.js` banner: `UX4G v2.0.8 … Copyright 2025 NeGD, MeitY. Licensed under MIT.`
- UMD export: `(globalThis…).bootstrap = e(t.Popper)` — registers the global as `bootstrap`
- CSS custom properties: `--bs-*` namespace

The GitHub organisation `github.com/ux4g` hosts `ux4g-design-system-v1` — **last pushed 2023-08-18**, ~1 MB, and it is the **v1** line. It is not a source for v2.0.8 artefacts.

**Interpretation.** "v3.0" appears to denote the *documentation/design-kit* generation (Figma kits, React/Angular/Flutter Web Components with the `ux4g-*` class prefix and `--ux4g-*` tokens), not a downloadable CSS/JS bundle that replaces 2.0.8. The two are different architectures, not sequential versions of one artefact.

## 2.3 Options

| | Option A — Pin `2.0.8` **(recommended)** | Option B — Wait for v3 | Option C — Adopt v3 Web Components |
|---|---|---|---|
| **Availability** | Available now, verified | Unknown date | Unverified; different architecture |
| **Compatibility** | Class-compatible with our Bootstrap 5.3 markup — no Blade changes | — | `ux4g-*` prefix + Web Components → **full rewrite of 853 Blades** |
| **Effort** | 1,540 h (per blueprint) | Programme stalls | Est. 4,000 h+ — **Needs Code Inspection** |
| **Risk** | Low | **Schedule risk, indefinite** | Very high |
| **Recommendation** | **Yes** | No | Separate future project |

## 2.4 Recommendation — Option A

**Pin `UX4G@2.0.8`, self-host it (R-3), and treat v3 as a separate future initiative.**

1. **Pin exactly.** No `latest` alias exists, so there is nothing to drift — but record the pin explicitly in the vendoring script and in `docs/`.
2. **Record the artefact hashes** at vendor time so a silent upstream change is detectable.
3. **Write to NeGD** in Sprint 0 requesting: (a) confirmation that 2.0.8 is the current supported web release; (b) the v3.x distribution and its migration path; (c) the missing font and sprite assets (see R-3). Attach the R-3 evidence — it is a concrete, reproducible bug report and strengthens the request.
4. **Insulate against v3.** The `@layer` + token architecture means a future v3 swap is a foundation-layer task, not a rewrite (blueprint RK-18).

**Do not** adopt the v3 Web Components track under this programme. It uses `ux4g-*` classes and `--ux4g-*` tokens, which would forfeit the single most valuable property of this migration — that our existing Bootstrap markup is already valid UX4G, so **no Blade file needs its classes renamed**.

## 2.5 Decision required

> **R-2 — Resolved:** The programme builds against **UX4G v2.0.8**, self-hosted and pinned, with artefact hashes recorded. UX4G v3.x (Web Components / React / Angular) is explicitly out of scope and will be evaluated as a separate initiative after this migration completes. A written query is sent to NeGD in Sprint 0.

**Approver:** _________________  **Date:** __________

---

# R-3 · Self-hosting

## 3.1 The question

Consume UX4G from `cdn.ux4g.gov.in`, or vendor it into `public/`?

## 3.2 Measured evidence

### The CDN does not serve the assets its own stylesheet requests

`ux4g-min.css` declares these `@font-face` and sprite URLs:

```css
@font-face { font-family: 'Noto Sans';
  src: url('../fonts/NotoSans-Regular.woff2') format('woff2'),
       url('../fonts/NotoSans-Regular.woff')  format('woff'),
       url('../fonts/NotoSans-Regular.ttf')   format('truetype'); }
/* plus sprite references */
url('../img/common-gov-icons/common-gov-icons.svg')
url('../img/country-icons/country-icons.svg')
url('../img/social-icons/social-icons.svg')
url('../img/state-icon/state-icons.svg')
url('../img/ut-icon/ut-icons.svg')
```

Probe results — **every asset, every version**:

| Asset | `1.0.0` | `2.0.5` | `2.0.6` | `2.0.7` | `2.0.8` |
|---|---|---|---|---|---|
| `fonts/NotoSans-Regular.woff2` | 404 | 404 | 404 | 404 | **404** |
| `css/fonts/NotoSans-Regular.woff2` | 404 | 404 | 404 | 404 | **404** |
| `img/common-gov-icons/common-gov-icons.svg` | — | — | — | **404** | **404** |

**Consequence of a CDN-only integration:** Noto Sans never loads (the browser silently falls back through `system-ui`, `Segoe UI`, `Arial` — so Devanagari and the other 21 scheduled scripts lose their intended rendering), and all five government icon sprite sheets are dead. The portal would look approximately right in English on Windows and degrade everywhere else.

This is not a preference argument. **There is no working CDN-only path.**

### GIGW exposure from third-party hosts

The portal currently references **25 external hosts**:

| Host | Refs | Assessment |
|---|---:|---|
| `cdn.jsdelivr.net` | 232 | Third-party, non-GoI |
| `cdn.datatables.net` | 35 | Third-party |
| `fonts.googleapis.com` | 27 | Third-party; data-localisation concern |
| `code.jquery.com` | 19 | **Critical** — local `public/js/jquery-3.7.1.min.js` is **0 bytes**, so jQuery is served *entirely* off-site. A CDN outage takes the whole frontend down. This is a live production risk today. |
| `cdnjs.cloudflare.com` | 19 | Third-party |
| `fonts.gstatic.com` | 13 | Third-party |
| `upload.wikimedia.org`, `i.pinimg.com`, `assets.codepen.io`, `front.codes` | 18 | **Unacceptable** — user-content hosts referenced from a government portal |
| Laravel marketing domains | 8 | Dead scaffolding in `welcome.blade.php` |
| GoI hosts | 20 | Acceptable |

### Self-hosting also unblocks R-1

The preferred R-1 remediation — stripping UX4G's 1,668 `!important` declarations and substituting brand colours at vendor time — is **only possible if we control the artefact**. R-3 is therefore a prerequisite for the cleanest R-1 implementation, not an independent choice.

## 3.3 Options

| | Option A — CDN only | Option B — CDN + local fonts | Option C — Full self-host **(recommended)** |
|---|---|---|---|
| **Noto Sans loads** | **No** | Yes | Yes |
| **Gov icon sprites load** | **No** | No | Yes (once sourced) |
| **Survives CDN outage** | No | Partly | Yes |
| **GIGW third-party exposure** | High | High | **None** |
| **Enables `!important` strip (R-1)** | No | No | **Yes** |
| **Effort** | 0 h | ~16 h | **48 h** |
| **Verdict** | **Not viable** | Half-measure | **Recommended** |

## 3.4 Recommendation — Option C

**Self-host the complete frontend asset stack.** Target layout (blueprint §9.2):

```
public/
├── css/ux4g/
│   ├── ux4g-min.css            ← 269 KB, v2.0.8, pinned, !important stripped
│   ├── fonts/NotoSans-*.woff2  ← sourced from Google Fonts (Open Font License)
│   └── img/*-icons.svg         ← escalate to NeGD; see fallback below
├── css/ux4g-tokens.css         ← the ONLY file overriding brand colour (R-1)
└── js/ux4g/ux4g.min.js         ← 60 KB + Popper
```

Sourcing the missing assets:

| Asset | Source | Confidence |
|---|---|---|
| Noto Sans (woff2/woff/ttf) | Google Fonts — Open Font License, freely redistributable, identical family | **High** — direct substitute |
| 5 government icon sprite sheets | **No public source located.** Escalate to NeGD alongside the R-2 query. | **Needs external input** |

**Fallback if NeGD cannot supply the sprites:** the sprites appear to cover national/state/UT/social/common-government iconography. If unavailable, retain the existing icon systems for those cases and log it as an accepted deviation. **Do not block Sprint 1 on the sprites** — they affect decoration, not function. Track as an open item.

Fold into the same sprint (all already scoped in blueprint S0/S1):

1. Restore the **0-byte local jQuery** — a live production risk independent of UX4G.
2. Vendor DataTables responsive/buttons CSS, Bootstrap Icons, Summernote 0.8.18 (currently CDN-only despite being used in 67 places).
3. Remove `pinimg.com`, `codepen.io`, `wikimedia.org`, `front.codes` and the Laravel marketing references.
4. Add a CI check that fails the build on any new external host in a Blade file.

## 3.5 Decision required

> **R-3 — Resolved:** The complete frontend asset stack is self-hosted under `public/`. UX4G v2.0.8 is vendored with a recorded hash; Noto Sans is sourced from Google Fonts; the government icon sprites are requested from NeGD and tracked as an open item that does not block Sprint 1. All non-GoI CDN references are removed and a CI guard prevents reintroduction.

**Approver:** _________________  **Date:** __________

---

## 4. Consolidated impact on the plan

| Item | Blueprint said | Now | Δ |
|---|---|---|---|
| S1 Foundation | 104 h | 104 h | — |
| S2 Tokens | 88 h | **112 h** | +24 h — compiled override build (R-1) |
| S14 Accessibility | 104 h | **112 h** | +8 h — remediate 3 failing UX4G button variants |
| **Programme total (P50)** | **1,540 h** | **1,572 h** | **+32 h (+2 %)** |

The three decisions add ~2 % to the estimate. They do not change the critical path, the team size, or the ~5.2-month calendar.

## 5. New risks arising

| ID | Risk | Prob. | Impact | Mitigation |
|---|---|---|---|---|
| **RK-19** | UX4G hard-codes `#613AF5` 38× (5 with `!important`) and references `var(--bs-primary)` only once — token override is ineffective | **Confirmed** | **High** | Compiled vendor-time override (R-1); never assume a token change re-brands |
| **RK-20** | UX4G ships 1,668 of its own `!important` rules; naive `@layer ux4g, app` ordering makes them beat all project CSS | **Confirmed** | **High** | Strip at vendor time, or use `@layer app-important, ux4g, app` |
| **RK-21** | Three shipped UX4G button variants fail WCAG 2.1 AA (`.btn-info` 2.56:1, `.btn-success` 3.73:1, `.btn-warning` 3.86:1), affecting ~821 usages | **Confirmed** | **High** | Override in `ux4g-tokens.css` (S2); verify in S14 sweep |
| **RK-22** | Government icon sprites have no located public source | **Confirmed** | Medium | Escalate to NeGD; do not block Sprint 1; accept deviation if unavailable |

## 6. Open items requiring external input

| Item | Owner | Needed by |
|---|---|---|
| Confirm 2.0.8 is the supported web release; obtain v3.x distribution and migration path | NeGD / MeitY | Sprint 1 |
| Obtain the 5 government icon sprite sheets | NeGD / MeitY | Sprint 8 (not blocking S1) |
| Choose remediation route for `success`/`warning`/`info`: darken hue **or** switch to dark text | LBSNAA design + UX4G migration lead | Sprint 1 |
| Institutional sign-off on retaining navy as a documented design-system exception | LBSNAA management | **Before Sprint 1** |

---

**Document status:** All three decisions answered with measured evidence. Contrast figures computed with the WCAG 2.1 formula and validated against published reference pairs. CDN availability probed version by version on the date of writing. Items that could not be resolved from available sources are listed in §6 rather than estimated.

**Note on figures superseded:** an earlier draft of the blueprint quoted 11.4:1 for LBSNAA navy and 5.9:1 for UX4G violet. The correct measured values are **9.83:1** and **6.12:1**. The recommendation is unchanged — the navy remains the more accessible choice — and the blueprint has been corrected.
