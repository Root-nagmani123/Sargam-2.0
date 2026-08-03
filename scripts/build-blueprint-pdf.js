/**
 * Renders docs/UX4G-Migration-Blueprint.md to a print-ready A4 PDF.
 *
 * Pipeline:  markdown --marked--> HTML --Chrome(Playwright)--> PDF
 * Mermaid fences are rendered to real SVG in-page before the PDF is taken.
 *
 * Requires (not added to package.json on purpose — this is a docs tool, not app code):
 *     npm i --no-save marked@12 mermaid@10
 *
 * Usage:
 *     node scripts/build-blueprint-pdf.js
 *     node scripts/build-blueprint-pdf.js path/to/other.md path/to/out.pdf
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
// Resolve to absolute — the file:// navigation below cannot use relative paths.
const SRC = path.resolve(process.argv[2] || path.join(ROOT, 'docs', 'UX4G-Migration-Blueprint.md'));
const OUT = path.resolve(process.argv[3] || path.join(ROOT, 'docs', 'UX4G-Migration-Blueprint.pdf'));
const HTML_OUT = OUT.replace(/\.pdf$/i, '.html');

// ---------------------------------------------------------------- dependencies

function resolveOptional(name) {
    const candidates = [
        name,
        path.join(ROOT, 'node_modules', name),
        path.join(process.env.TEMP || '/tmp', 'mdbuild', 'node_modules', name),
    ];
    for (const c of candidates) {
        try { return require(c); } catch (e) { /* keep trying */ }
    }
    return null;
}

const markedMod = resolveOptional('marked');
if (!markedMod) {
    console.error('Missing "marked". Run:  npm i --no-save marked@12 mermaid@10');
    process.exit(1);
}
const marked = markedMod.marked || markedMod;

let mermaidSrc = null;
for (const p of [
    path.join(ROOT, 'node_modules', 'mermaid', 'dist', 'mermaid.min.js'),
    path.join(process.env.TEMP || '/tmp', 'mdbuild', 'node_modules', 'mermaid', 'dist', 'mermaid.min.js'),
]) {
    if (fs.existsSync(p)) { mermaidSrc = fs.readFileSync(p, 'utf8'); break; }
}

// ---------------------------------------------------------------- constants

const NAVY = '#004384';       // LBSNAA institutional navy
const NAVY_DEEP = '#00294f';
const ACCENT = '#b8860b';

const PRODUCT = 'Sargam 2.0';
const ORG = 'Lal Bahadur Shastri National Academy of Administration';

// Cover configuration per document, keyed by markdown basename.
const PROFILES = {
    'UX4G-Migration-Blueprint': {
        title: 'UX4G Design System Migration',
        sub: 'Technical Migration Blueprint',
        kpis: [
            ['853', 'Blade templates audited'],
            ['537', 'Page views in scope'],
            ['1,572 h', 'Estimated effort (P50)'],
            ['~5.2 mo', 'With 3 FE + 1 QA'],
        ],
        meta: [
            ['Prepared for', 'Sargam 2.0 development team &amp; management'],
            ['Scope', 'Frontend architecture only — no backend, route, API or schema change'],
            ['Target', 'UX4G Design System v2.0.8 (NeGD / MeitY)'],
            ['Audit basis', 'Working tree at HEAD <code>60715da05</code> + live UX4G CDN artefacts'],
        ],
        foot: 'Internal — for LBSNAA circulation. Every quantitative claim is measured, not estimated; ' +
              'unverified items are marked &ldquo;Needs Code Inspection&rdquo;.',
    },
    'UX4G-Decision-Note-R1-R2-R3': {
        title: 'UX4G Migration — Decision Note',
        sub: 'R-1 Brand Colour · R-2 Version Pinning · R-3 Self-Hosting',
        kpis: [
            ['9.83:1', 'LBSNAA navy contrast (AAA)'],
            ['v2.0.8', 'Newest version CDN serves'],
            ['3', 'UX4G buttons failing WCAG AA'],
            ['+2 %', 'Impact on programme estimate'],
        ],
        meta: [
            ['Prepared for', 'LBSNAA management &amp; UX4G migration steering group'],
            ['Companion to', '<code>docs/UX4G-Migration-Blueprint.md</code>'],
            ['Action required', 'A recorded decision on each of R-1, R-2 and R-3'],
            ['Blocks', 'Sprint 1 cannot start without R-1 and R-2'],
        ],
        foot: 'Internal — for LBSNAA circulation. Contrast ratios computed with the WCAG 2.1 formula and ' +
              'validated against published reference pairs; CDN availability probed version by version.',
    },
};

