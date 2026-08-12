const { test, expect } = require("@playwright/test");

const OUT = process.env.EOR_SHOT_DIR;

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

  await login(page);
  await page.goto("/admin/estate/possession-for-others", { waitUntil: "domcontentloaded" });
  await page.waitForSelector("#estatePossessionTable tbody tr", { timeout: 60000 });
  // The header buttons keep their href, so a click before the page JS binds
  // navigates instead of opening the modal. Let the page settle first.
  await page.waitForLoadState("networkidle").catch(function() {});
  await page.waitForTimeout(2000);

  const report = {};

  /* ---------- Update Meter Reading modal ---------- */
  await page.click("#btnUpdateReading");
  await page.waitForSelector("#updateMeterReadingModal #meterReadingFilterForm", { timeout: 30000 });
  await page.waitForTimeout(800);
  const meter = page.locator("#updateMeterReadingModal");
  report.meterInitial = {
    title: await meter.locator(".modal-title").innerText(),
    labels: await meter.locator(".form-label").allInnerTexts(),
    footerButtons: await meter.locator(".modal-footer button:visible").allInnerTexts(),
    gridHidden: await meter.locator("#meterReadingSaveForm").isHidden(),
  };
  await page.screenshot({ path: `${OUT}/epo-meter-initial.png` });

  // Load Data with a month that has readings.
  await meter.locator("#bill_month").fill("2026-07");
  await page.waitForTimeout(300);
  await meter.locator("#loadMeterReadingsBtn").click();
  await page.waitForTimeout(2500);
  report.meterAfterLoad = {
    rows: await meter.locator("#updateMeterReadingOtherTable tbody tr").count(),
    headers: await meter.locator("#updateMeterReadingOtherTable thead th").allInnerTexts(),
    footerButtons: await meter.locator(".modal-footer button:visible").allInnerTexts(),
    error: (await meter.locator(".js-umr-error").innerText().catch(() => "")).trim(),
    noData: await meter.locator("#noDataMessage").isVisible(),
  };
  await page.screenshot({ path: `${OUT}/epo-meter-loaded.png` });

  // Save without ticking a row must be refused inline (no browser alert).
  if (report.meterAfterLoad.rows > 0) {
    await meter.locator("#saveMeterReadingsBtn").click();
    await page.waitForTimeout(800);
    report.meterSaveGuard = (await meter.locator(".js-umr-error").innerText()).trim();

    // Unit column recomputes as a reading is typed.
    await meter.locator("#updateMeterReadingOtherTable tbody tr:first-child input.row-check-master").check();
    const reading = meter.locator("#updateMeterReadingOtherTable tbody tr:first-child input.curr-reading").first();
    const last = await meter.locator("#updateMeterReadingOtherTable tbody tr:first-child td").nth(5).innerText();
    await reading.fill(String(parseInt(last, 10) + 25));
    await page.waitForTimeout(400);
    report.unitCell = (await meter.locator("#updateMeterReadingOtherTable tbody tr:first-child .unit-cell").first().innerText()).trim();
  }

  await meter.locator(".modal-footer .ds-btn-cancel").click();
  await page.waitForTimeout(900);

  /* ---------- Add Possession modal ---------- */
  await page.click("#btnAddPossession");
  await page.waitForSelector("#possessionFormModal #possessionForm", { timeout: 30000 });
  await page.waitForTimeout(800);
  const form = page.locator("#possessionFormModal");
  report.addForm = {
    title: await form.locator(".modal-title").innerText(),
    labels: await form.locator(".form-label").allInnerTexts(),
    submit: await form.locator(".js-epo-submit").innerText(),
    cancel: await form.locator(".ds-btn-cancel").innerText(),
  };
  await page.screenshot({ path: `${OUT}/epo-add-possession.png` });

  // Requester pick fills Request ID + Section; campus pick cascades to unit type.
  await form.locator("#estate_other_req_pk").selectOption({ index: 1 });
  await page.waitForTimeout(500);
  report.afterRequester = {
    requestId: await form.locator("#request_id_display").inputValue(),
    section: await form.locator("#section_display").inputValue(),
  };

  await form.locator("#estate_campus_master_pk").selectOption({ index: 1 });
  await page.waitForTimeout(2500);
  report.afterCampus = {
    unitTypes: await form.locator("#estate_unit_type_master_pk option").count(),
    buildings: await form.locator("#estate_block_master_pk option").count(),
    subTypes: await form.locator("#estate_unit_sub_type_master_pk option").count(),
  };

  const buildings = await form.locator("#estate_block_master_pk option").count();
  if (buildings > 1) {
    await form.locator("#estate_block_master_pk").selectOption({ index: 1 });
    await page.waitForTimeout(2000);
    report.afterBuilding = {
      subTypes: await form.locator("#estate_unit_sub_type_master_pk option").count(),
    };
    const subs = await form.locator("#estate_unit_sub_type_master_pk option").count();
    if (subs > 1) {
      await form.locator("#estate_unit_sub_type_master_pk").selectOption({ index: 1 });
      await page.waitForTimeout(2000);
      report.afterSubType = {
        houses: await form.locator("#estate_house_master_pk option").count(),
      };
      const houses = await form.locator("#estate_house_master_pk option").count();
      if (houses > 1) {
        await form.locator("#estate_house_master_pk").selectOption({ index: 1 });
        await page.waitForTimeout(600);
        report.afterHouse = {
          houseNo: await form.locator("#house_no").inputValue(),
          meterOne: await form.locator("#meter_one_display_oth").inputValue(),
          secondaryShown: await form.locator("#secondary-meter-wrapper-oth").isVisible(),
        };
      }
    }
  }
  await page.screenshot({ path: `${OUT}/epo-add-possession-filled.png` });

  // Submitting with a duplicate requester must surface inline, modal stays open.
  await form.locator(".js-epo-submit").click();
  await page.waitForTimeout(2500);
  report.afterSubmit = {
    modalStillOpen: await form.isVisible(),
    formError: (await form.locator(".js-epo-form-error").innerText().catch(() => "")).trim(),
    fieldErrors: (await form.locator(".field-error").allInnerTexts()).filter((t) => t.trim()),
    tableRows: await page.locator("#estatePossessionTable tbody tr").count(),
    notice: (await page.locator("#possessionCardBody .alert").innerText().catch(() => "")).trim(),
  };
  report.jsErrors = jsErrors;
  console.log("REPORT>>" + JSON.stringify(report, null, 2));

  // Screenshot last: it has hung this run before, and the report is what matters.
  await page.screenshot({ path: `${OUT}/epo-add-possession-submitted.png`, timeout: 15000 }).catch(function() {});
});
