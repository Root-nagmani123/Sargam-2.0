/**
 * Rasterizes a PDF to PNG page images so the output can actually be inspected.
 * Renders with pdf.js inside a headless Chromium page — no native deps.
 *
 * Requires:  npm i --no-save pdfjs-dist@4.0.379
 * Usage:     node scripts/rasterize-pdf.js <in.pdf> <outDir> [firstPage] [lastPage] [scale]
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const PDF = path.resolve(process.argv[2] || path.join(ROOT, 'docs', 'UX4G-Migration-Blueprint.pdf'));
const OUTDIR = path.resolve(process.argv[3] || path.join(ROOT, 'tmp_imports', 'pdf-proof'));
const FIRST = parseInt(process.argv[4] || '1', 10);
const LAST = parseInt(process.argv[5] || '0', 10);   // 0 = all
const SCALE = parseFloat(process.argv[6] || '1.5');

fs.mkdirSync(OUTDIR, { recursive: true });

const PDFJS = path.join(ROOT, 'node_modules', 'pdfjs-dist', 'legacy', 'build', 'pdf.mjs');
const WORKER = path.join(ROOT, 'node_modules', 'pdfjs-dist', 'legacy', 'build', 'pdf.worker.mjs');
for (const f of [PDF, PDFJS, WORKER]) {
    if (!fs.existsSync(f)) { console.error('missing: ' + f); process.exit(1); }
}

(async () => {
    const { chromium } = require(path.join(ROOT, 'node_modules', 'playwright-core'));

    let browser = null;
    for (const channel of ['chrome', 'msedge']) {
        try { browser = await chromium.launch({ channel, headless: true }); break; } catch (e) { }
    }
    if (!browser) browser = await chromium.launch({ headless: true });

    const page = await browser.newPage({ viewport: { width: 1400, height: 1000 } });
    page.on('console', m => { if (m.type() === 'error') console.log('  [page]', m.text()); });

    await page.setContent('<!doctype html><html><body style="margin:0"><div id="r"></div></body></html>');

    const pdfBase64 = fs.readFileSync(PDF).toString('base64');

    const total = await page.evaluate(async ({ pdfjsSrc, workerSrc, b64 }) => {
        const blob = new Blob([pdfjsSrc], { type: 'text/javascript' });
        const mod = await import(URL.createObjectURL(blob));
        mod.GlobalWorkerOptions.workerSrc =
            URL.createObjectURL(new Blob([workerSrc], { type: 'text/javascript' }));

        const bin = atob(b64);
        const bytes = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);

        window.__doc = await mod.getDocument({ data: bytes }).promise;
        return window.__doc.numPages;
    }, {
        pdfjsSrc: fs.readFileSync(PDFJS, 'utf8'),
        workerSrc: fs.readFileSync(WORKER, 'utf8'),
        b64: pdfBase64,
    });

    const last = LAST > 0 ? Math.min(LAST, total) : total;
    console.log(`pages: ${total} | rendering ${FIRST}..${last} @ ${SCALE}x -> ${OUTDIR}`);

    for (let n = FIRST; n <= last; n++) {
        const dataUrl = await page.evaluate(async ({ n, scale }) => {
            const pg = await window.__doc.getPage(n);
            const vp = pg.getViewport({ scale });
            const c = document.createElement('canvas');
            c.width = Math.ceil(vp.width); c.height = Math.ceil(vp.height);
            const ctx = c.getContext('2d');
            ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
            await pg.render({ canvasContext: ctx, viewport: vp }).promise;
            return c.toDataURL('image/png');
        }, { n, scale: SCALE });

        const file = path.join(OUTDIR, `page-${String(n).padStart(3, '0')}.png`);
        fs.writeFileSync(file, Buffer.from(dataUrl.split(',')[1], 'base64'));
        process.stdout.write(`  p${n}`);
    }
    console.log('\ndone');
    await browser.close();
})();
