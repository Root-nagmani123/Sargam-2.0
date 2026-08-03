/**
 * Stage 0 / S-4 — route discovery for the visual-regression baseline.
 *
 * Logs in, harvests every navigable route from the RBAC sidebar (including the
 * hidden tab panes, which hold most of the menu tree), then writes a
 * DETERMINISTIC, committed route list to routes.json.
 *
 * Why discovery instead of a hand-written list: the sidebar is database-driven
 * (SidebarNavResolver + MenuRouteMatcher), so a hand-written list would drift
 * from what users can actually reach. Why a committed file instead of crawling
 * at capture time: the baseline must screenshot the SAME routes every run.
 *
 * Guarded behind E2E_DISCOVER=1 so a normal test run can never silently
 * regenerate the route list the baseline is pinned to.
 *
 *   E2E_DISCOVER=1 E2E_USERNAME=... E2E_PASSWORD=... \
 *     npx playwright test tests/e2e/visual/discover-routes.spec.js --project=chrome
 */

const fs = require('fs');
const { test, expect } = require('@playwright/test');
const { login, ROUTES_FILE } = require('./_helpers');

const MAX_ROUTES = parseInt(process.env.E2E_MAX_ROUTES || '60', 10);

// Never screenshot these — they mutate state, end the session, or are
// inherently non-deterministic visual fixtures:
//  - logout/delete/… : state-changing or session-ending
//  - export/download/print/pdf/… : file responses, not pages
//  - birthday-wishes : "Today's Birthdays" is a variable-length, date-dependent
//    list; its cards change height/count run-to-run, so even a per-card mask
//    leaves boundary diffs. Its chrome (header/nav/two-column layout) is already
//    covered by the other pages that share the admin layout.
//  - calendar : FullCalendar renders asynchronously (a "Loading…" state swaps to
//    a grid on a timer) AND shows date-dependent events + a "today" highlight, so
//    it is non-deterministic even between two same-minute runs. Not a stable
//    pixel fixture; its chrome is covered by the other admin pages.
const EXCLUDE = /logout|signout|delete|destroy|remove|export|download|print|pdf|excel|csv|birthday|calendar|\.(pdf|xlsx|csv|zip)$/i;

