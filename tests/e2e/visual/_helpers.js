/**
 * Shared helpers for the UX4G visual-regression baseline (Stage 0, item S-4).
 *
 * These helpers touch NO application code. They exist so that a screenshot
 * diff means "the UI changed", not "the page rendered at a different moment".
 *
 * Credentials follow the convention already established by tests/e2e/erp-smoke.spec.js:
 *     E2E_BASE_URL   (default http://localhost:8000)
 *     E2E_USERNAME
 *     E2E_PASSWORD
 */

const path = require('path');

const VISUAL_DIR = __dirname;
const ROUTES_FILE = path.join(VISUAL_DIR, 'routes.json');
const STABILIZE_CSS = path.join(VISUAL_DIR, 'stabilize.css');

/** Regions whose CONTENT is legitimately volatile. Masked (painted over) rather
 *  than hidden, so layout/box-model regressions are still caught. */
const MASK_SELECTORS = [
    '.dataTables_info',            // "Showing 1 to 10 of 4,231 entries" — row counts drift
    '#datatable_paginate',
    '[data-testid="live-region"]',
    '.js-server-time',
    // mews/captcha renders a fresh random image on EVERY request (verified on
    // /registration/fc-auth). Masked, not hidden, so the box still occupies its
    // real space and a layout regression around it is still caught.
    '#captchaImage',
    'img[src*="/captcha/"]',
    // Dashboard header shows a live clock (#dashboard-live-time, H:i — changes every
    // minute) and today's date next to it (changes daily). Both are volatile and
    // would fail any run that crosses a minute/day boundary from capture time.
    '#dashboard-live-time',
    // "Today's Birthdays" cards (birthday-wishes page + dashboard panel) list whoever
    // has a birthday today — date-dependent data that changes day to day.
    '.birthday-person-card',
];

/**
 * Log in through the real form. Deliberately uses the visible controls and the
 * page's own submit handler so any client-side transform still runs.
 */
async function login(page, baseURL) {
    const username = process.env.E2E_USERNAME;
    const password = process.env.E2E_PASSWORD;
    if (!username || !password) {
        throw new Error('E2E_USERNAME / E2E_PASSWORD are required for authenticated capture');
    }

    await page.goto(baseURL || '/', { waitUntil: 'domcontentloaded' });

    await page.locator('input[name="username"], input[name="email"], input[type="email"]')
        .first().fill(username);
    await page.locator('input[name="password"], input[type="password"]')
        .first().fill(password);

    await Promise.all([
        page.waitForLoadState('networkidle').catch(() => {}),
        page.locator('button[type="submit"], input[type="submit"], button:has-text("Login")')
            .first().click(),
    ]);

    // Give the post-login redirect a chance to settle before asserting.
    await page.waitForTimeout(1200);

    if (/\/login\b/i.test(page.url())) {
        throw new Error(`Login failed — still on ${page.url()}. Check E2E_USERNAME / E2E_PASSWORD.`);
    }
    return page.url();
}

/**
 * Bring a loaded page to a deterministic visual resting state.
 * Order matters: fonts before lazy-images before settle.
 */
async function stabilize(page) {
    // Web fonts reflow text when they land mid-screenshot.
    await page.evaluate(() => document.fonts && document.fonts.ready).catch(() => {});

    // Trigger lazy-loaded images/sections, then return to top so the scroll
    // offset is identical on every run.
    await page.evaluate(async () => {
        const step = window.innerHeight;
        const max = document.body.scrollHeight;
        for (let y = 0; y < max; y += step) {
            window.scrollTo(0, y);
            await new Promise((r) => setTimeout(r, 40));
        }
        window.scrollTo(0, 0);
    }).catch(() => {});

    // Let DataTables finish its draw and any XHR-driven widget settle.
    await page.waitForLoadState('networkidle').catch(() => {});

    // Wait out async widgets that render AFTER networkidle (e.g. FullCalendar shows
    // "Loading calendar..." then swaps in a grid on a timer). Screenshotting mid-load
    // produces a flaky, layout-shifted capture. Best-effort, capped so a page with a
    // genuinely persistent "Loading" label can't hang the run.
    await page.waitForFunction(() => {
        const loading = Array.from(document.querySelectorAll('body *')).some((el) => {
            if (el.children.length) return false;
            const t = (el.textContent || '').trim().toLowerCase();
            return /loading[.…]*$/.test(t) && el.offsetParent !== null;
        });
        const fcRendering = document.querySelector('.fc:not(.fc-media-screen), .fc-view-harness:empty');
        return !loading && !fcRendering;
    }, null, { timeout: 8000 }).catch(() => {});

    await page.waitForTimeout(600);

    // Blur whatever has focus so focus rings don't vary between runs.
    await page.evaluate(() => document.activeElement && document.activeElement.blur()).catch(() => {});
}

/** Turn an absolute or relative URL into a filesystem-safe snapshot name. */
function slugForRoute(url) {
    let p;
    try {
        p = new URL(url, 'http://x').pathname;
    } catch (e) {
        p = String(url);
    }
    const slug = p.replace(/^\/+|\/+$/g, '')
        .replace(/[^a-zA-Z0-9/_-]/g, '-')
        .replace(/\//g, '__')
        .replace(/-{2,}/g, '-')
        .slice(0, 90);
    return slug || 'root';
}

/** Locators for the volatile regions, resolved against a live page. */
function masksFor(page) {
    const masks = MASK_SELECTORS.map((s) => page.locator(s));
    // The dashboard clock and the date sit as siblings inside one .lh-sm widget.
    // Masking that whole widget (scoped by the clock's unique id ancestor) covers
    // both volatile values in a single region without touching any other page.
    masks.push(
        page.locator('#dashboard-live-time')
            .locator('xpath=ancestor::div[contains(concat(" ", normalize-space(@class), " "), " lh-sm ")][1]')
    );
    // Dashboard greeting is time-of-day dependent ("Good morning/afternoon/evening").
    // No `^` anchor: the Blade renders the greeting with leading whitespace, so an
    // anchored regex never matched. Text-filtered so it hits ONLY that greeting line.
    masks.push(page.locator('p').filter({ hasText: /Good (morning|afternoon|evening),/ }));
    return masks;
}

module.exports = {
    VISUAL_DIR,
    ROUTES_FILE,
    STABILIZE_CSS,
    MASK_SELECTORS,
    login,
    stabilize,
    slugForRoute,
    masksFor,
};
