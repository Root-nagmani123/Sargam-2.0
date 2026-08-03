/**
 * Stage 0 / S-4 — the UX4G visual-regression baseline.
 *
 * Captures a full-page screenshot of every route in routes.json. On the FIRST
 * run Playwright writes the baseline images; on every later run it diffs
 * against them and fails on visual change.
 *
 * This is the gate for the entire UX4G migration: Stage 0 steps S-1..S-6 and
 * Phase 1 must all produce ZERO diff here. Later phases will produce diffs
 * intentionally, and the baseline is then re-approved with --update-snapshots.
 *
 *   E2E_USERNAME=... E2E_PASSWORD=... \
 *     npx playwright test tests/e2e/visual/baseline.spec.js --project=chrome
 *
 *   # after an INTENDED visual change, review the diffs then:
 *   ... npx playwright test tests/e2e/visual/baseline.spec.js --project=chrome --update-snapshots
 */

const fs = require('fs');
const { test, expect } = require('@playwright/test');
const {
    login, stabilize, slugForRoute, masksFor,
    ROUTES_FILE, STABILIZE_CSS,
} = require('./_helpers');

const PUBLIC_ROUTES_FILE = require('path').join(__dirname, 'routes.public.json');

const hasCreds = Boolean(process.env.E2E_USERNAME && process.env.E2E_PASSWORD);

// Two modes:
//   AUTHENTICATED — credentials present -> log in, capture routes.json (494-page surface)
//   PUBLIC        — no credentials      -> no login, capture routes.public.json
// The public mode exists so the baseline is not blocked entirely on credentials;
// it covers fc.layouts.master and layouts.app, but NOT admin.layouts.master.
const MODE = hasCreds ? 'authenticated' : 'public';
const activeFile = hasCreds ? ROUTES_FILE : PUBLIC_ROUTES_FILE;
const hasRoutes = fs.existsSync(activeFile);

const routes = hasRoutes
    ? (JSON.parse(fs.readFileSync(activeFile, 'utf8')).routes || [])
    : [];

// Without this guard the file would generate ZERO tests when the route list is
// absent, and a run would report "passed" while capturing nothing at all.
test(`S-4 preflight [${MODE}]: route list exists and is non-empty`, () => {
    expect(hasRoutes,
        `${activeFile} missing` + (hasCreds
            ? ' — run discover-routes.spec.js with E2E_DISCOVER=1 first'
            : '')).toBe(true);
    expect(routes.length, 'route list contains no routes').toBeGreaterThan(0);
    if (!hasCreds) {
        console.log('\n  MODE: public (no E2E_USERNAME/E2E_PASSWORD).'
            + '\n  Covering fc.layouts.master + layouts.app only.'
            + '\n  The 494 admin pages still require credentials.\n');
    }
});

test.describe(`S-4 visual baseline [${MODE}]`, () => {
    test.skip(!hasRoutes, 'route list missing');

    // One login for the whole file (shared page from beforeAll) — re-authenticating
    // per route would triple runtime. NOT serial mode: serial skips every remaining
    // test after the first failure, which would hide most diffs during a gate run
    // where several intended changes are expected. workers:1 (playwright.config.js)
    // already runs these in order on the shared page; each test re-navigates first,
    // so a prior failure doesn't corrupt the next.

    let page;

    test.beforeAll(async ({ browser, baseURL }) => {
        page = await browser.newPage();
        // Public mode captures unauthenticated pages, so logging in would be wrong
        // as well as impossible — an authenticated session changes what they render.
        if (hasCreds) await login(page, baseURL);
    });

    test.afterAll(async () => {
        if (page) await page.close();
    });

    for (const route of routes) {
        test(`visual: ${route}`, async () => {
            // Pinned to chrome; cross-browser is a separate Phase 15 activity.
            // Checked here (not at describe level) because a describe-level
            // test.skip callback receives fixtures only — never testInfo.
            test.skip(test.info().project.name !== 'chrome', 'baseline runs on chrome');
            test.setTimeout(90 * 1000);

            const response = await page.goto(route, {
                waitUntil: 'domcontentloaded',
                timeout: 45_000,
            });

            // A 5xx is a broken page, not a visual change — fail loudly and
            // separately so it is never mistaken for a styling regression.
            expect(response, `No response for ${route}`).not.toBeNull();
            expect(response.status(), `HTTP ${response.status()} at ${route}`).toBeLessThan(500);

            // Authenticated mode only: being bounced to login mid-run means the
            // session dropped, which would silently baseline 60 copies of the
            // login page. In public mode some routes ARE login pages, legitimately.
            if (hasCreds) {
                expect(page.url(), `Session lost while loading ${route}`).not.toMatch(/\/login\b/i);
            }

            await stabilize(page);

            await expect(page).toHaveScreenshot(`${slugForRoute(route)}.png`, {
                fullPage: true,
                animations: 'disabled',
                caret: 'hide',
                scale: 'css',
                stylePath: STABILIZE_CSS,
                mask: masksFor(page),
                maxDiffPixelRatio: 0.001,   // ~0.1 % tolerance for AA/subpixel noise
                timeout: 30_000,
            });
        });
    }
});