const PROFILE = PROFILES[path.basename(SRC, '.md')] || {
    title: path.basename(SRC, '.md').replace(/[-_]/g, ' '),
    sub: 'Technical Document', kpis: [], meta: [], foot: 'Internal — for LBSNAA circulation.',
};
const DOC_TITLE = PROFILE.title;
const DOC_SUB = PROFILE.sub;

// ---------------------------------------------------------------- markdown → html

const md = fs.readFileSync(SRC, 'utf8');

marked.setOptions({ gfm: true, breaks: false, mangle: false, headerIds: false });

const slug = (s) => s.toLowerCase().replace(/[^\w\s-]/g, '').trim().replace(/\s+/g, '-');

// Collect headings for the table of contents, and give each an id.
const toc = [];
const renderer = new marked.Renderer();
const baseCode = renderer.code.bind(renderer);

renderer.heading = function (text, level) {
    const plain = String(text).replace(/<[^>]+>/g, '').trim();
    const id = slug(plain);
    if (level === 2 || level === 3) toc.push({ level, text: plain, id });
    const cls = level === 2 ? ' class="sec"' : '';
    return `<h${level} id="${id}"${cls}>${text}</h${level}>\n`;
};

// Mermaid fences pass through as <pre class="mermaid"> for in-page rendering.
renderer.code = function (code, lang, escaped) {
    if ((lang || '').trim() === 'mermaid') {
        return `<div class="figure"><pre class="mermaid">${code}</pre></div>\n`;
    }
    return baseCode(code, lang, escaped);
};

let body = marked.parse(md, { renderer });

// Widen the font-shrink for tables with many columns so they fit A4 portrait.
body = body.replace(/<table>([\s\S]*?)<\/table>/g, (whole, inner) => {
    const headRow = (inner.match(/<thead>[\s\S]*?<\/thead>/) || [''])[0];
    const cols = (headRow.match(/<th[\s>]/g) || []).length;
    let cls = 'c-sm';
    if (cols >= 11) cls = 'c-xl';
    else if (cols >= 8) cls = 'c-lg';
    else if (cols >= 6) cls = 'c-md';
    return `<div class="tw"><table class="${cls}" data-cols="${cols}">${inner}</table></div>`;
});

// Drop the first H1 (the cover page carries it) and its immediate meta lines.
body = body.replace(/^\s*<h1[^>]*>[\s\S]*?<\/h1>\s*/, '');

// ---------------------------------------------------------------- toc html

const tocHtml = toc.map(t => {
    const m = t.text.match(/^(\d+(?:\.\d+)?)[.\s]+(.*)$/);
    const num = m ? m[1] : '';
    const label = m ? m[2] : t.text;
    return `<li class="l${t.level}"><a href="#${t.id}"><span class="n">${num}</span><span class="t">${label}</span></a></li>`;
}).join('\n');

// ---------------------------------------------------------------- page shell

const today = new Date().toISOString().slice(0, 10);

