const { test, expect } = require("@playwright/test");

// Screenshots only when a directory is given; never into the repo (test-results/ was published once).
const OUT = process.env.EOR_SHOT_DIR || "";
const shot = (page, name) => (OUT ? page.screenshot({ path: `${OUT}/${name}.png`, timeout: 15000 }).catch(() => {}) : null);

// The Add Possession save is answered here, so this spec never writes an estate_possession_other row.
const STORE = "**/admin/estate/possession-view/store";
const STUB_REFUSAL = "Stubbed refusal from the e2e spec.";

async function login(page) {
  await page.goto("/login", { waitUntil: "domcontentloaded" });
  await page
    .locator('input[name="email"], input[name="username"], input[type="text"]')
    .first()
    .fill(process.env.E2E_USERNAME);
  await page.locator('input[type="password"]').first().fill(process.env.E2E_PASSWORD);
  await Promise.all([
    page.waitForLoadState("networkidle"),
    page.locator('button[type="submit"], input[type="submit"]').first().click(),
  ]);
  await expect(page).not.toHaveURL(/login/i);
}

test("Possession for Others — Update Reading + Add Possession modals", async ({ page }) => {
  test.setTimeout(600 * 1000);
  const jsErrors = [];
  page.on("pageerror", (e) => jsErrors.push(String(e.message)));

  let storePosts = 0;
  await page.route(STORE, (route) => {
    storePosts++;
    return route.fulfill({
      status: 422,
      contentType: "application/json",
      body: JSON.stringify({ success: false, message: STUB_REFUSAL, errors: { estate_other_req_pk: [STUB_REFUSAL] } }),
    });
  });

  await login(page);
  await page.goto("/admin/estate/possession-for-others", { waitUntil: "domcontentloaded" });
  await page.waitForSelector("#estatePossessionTable tbody tr", { timeout: 60000 });
  // The header buttons keep their href, so a click before the page JS binds
  // navigates instead of opening the modal. Let the page settle first.
  await page.waitForLoadState("networkidle").catch(function() {});
  await page.waitForTimeout(2000);

  /* ---------- Update Meter Reading modal ---------- */
  await page.click("#btnUpdateReading");
  await page.waitForSelector("#updateMeterReadingModal #meterReadingFilterForm", { timeout: 30000 });
  await page.waitForTimeout(800);
  const meter = page.locator("#updateMeterReadingModal");
  await expect(meter.locator(".modal-title")).toContainText(/meter reading/i);
  await expect(meter.locator("#meterReadingSaveForm")).toBeHidden();
  await shot(page, "epo-meter-initial");

  // Load Data with a month that has readings.
  await meter.locator("#bill_month").fill("2026-07");
  await page.waitForTimeout(300);
  await meter.locator("#loadMeterReadingsBtn").click();
  await page.waitForTimeout(2500);
  const rows = await meter.locator("#updateMeterReadingOtherTable tbody tr").count();
  const noData = await meter.locator("#noDataMessage").isVisible();
  expect(rows > 0 || noData, "the grid either lists readings or says there are none").toBe(true);
  await expect(meter.locator(".js-umr-error")).toBeHidden();
  await shot(page, "epo-meter-loaded");

  if (rows > 0) {
    // Save without ticking a row must be refused inline (no browser alert).
    await meter.locator("#saveMeterReadingsBtn").click();
    await page.waitForTimeout(800);
    await expect(meter.locator(".js-umr-error")).toBeVisible();
    expect((await meter.locator(".js-umr-error").innerText()).trim()).not.toBe("");

    // Unit column recomputes as a reading is typed.
    await meter.locator("#updateMeterReadingOtherTable tbody tr:first-child input.row-check-master").check();
    const reading = meter.locator("#updateMeterReadingOtherTable tbody tr:first-child input.curr-reading").first();
    const last = parseInt(await meter.locator("#updateMeterReadingOtherTable tbody tr:first-child td").nth(5).innerText(), 10);
    if (!Number.isNaN(last)) {
      await reading.fill(String(last + 25));
      await page.waitForTimeout(400);
      const unit = (await meter.locator("#updateMeterReadingOtherTable tbody tr:first-child .unit-cell").first().innerText()).trim();
      expect(unit, "units are computed from the typed reading").toMatch(/^\d+$/);
    }
  }

  await meter.locator(".modal-footer .ds-btn-cancel").click();
  await page.waitForTimeout(900);

  /* ---------- Add Possession modal ---------- */
  await page.click("#btnAddPossession");
  await page.waitForSelector("#possessionFormModal #possessionForm", { timeout: 30000 });
  await page.waitForTimeout(800);
  const form = page.locator("#possessionFormModal");
  await expect(form.locator(".modal-title")).toContainText(/possession/i);
  await shot(page, "epo-add-possession");

  // Requester pick fills Request ID; campus pick cascades to unit type.
  await form.locator("#estate_other_req_pk").selectOption({ index: 1 });
  await page.waitForTimeout(500);
  expect(await form.locator("#request_id_display").inputValue(), "requester fills Request ID").not.toBe("");

  await form.locator("#estate_campus_master_pk").selectOption({ index: 1 });
  await page.waitForTimeout(2500);
  expect(await form.locator("#estate_unit_type_master_pk option").count(), "campus fills unit types").toBeGreaterThan(1);

  if ((await form.locator("#estate_block_master_pk option").count()) > 1) {
    await form.locator("#estate_block_master_pk").selectOption({ index: 1 });
    await page.waitForTimeout(2000);
    if ((await form.locator("#estate_unit_sub_type_master_pk option").count()) > 1) {
      await form.locator("#estate_unit_sub_type_master_pk").selectOption({ index: 1 });
      await page.waitForTimeout(2000);
      if ((await form.locator("#estate_house_master_pk option").count()) > 1) {
        await form.locator("#estate_house_master_pk").selectOption({ index: 1 });
        await page.waitForTimeout(600);
        expect(await form.locator("#house_no").inputValue(), "house pick fills House No.").not.toBe("");
      }
    }
  }
  await shot(page, "epo-add-possession-filled");

  // A refused save (stubbed) must surface inline and keep the modal open.
  await form.locator(".js-epo-submit").click();
  await page.waitForTimeout(2500);
  expect(storePosts, "the submit reached the stub, not the server").toBe(1);
  await expect(form).toBeVisible();
  const shown = [
    (await form.locator(".js-epo-form-error").innerText().catch(() => "")).trim(),
    ...(await form.locator(".field-error").allInnerTexts()).map((t) => t.trim()),
  ];
  expect(shown, "the refusal is shown to the user").toContain(STUB_REFUSAL);
  await shot(page, "epo-add-possession-submitted");

  expect(jsErrors, "no page errors").toEqual([]);
});
