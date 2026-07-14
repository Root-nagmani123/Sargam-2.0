const { test, expect } = require("@playwright/test");

/**
 * Regression test for the TomSelect -> Select2 migration across the Estate module.
 *
 * Run:
 *   E2E_BASE_URL=https://sargam2.lbsnaa.gov.in \
 *   E2E_USERNAME=xxx E2E_PASSWORD=yyy \
 *   npx playwright test tests/e2e/estate-select2.spec.js
 *
 * Skips automatically if credentials are not provided.
 */

async function login(page) {
  const username = process.env.E2E_USERNAME;
  const password = process.env.E2E_PASSWORD;
  await page.goto("", { waitUntil: "domcontentloaded" });
  const userInput = page
    .locator('input[name="email"], input[name="username"], input[type="email"], input[type="text"]')
    .first();
  const passInput = page.locator('input[name="password"], input[type="password"]').first();
  const submit = page
    .locator('button[type="submit"], input[type="submit"], button:has-text("Login")')
    .first();
  await userInput.fill(username);
  await passInput.fill(password);
  await Promise.all([page.waitForLoadState("networkidle"), submit.click()]);
  await expect(page, "Should leave login page after auth").not.toHaveURL(/login/i);
}

// Estate screens that were migrated. Path is relative to E2E_BASE_URL.
const ESTATE_PAGES = [
  { name: "Return House", path: "admin/estate/return-house" },
  { name: "Possession View", path: "admin/estate/possession-view" },
  { name: "Request For Estate", path: "admin/estate/request-for-estate" },
  { name: "Estate Migration Report", path: "admin/estate/migration-report" },
  { name: "Generate Estate Bill", path: "admin/estate/generate-estate-bill" },
];

test.describe("Estate module — Select2 migration", () => {
  test.beforeEach(async () => {
    test.skip(
      !process.env.E2E_USERNAME || !process.env.E2E_PASSWORD,
      "E2E_USERNAME/E2E_PASSWORD not provided"
    );
  });

  for (const pageDef of ESTATE_PAGES) {
    test(`${pageDef.name}: Select2 renders, no TomSelect, no JS errors`, async ({
      page,
      baseURL,
    }) => {
      test.setTimeout(120 * 1000);

      const jsErrors = [];
      page.on("pageerror", (e) => jsErrors.push(e.message));

      await login(page);

      const base = (baseURL || "").replace(/\/$/, "");
      const resp = await page.goto(`${base}/${pageDef.path}`, {
        waitUntil: "networkidle",
        timeout: 30_000,
      });
      expect(resp, `No response for ${pageDef.path}`).not.toBeNull();
      expect(resp.status(), `HTTP 5xx at ${pageDef.path}`).toBeLessThan(500);

      // No leftover TomSelect widget anywhere on the page.
      const tomSelectWrappers = await page.locator(".ts-wrapper, .ts-dropdown").count();
      expect(tomSelectWrappers, "TomSelect widgets should be gone").toBe(0);

      // Select2 library must be loaded.
      const hasSelect2 = await page.evaluate(
        () => !!(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2)
      );
      expect(hasSelect2, "jQuery Select2 plugin should be loaded").toBeTruthy();

      // No critical JS errors on load.
      expect(jsErrors, `JS errors on ${pageDef.name}: ${jsErrors.join(" | ")}`).toEqual([]);
    });
  }

  test("Return House: modal opens and employee dropdown becomes Select2 & searchable", async ({
    page,
    baseURL,
  }) => {
    test.setTimeout(120 * 1000);
    const jsErrors = [];
    page.on("pageerror", (e) => jsErrors.push(e.message));

    await login(page);
    const base = (baseURL || "").replace(/\/$/, "");
    await page.goto(`${base}/admin/estate/return-house`, { waitUntil: "networkidle" });

    // Open the "Return House" modal.
    await page.locator('[data-bs-target="#requestHouseModal"]').first().click();
    const modal = page.locator("#requestHouseModal");
    await expect(modal).toBeVisible();

    // The employee-name select should be upgraded to a Select2 container inside the modal.
    // (Select2 renders a sibling .select2-container next to the native <select>.)
    const employeeSelect2 = modal.locator("#request_employee_name + .select2-container");
    await expect(
      employeeSelect2,
      "Employee Name should be a Select2 widget"
    ).toHaveCount(1);

    // Open the Select2 dropdown — the search box lives INSIDE the dropdown (this is the fix:
    // the '--Select--' placeholder no longer sits inline in the typed text).
    await employeeSelect2.click();
    const search = page.locator(".select2-search__field");
    await expect(search, "Select2 search box should appear").toBeVisible();
    await search.fill("a");
    // Some options should be filterable (results container present).
    await expect(page.locator(".select2-results")).toBeVisible();

    expect(jsErrors, `JS errors: ${jsErrors.join(" | ")}`).toEqual([]);
  });
});