const html = `<!doctype html>
<html lang="en"><head><meta charset="utf-8">
<title>${PRODUCT} — ${DOC_TITLE}</title>
<style>
:root{ --navy:${NAVY}; --navy-deep:${NAVY_DEEP}; --accent:${ACCENT};
       --ink:#12181f; --muted:#5b6672; --line:#d7dde4; --bg-soft:#f5f7f9; }

@page { size: A4; margin: 17mm 13mm 16mm 13mm; }
@page :first { margin: 0; }

*{ box-sizing:border-box; }
html,body{ margin:0; padding:0; }
body{
  font-family:"Noto Sans","Segoe UI",system-ui,-apple-system,Arial,sans-serif;
  font-size:9.1pt; line-height:1.5; color:var(--ink);
  -webkit-print-color-adjust:exact; print-color-adjust:exact;
}

/* ---------------------------------------------------------- cover */
.cover{
  page-break-after:always; break-after:page;
  height:297mm; width:210mm; position:relative; overflow:hidden;
  background:linear-gradient(155deg,var(--navy-deep) 0%,var(--navy) 48%,#0a5a9e 100%);
  color:#fff; padding:26mm 20mm 18mm;
  display:flex; flex-direction:column;
}
/* The global heading rule paints headings navy; the cover must override it or
   the title renders navy-on-navy and is effectively invisible. */
.cover h1,.cover h2,.cover .product{ color:#fff; }
.cover .product{ color:#ffd97a; }
.cover code{ background:rgba(255,255,255,.14); border-color:rgba(255,255,255,.28); color:#dbeafe; }
.cover::after{
  content:""; position:absolute; right:-60mm; bottom:-70mm;
  width:180mm; height:180mm; border-radius:50%;
  background:radial-gradient(circle,rgba(255,255,255,.10) 0%,rgba(255,255,255,0) 68%);
}
.cover .org{ font-size:9pt; letter-spacing:.16em; text-transform:uppercase; opacity:.82; }
.cover .rule{ width:26mm; height:3px; background:var(--accent); margin:9mm 0 8mm; }
.cover h1{ font-size:33pt; line-height:1.1; font-weight:700; margin:0; letter-spacing:-.01em; }
.cover h2{ font-size:15pt; font-weight:400; margin:5mm 0 0; opacity:.9; }
.cover .product{ font-size:11pt; letter-spacing:.22em; text-transform:uppercase;
                 margin-top:12mm; color:#ffd97a; font-weight:600; }
.cover .spacer{ flex:1; }
.cover .kpis{ display:flex; gap:5mm; margin-bottom:11mm; position:relative; z-index:2; }
.cover .kpi{ flex:1; background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.20);
             border-radius:3px; padding:4mm 4mm 4.5mm; }
.cover .kpi b{ display:block; font-size:17pt; line-height:1.15; }
.cover .kpi span{ font-size:7.6pt; opacity:.85; letter-spacing:.04em; }
.cover .meta{ position:relative; z-index:2; border-top:1px solid rgba(255,255,255,.28);
              padding-top:6mm; font-size:8.4pt; display:grid;
              grid-template-columns:auto 1fr; gap:2mm 8mm; }
.cover .meta dt{ opacity:.7; letter-spacing:.05em; text-transform:uppercase; font-size:7.2pt; }
.cover .meta dd{ margin:0; }
.cover .class{ margin-top:7mm; font-size:7.6pt; opacity:.7; }

/* ---------------------------------------------------------- toc */
.toc{ page-break-after:always; break-after:page; }
.toc h2{ font-size:16pt; color:var(--navy); border-bottom:2px solid var(--navy);
         padding-bottom:2.5mm; margin:0 0 6mm; }
.toc ul{ list-style:none; margin:0; padding:0; }
.toc li{ margin:0; }
.toc a{ text-decoration:none; color:var(--ink); display:flex; gap:4mm;
        padding:1.3mm 0; border-bottom:1px dotted #dde3e9; }
.toc .l2 a{ font-weight:650; color:var(--navy); font-size:9.6pt; padding-top:2.6mm; }
.toc .l3 a{ padding-left:9mm; font-size:8.5pt; color:#3d4855; border-bottom:none; }
.toc .n{ min-width:11mm; color:var(--accent); font-weight:700; font-variant-numeric:tabular-nums; }
.toc .l3 .n{ min-width:13mm; color:var(--muted); font-weight:600; }

/* ---------------------------------------------------------- headings */
h1,h2,h3,h4,h5{ font-weight:700; color:var(--navy); line-height:1.25;
                break-after:avoid; page-break-after:avoid; }
h2.sec{ font-size:15pt; margin:7mm 0 5mm; padding:0 0 2.5mm;
        border-bottom:2px solid var(--navy); }
/* Two pagination modes, chosen per document:
   .sections — no H1 dividers, so each H2 is a major section and starts a page
               (the blueprint's 18 sections).
   .parts    — H1s are part dividers (R-1 / R-2 / R-3); only those start a page
               and H2s flow, otherwise short subsections waste most of a page. */
main.sections h2.sec{ break-before:page; page-break-before:always; }
main.sections h2.sec:first-child{ break-before:auto; page-break-before:auto; }
main.parts h1{ font-size:20pt; margin:0 0 6mm; padding:0 0 3mm;
               border-bottom:3px solid var(--navy);
               break-before:page; page-break-before:always; }
main.parts h1:first-child{ break-before:auto; page-break-before:auto; }
main.parts h1 + h2.sec{ margin-top:0; }
h3{ font-size:11pt; margin:7mm 0 2.5mm; color:var(--navy-deep); }
h4{ font-size:9.6pt; margin:5mm 0 2mm; color:#26405c; }
p{ margin:0 0 2.8mm; orphans:3; widows:3; }
strong{ font-weight:650; }
hr{ border:none; border-top:1px solid var(--line); margin:7mm 0; }
a{ color:var(--navy); }

ul,ol{ margin:0 0 3mm; padding-left:5.5mm; }
li{ margin:0 0 1.2mm; }

/* ---------------------------------------------------------- tables */
.tw{ margin:0 0 4.5mm; break-inside:auto; }
table{ width:100%; border-collapse:collapse; table-layout:auto; }
thead{ display:table-header-group; }
tr{ break-inside:avoid; page-break-inside:avoid; }
th{ background:var(--navy); color:#fff; text-align:left; font-weight:600;
    padding:1.7mm 2mm; border:1px solid var(--navy); line-height:1.3;
    vertical-align:bottom; }
td{ padding:1.5mm 2mm; border:1px solid var(--line); vertical-align:top; line-height:1.36; }
tbody tr:nth-child(even) td{ background:var(--bg-soft); }
table.c-sm{ font-size:8.5pt; }
table.c-md{ font-size:7.9pt; }
table.c-lg{ font-size:7.2pt; }
table.c-xl{ font-size:6.5pt; }
table.c-lg th,table.c-lg td,table.c-xl th,table.c-xl td{ padding:1.1mm 1.3mm; }
td code,th code{ font-size:.93em; }

/* ---------------------------------------------------------- code + quotes */
code{ font-family:"Cascadia Mono",Consolas,"Courier New",monospace;
      background:#eef2f6; border:1px solid #e0e6ec; border-radius:2px;
      padding:.3mm 1mm; font-size:8.1pt; color:#0f3d63; }
pre{ background:#f7f9fb; border:1px solid var(--line); border-left:3px solid var(--navy);
     border-radius:2px; padding:3mm 3.5mm; overflow:visible; white-space:pre;
     font-size:6.9pt; line-height:1.34; margin:0 0 4.5mm; break-inside:avoid; }
pre code{ background:none; border:none; padding:0; font-size:inherit; color:#1a2733; }

blockquote{ margin:0 0 4.5mm; padding:3mm 4mm; background:#fff8e6;
            border:1px solid #f0dfae; border-left:3px solid var(--accent);
            border-radius:2px; break-inside:avoid; }
blockquote p:last-child{ margin-bottom:0; }
blockquote strong{ color:#7a5a00; }

/* ---------------------------------------------------------- figures */
.figure{ break-inside:avoid; page-break-inside:avoid; text-align:center;
         margin:0 0 5mm; padding:4mm; border:1px solid var(--line);
         border-radius:3px; background:#fcfdfe; }
/* Mermaid stamps an inline max-width on the <svg>, which leaves the diagram
   rendering at intrinsic size (tiny) inside a full-width figure. Force it wide. */
.figure svg{ width:100% !important; max-width:100% !important; height:auto; }
.mermaid{ background:none; border:none; padding:0; margin:0; }
</style></head>
<body>

<section class="cover">
  <div class="org">${ORG}</div>
  <div class="rule"></div>
  <h1>${DOC_TITLE}</h1>
  <h2>${DOC_SUB}</h2>
  <div class="product">${PRODUCT}</div>

  <div class="spacer"></div>

  <div class="kpis">
${PROFILE.kpis.map(([b, s]) => `    <div class="kpi"><b>${b}</b><span>${s}</span></div>`).join('\n')}
  </div>

  <dl class="meta">
${PROFILE.meta.map(([k, v]) => `    <dt>${k}</dt><dd>${v}</dd>`).join('\n')}
    <dt>Date</dt><dd>${today}</dd>
  </dl>

  <div class="class">${PROFILE.foot}</div>
</section>

<nav class="toc">
  <h2>Contents</h2>
  <ul>
${tocHtml}
  </ul>
</nav>

<main class="${/<h1[ >]/.test(body) ? 'parts' : 'sections'}">
${body}
</main>

${mermaidSrc ? `<script>${mermaidSrc}</script>
<script>
  // Un-escape the entity-encoded fence content marked produced, then render.
  document.querySelectorAll('pre.mermaid').forEach(function (el) {
    var ta = document.createElement('textarea');
    ta.innerHTML = el.innerHTML;
    el.textContent = ta.value;
  });
  window.__mermaidDone = false;
  mermaid.initialize({
    startOnLoad: false, theme: 'base', securityLevel: 'loose',
    flowchart: { htmlLabels: true, curve: 'basis', nodeSpacing: 34, rankSpacing: 58,
                 useMaxWidth: false, padding: 10 },
    themeVariables: {
      fontFamily: '"Noto Sans","Segoe UI",sans-serif', fontSize: '15px',
      primaryColor: '#eaf2fb', primaryTextColor: '#12181f', primaryBorderColor: '${NAVY}',
      lineColor: '#5b6672', secondaryColor: '#f5f7f9', tertiaryColor: '#fff'
    }
  });
  mermaid.run({ querySelector: 'pre.mermaid' })
    .then(function(){ window.__mermaidDone = true; })
    .catch(function(e){ console.error('mermaid', e); window.__mermaidDone = true; });