test.describe('S-4 route discovery', () => {
    test.skip(!process.env.E2E_DISCOVER, 'Set E2E_DISCOVER=1 to regenerate routes.json');

    test('harvest sidebar routes into routes.json', async ({ page, baseURL }) => {
        // Checked here, not at describe level: a describe-level test.skip callback
        // receives fixtures only, never testInfo.
        test.skip(test.info().project.name !== 'chrome', 'Discovery runs once, on chrome');
        test.setTimeout(300 * 1000);

        const landing = await login(page, baseURL);
        const origin = new URL(page.url()).origin;

        // Candidate routes come from the DB-driven sidebar (menus table), committed
        // as routes.candidates.json. The dashboard DOM alone is not enough — the
        // RBAC menu tree is built by JS/AJAX, so scraping <a href> yields almost
        // nothing. The DB list is the authoritative surface. DOM anchors on the
        // landing page are folded in as a supplement.
        const CANDIDATES_FILE = require('path').join(__dirname, 'routes.candidates.json');
        let candidates = [];
        if (fs.existsSync(CANDIDATES_FILE)) {
            candidates = JSON.parse(fs.readFileSync(CANDIDATES_FILE, 'utf8')).routes || [];
        }
        const domHrefs = await page.evaluate((o) =>
            Array.from(document.querySelectorAll('a[href]')).map((a) => a.href)
                .filter((h) => h && h.startsWith(o) && !h.includes('#') && !h.startsWith('javascript:'))
                .map((h) => new URL(h).pathname), origin);

        const cleaned = Array.from(new Set(
            [...candidates, ...domHrefs]
                // Strip stray control chars (DOM hrefs can carry a trailing \r from
                // the source HTML) and any trailing slash, so /x, /x\r and /x/ are
                // one route — otherwise they slip past Set and collide at slug time.
                .map((p) => p.replace(/[\r\n\t]/g, '').replace(/\/+$/, '') || '/')
        ))
            .filter((p) => p && p !== '/')
            .filter((p) => !EXCLUDE.test(p))
            // Skip routes with numeric ids — record-specific pages are unstable
            // baselines (the record may be edited or deleted between runs). The
            // resolver emits /edit/0 style placeholders; those are fine.
            .filter((p) => !/\/[1-9]\d{1,}(\/|$)/.test(p))
            .sort();

        expect(cleaned.length, 'No candidate routes — is routes.candidates.json present and the account permissioned?')
            .toBeGreaterThan(0);

        // Build a module-spread ORDER first (round-robin across first-path-segment),
        // THEN verify in that order and stop at MAX_ROUTES. Verifying all 213 would
        // blow the timeout; verifying breadth-first means our ~60 confirmed routes
        // sample many modules instead of clustering in whichever sorts first.
        const byModule = new Map();
        for (const p of cleaned) {
            const mod = p.split('/').filter(Boolean)[0] || 'root';
            if (!byModule.has(mod)) byModule.set(mod, []);
            byModule.get(mod).push(p);
        }
        const modules = Array.from(byModule.keys()).sort();
        const order = [];
        for (let depth = 0; order.length < cleaned.length; depth++) {
            let added = false;
            for (const mod of modules) {
                const list = byModule.get(mod);
                if (depth < list.length) { order.push(list[depth]); added = true; }
            }
            if (!added) break;
        }

        // Verify each candidate renders (2xx/3xx, not bounced to login) BEFORE it can
        // enter the baseline — baselining an error page is worse than omitting it.
        // waitUntil:'commit' fires on first response byte: we only need the status
        // here; full render + stabilisation happens later in baseline.spec.js.
        const verified = [];
        for (const path of order) {
            if (verified.length >= MAX_ROUTES) break;
            try {
                const resp = await page.goto(path, { waitUntil: 'commit', timeout: 20_000 });
                const status = resp ? resp.status() : 0;
                const bounced = /\/login\b/i.test(page.url());
                if (status >= 200 && status < 400 && !bounced) verified.push(path);
                else console.log(`  skip ${path} (status=${status}${bounced ? ', bounced' : ''})`);
            } catch (e) {
                console.log(`  skip ${path} (${e.message.split('\n')[0]})`);
            }
        }
        expect(verified.length, 'No candidate route rendered 200 while authenticated').toBeGreaterThan(0);

        const verifiedModules = new Set(verified.map((p) => p.split('/').filter(Boolean)[0] || 'root'));

        // Collision guard: Playwright re-sanitises the screenshot name to roughly
        // [A-Za-z0-9-], so two DIFFERENT routes can collapse to the SAME snapshot
        // file — the second silently overwrites the first, and both tests then pass
        // against one image (silent under-coverage). Fail loudly here instead.
        const sanitize = (p) => p.replace(/[^A-Za-z0-9]+/g, '-').replace(/^-|-$/g, '');
        const byFile = new Map();
        for (const r of verified) {
            const f = sanitize(r);
            if (byFile.has(f)) {
                throw new Error(`Snapshot-name collision: "${byFile.get(f)}" and "${r}" both map to "${f}". `
                    + `Adjust the route set or the naming scheme.`);
            }
            byFile.set(f, r);
        }

        // The landing/dashboard page is the most-viewed screen; pin it first,
        // de-duplicated (it is usually also in the verified list).
        const landingPath = new URL(landing).pathname;
        const ordered = Array.from(new Set([landingPath, ...verified])).slice(0, MAX_ROUTES);

        const payload = {
            _comment: 'Generated by discover-routes.spec.js (Stage 0 / S-4). '
                + 'Committed so the visual baseline pins the SAME routes every run. '
                + 'Regenerate with E2E_DISCOVER=1.',
            generatedFrom: origin,
            candidates: cleaned.length,
            verified: verified.length,
            moduleCount: verifiedModules.size,
            captured: ordered.length,
            routes: ordered,
        };

        fs.writeFileSync(ROUTES_FILE, JSON.stringify(payload, null, 2) + '\n', 'utf8');

        console.log(`\n  candidates=${cleaned.length}  verified=${verified.length}  `
            + `modules=${verifiedModules.size}  captured=${ordered.length} -> ${ROUTES_FILE}\n`);
    });
});
