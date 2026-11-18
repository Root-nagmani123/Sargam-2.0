// Self-host a pinned UX4G release from the official CDN as ONE flattened stylesheet.
// Usage: node scripts/vendor-ux4g.mjs [version]   (default 3.3.2)
// Output: public/vendor/ux4g/<version>/{ux4g.css, ux4g-custom.js, assets/**, SOURCE.json}
//
// Deliberate deviations from the CDN index.css (see docs/UX4G-MIGRATION-REPORT.md):
//  - Unlayered: the CDN wraps UX4G in @layer, and unlayered Bootstrap/app CSS would then beat every
//    ux4g-* class. Loaded first + unlayered, ux4g-* classes win on specificity while legacy rules keep
//    winning ties, so unmigrated pages are unchanged.
//  - Skipped while Bootstrap coexists: reset.css (strips list bullets/outlines, makes every img block),
//    fonts.css (pulls ~20 Google families; SARGAM already loads Noto Sans + Devanagari),
//    focus.css (adds position:relative on every :focus-visible; SARGAM re-declares the same ring without it),
//    brand themes (Neel/Gul/Rekh; SARGAM uses its own primary scale).
//  - ux4g.js is not shipped: it binds [data-bs-toggle] and would double-toggle Bootstrap widgets.
//  - ux4g-custom.js is shipped but NOT loaded by any layout: it also opens Bootstrap `.modal`s on
//    data-bs-toggle="modal" (adds is-open + body overflow:hidden it never removes). Load it only on
//    pages that no longer use Bootstrap JS widgets.
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';

const version = process.argv[2] || '3.3.2';
const BASE = `https://cdn.ux4g.gov.in/UX4G@${version}/`;
const OUT = path.resolve('public/vendor/ux4g', version);
const ENTRY = [
    'tokens/ux4g-tokens.css',
    'semantics/ux4g-semantic.css',
    'themes/dark.css',
    'foundations/icons.css',
    'layout/ux4g.layout.css',
    'components/ux4g.components.css',
    'patterns/ux4g-patterns.css',
    'utilities/ux4g-utilities.css',
];
const SKIP = new Set(['reset/reset.css', 'foundations/fonts.css', 'foundations/focus.css', 'themes/brand-themes.css']);
const JS = ['ux4g-custom.js'];

const hashes = {};
async function fetchFile(rel) {
    const res = await fetch(BASE + rel);
    if (!res.ok) throw new Error(`${res.status} ${BASE + rel}`);
    const buf = Buffer.from(await res.arrayBuffer());
    hashes[rel] = 'sha256-' + crypto.createHash('sha256').update(buf).digest('base64');
    return buf;
}
function save(rel, buf) {
    const dest = path.join(OUT, rel);
    fs.mkdirSync(path.dirname(dest), { recursive: true });
    fs.writeFileSync(dest, buf);
}

const inlined = new Set();
const assets = new Set();
async function flatten(rel) {
    if (SKIP.has(rel) || inlined.has(rel)) return '';
    inlined.add(rel);
    const dir = path.posix.dirname(rel);
    let css = (await fetchFile(rel)).toString('utf8');
    // Rebase url() to the flattened file at OUT root; collect same-origin assets.
    css = css.replace(/url\(\s*(["']?)([^"')]+)\1\s*\)/g, (m, q, ref) => {
        if (/^(data:|#|%23|https?:)/.test(ref)) return m;
        const target = path.posix.normalize(path.posix.join(dir, ref.split(/[?#]/)[0]));
        assets.add(target);
        return `url("${target}")`;
    });
    const imports = [...css.matchAll(/@import\s+(?:url\()?\s*["']([^"']+)["']\s*\)?[^;]*;/g)];
    let body = css;
    let out = '';
    for (const [stmt, ref] of imports) {
        body = body.replace(stmt, '');
        if (/^https?:/.test(ref)) { console.warn(`dropped external @import in ${rel}: ${ref.slice(0, 60)}...`); continue; }
        out += await flatten(path.posix.normalize(path.posix.join(dir, ref)));
    }
    return out + `\n/* ---- ${rel} ---- */\n` + body;
}

fs.rmSync(OUT, { recursive: true, force: true });
let bundle = `/*! UX4G ${version} — official CDN ${BASE}, flattened for SARGAM by scripts/vendor-ux4g.mjs. MIT, NeGD/MeitY. */\n`;
for (const e of ENTRY) bundle += await flatten(e);
save('ux4g.css', Buffer.from(bundle));
for (const a of assets) {
    try { save(a, await fetchFile(a)); } catch (err) { console.warn('asset missing:', a, err.message); }
}
for (const j of JS) save(j, await fetchFile(j));
save('SOURCE.json', Buffer.from(JSON.stringify({ version, source: BASE, fetched: new Date().toISOString(), skipped: [...SKIP], files: hashes }, null, 2)));
console.log(`UX4G ${version}: ${inlined.size} css files -> ux4g.css (${bundle.length} bytes), ${assets.size} assets, ${JS.length} js`);