</script>` : '<!-- mermaid unavailable: diagram left as source -->'}

</body></html>`;

fs.writeFileSync(HTML_OUT, html, 'utf8');
console.log(`HTML  -> ${HTML_OUT}  (${(html.length / 1024).toFixed(0)} KB, ${toc.length} TOC entries)`);

// ---------------------------------------------------------------- html → pdf

(async () => {
    const { chromium } = require(path.join(ROOT, 'node_modules', 'playwright-core'));

    let browser = null;
    for (const channel of ['chrome', 'msedge']) {
        try { browser = await chromium.launch({ channel, headless: true }); break; }
        catch (e) { console.log(`  (channel "${channel}" unavailable)`); }
    }
    if (!browser) {
        try { browser = await chromium.launch({ headless: true }); }
        catch (e) {
            console.error('No Chromium-based browser found. Install Chrome/Edge, or run: npx playwright install chromium');
            process.exit(1);
        }
    }

    const page = await browser.newPage();
    await page.goto('file:///' + HTML_OUT.replace(/\\/g, '/'), { waitUntil: 'load' });

    if (mermaidSrc) {
        await page.waitForFunction('window.__mermaidDone === true', null, { timeout: 60000 })
            .catch(() => console.warn('  ! mermaid render timed out; diagram may be missing'));
        // Strip mermaid's inline max-width so the SVG scales to the figure width.
        await page.evaluate(() => {
            document.querySelectorAll('.figure svg').forEach(s => {
                s.style.maxWidth = '100%'; s.style.width = '100%'; s.removeAttribute('width');
            });
        });
    }
    await page.emulateMedia({ media: 'print' });
    await page.waitForTimeout(600);

    // The cover must be full-bleed (no page margins, no running header/footer),
    // while the body needs both. Chrome cannot vary that within one print job, so
    // render two passes and stitch them. Cover stays unnumbered, body numbers from 1.
    const tmpCover = OUT.replace(/\.pdf$/i, '.__cover.pdf');
    const tmpBody = OUT.replace(/\.pdf$/i, '.__body.pdf');

    const setPass = (which) => page.evaluate((w) => {
        let s = document.getElementById('__pass');
        if (!s) { s = document.createElement('style'); s.id = '__pass'; document.head.appendChild(s); }
        s.textContent = w === 'cover'
            ? '@page{size:A4;margin:0!important} nav.toc,main{display:none!important}'
            : '@page{size:A4;margin:17mm 13mm 16mm} section.cover{display:none!important}';
    }, which);

    await setPass('cover');
    await page.pdf({ path: tmpCover, format: 'A4', printBackground: true, preferCSSPageSize: true,
                     margin: { top: 0, bottom: 0, left: 0, right: 0 } });

    const bar = `border-top:.5px solid #d7dde4;width:100%;margin:0 13mm;padding-top:2mm;
                 font-family:'Segoe UI',sans-serif;font-size:6.6pt;color:#7a8794;
                 display:flex;justify-content:space-between;`;

    await setPass('body');
    await page.pdf({
        path: tmpBody,
        format: 'A4',
        printBackground: true,
        displayHeaderFooter: true,
        headerTemplate: `<div style="${bar}border-top:none;border-bottom:.5px solid #d7dde4;padding:0 0 1.5mm;">
            <span>${PRODUCT} &nbsp;·&nbsp; ${DOC_TITLE}</span>
            <span>${DOC_SUB}</span></div>`,
        footerTemplate: `<div style="${bar}">
            <span>${ORG} &nbsp;·&nbsp; Internal</span>
            <span>Page <span class="pageNumber"></span> of <span class="totalPages"></span></span></div>`,
        margin: { top: '20mm', bottom: '16mm', left: '13mm', right: '13mm' },
    });

    await browser.close();

    // ------------------------------------------------------------ stitch
    const { PDFDocument } = require(path.join(ROOT, 'node_modules', 'pdf-lib'));
    const out = await PDFDocument.create();
    for (const f of [tmpCover, tmpBody]) {
        const src = await PDFDocument.load(fs.readFileSync(f));
        const pages = await out.copyPages(src, src.getPageIndices());
        pages.forEach(p => out.addPage(p));
    }
    out.setTitle(`${PRODUCT} — ${DOC_TITLE}: ${DOC_SUB}`);
    out.setSubject('Frontend migration blueprint: Bootstrap 5.3 → UX4G Design System v2.0.8');
    out.setAuthor(ORG);
    out.setCreator('scripts/build-blueprint-pdf.js');
    out.setKeywords(['UX4G', 'Bootstrap', 'Laravel', 'Sargam 2.0', 'GIGW', 'WCAG 2.1 AA', 'LBSNAA']);
    fs.writeFileSync(OUT, await out.save());
    for (const f of [tmpCover, tmpBody]) { try { fs.unlinkSync(f); } catch (e) { } }

    const kb = (fs.statSync(OUT).size / 1024).toFixed(0);
    console.log(`PDF   -> ${OUT}  (${kb} KB, ${out.getPageCount()} pages)`);
})();
