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

const hasCreds = Boolean(process.env.E2E_USERNAME && process.env.E2E_PASSWORD);
const hasRoutes = fs.existsSync(ROUTES_FILE);

const routes = hasRoutes
    ? (JSON.parse(fs.readFileSync(ROUTES_FILE, 'utf8')).routes || [])
    : [];

// Without this guard the file would generate ZERO tests when routes.json is
// absent, and a run would report "passed" while capturing nothing at all.
test('S-4 preflight: routes.json exists and is non-empty', () => {
    expect(hasRoutes,
        'routes.json missing — run discover-routes.spec.js with E2E_DISCOVER=1 first').toBe(true);
    expect(routes.length,
        'routes.json contains no routes').toBeGreaterThan(0);
});

test.describe('S-4 visual baseline', () => {
    test.skip(!hasCreds, 'E2E_USERNAME/E2E_PASSWORD not provided');
    test.skip(!hasRoutes, 'routes.json missing — run discover-routes.spec.js with E2E_DISCOVER=1');
    test.skip(({}, testInfo) => testInfo.project.name !== 'chrome',
        'Baseline is pinned to chrome; cross-browser is a separate Phase 15 activity');

    // One login for the whole file — re-authenticating per route would triple
    // runtime and add a redirect that can race the screenshot.
    test.describe.configure({ mode: 'serial' });

    let page;

    test.beforeAll(async ({ browser, baseURL }) => {
        page = await browser.newPage();
        await login(page, baseURL);
    });

    test.afterAll(async () => {
        if (page) await page.close();
    });

    for (const route of routes) {
        test(`visual: ${route}`, async () => {
            test.setTimeout(90 * 1000);

            const response = await page.goto(route, {
                waitUntil: 'domcontentloaded',
                timeout: 45_000,
            });

            // A 5xx is a broken page, not a visual change — fail loudly and
            // separately so it is never mistaken for a styling regression.
            expect(response, `No response for ${route}`).not.toBeNull();
            expect(response.status(), `HTTP ${response.status()} at ${route}`).toBeLessThan(500);

            // Being bounced to login mid-run means the session dropped; that
            // would silently baseline 60 copies of the login page.
            expect(page.url(), `Session lost while loading ${route}`).not.toMatch(/\/login\b/i);

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
