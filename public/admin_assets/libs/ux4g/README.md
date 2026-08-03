# UX4G Design System — self-hosted vendor bundle

**Version:** UX4G v2.0.8 (pinned) — the newest version `cdn.ux4g.gov.in` actually serves.
**Source:** NeGD / MeitY · https://doc.ux4g.gov.in · MIT licensed.
**Vendored:** Phase 1 of the UX4G migration. Do NOT edit these files — brand/behaviour
overrides live in `resources/scss/overrides/` and compile to `public/css/`.

## Why self-hosted (not CDN)

The migration is CDN-free (GIGW). Critically, UX4G's own CDN does **not** serve the
assets its stylesheet references — every `../fonts/NotoSans-*` and `../img/*-icons.svg`
URL returns 404 at every published version. Self-hosting is therefore mandatory, not a
preference. See `docs/UX4G-Decision-Note-R1-R2-R3.md` (R-2, R-3).

## Contents & integrity (sha256)

| File | Source | sha256 |
|---|---|---|
| `css/ux4g-min.css` | cdn.ux4g.gov.in/UX4G@2.0.8 | `04196aebdfdc22b5b57f614eebf3352f4de10dc2c0dad81cb1afa91143a285f8` |
| `js/ux4g.min.js` | cdn.ux4g.gov.in/UX4G@2.0.8 | `26d8d8b5265d4af2add1d70e0680334dc2c28d282304173e206aa904599bc579` |
| `js/popper.min.js` | @popperjs/core 2.11.8 (node_modules) | `c212f4b505a86352aed62b24a8f16f999f821ecbe6456c7f3c8a04bc87968782` |
| `fonts/NotoSans-Regular.woff2` | Google Fonts (Noto Sans v42, **Latin subset**) | `09aee8065d25508f23a4c3d92cd777ac869c52d93fd868a88f025d888a7937d6` |

UX4G is served only from its official government CDN, so no second-origin mirror exists
for cross-verification; the hashes above pin exactly what was vendored.

## Known gaps — resolve before the assets go live (Phase 2 typography / gov-icons)

1. **Noto Sans is the Latin subset only** (~13 KB). It covers the portal's English UI
   (the Devanagari in the header is a raster logo, not text). If any page renders live
   Devanagari/other-script text, source the full multi-script `NotoSans-Regular.woff2`
   from NeGD's real distribution or notofonts.github.io and replace the file here — the
   filename must stay identical so `ux4g-min.css`'s `@font-face` keeps resolving.
   `ux4g-min.css` references `.woff` and `.ttf` fallbacks too, but browsers stop at the
   first supported format (woff2), so those are never fetched.
2. **The 5 gov-icon sprites are empty placeholders.** `cdn.ux4g.gov.in` 404s them at
   every version and no public source was found. They exist here only to prevent console
   404s if `ux4g-min.css` loads. `ux4g` gov-icon / state / UT / country / social icon
   classes will render nothing until the real sprites are obtained from NeGD.

## Popper

`ux4g.min.js` is the non-bundled build — it expects Popper as an external global
(`window.Popper`), exactly like Bootstrap's non-bundle. `js/popper.min.js` provides it.
Load Popper before `ux4g.min.js`. (UX4G JS is vendored but NOT yet loaded by any page in
Phase 1 — that happens in the interactive-components phase.)
