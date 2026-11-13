/**
 * Regression test for the PR #349 Update Meter Reading dialog (Possession Details).
 *
 * The dialog injects _update_meter_reading_form into an already-loaded page. Its form
 * validation handler is bound inside $(document).ready, which jQuery 3 runs on a later
 * tick once the page is loaded, while the dialog's AJAX save handler was bound at once.
 * So the AJAX save ran FIRST: it posted before validation had copied the month and the
 * reading date into their hidden fields (every save failed with "The reading bill month
 * field is required."), and it posted even when validation refused the form.
 *
 * Runs the shipped script, lifted from the partial, injected after load as the dialog
 * does, in a real browser with jQuery. Only the list and save endpoints are stubbed.
 *
 * Run: npx playwright test tests/e2e/estate-meter-reading-modal-save.spec.js --project=chrome
 */
const { test, expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");

const ROOT = path.resolve(__dirname, "..", "..");
// UMR_FORM: run the spec against another commit's copy (the red run).
const FORM = process.env.UMR_FORM || path.join(ROOT, "resources", "views", "admin", "estate", "_update_meter_reading_form.blade.php");
// Same vendored jQuery as leave-view-multiday-dates.spec.js (public/js/jquery-3.7.1.min.js is empty).
const JQUERY = path.join(ROOT, "vendor", "phpunit", "php-code-coverage", "src", "Report", "Html", "Renderer", "Template", "js", "jquery.min.js");
const ORIGIN = "http://umr.test";

function scriptSource({ inModal = true, prefill = null } = {}) {
  const src = fs.readFileSync(FORM, "utf8").replace(/\r\n/g, "\n");
  const start = src.indexOf("<script>");
  const end = src.indexOf("</script>", start);
  if (start === -1 || end === -1) throw new Error("<script> block not found in _update_meter_reading_form.blade.php");
  const js = src
    .slice(start + "<script>".length, end)
    .replace("@json($inModal)", JSON.stringify(inModal))
    .replace("{{ route('admin.estate.update-meter-reading.list') }}", `${ORIGIN}/list`)
    .replace("{{ route('admin.estate.update-meter-reading.blocks') }}", `${ORIGIN}/blocks`)
    .replace("{{ route('admin.estate.update-meter-reading.unit-sub-types') }}", `${ORIGIN}/unit-sub-types`)
    .replace("@json($prefill ?? null)", JSON.stringify(prefill))
    .replace("@if (! $errors->any())", "")
    .replace("@endif", "");
  if (/@json|@if|@endif|\{\{|\{!!/.test(js)) throw new Error("a Blade expression in the script needs replacing here");
  return js;
}

// The dialog body as the partial renders it with $inModal = true (filters, save form, footer).
const DIALOG = `<!doctype html><html><body>
  <input type="month" id="bill_month" name="bill_month" max="2099-12">
  <select id="estate_name"><option value="">Select</option><option value="64">Happy Valley</option></select>
  <select id="unit_name"><option value="">Select</option></select>
  <select id="building"><option value="">Select</option></select>
  <select id="unit_sub_type"><option value="">All</option></select>
  <input type="date" id="meter_reading_date" name="meter_reading_date">
  <button type="button" id="loadMeterReadingsBtn">Load</button>
  <form id="meterReadingSaveForm" method="POST" action="${ORIGIN}/store" style="display:none;">
    <input type="hidden" name="reading_bill_month" id="reading_bill_month" value="">
    <input type="hidden" name="reading_current_date" id="reading_current_date" value="">
    <input type="hidden" name="reading_campus_id" id="reading_campus_id" value="">
    <input type="hidden" name="reading_block_id" id="reading_block_id" value="">
    <input type="hidden" name="reading_unit_type_id" id="reading_unit_type_id" value="">
    <input type="hidden" name="reading_unit_sub_type_id" id="reading_unit_sub_type_id" value="">
    <input type="hidden" name="edit_reading_pk" id="edit_reading_pk" value="">
    <table id="updateMeterReadingTable"><thead><tr><th><input type="checkbox" id="select_all"></th></tr></thead><tbody></tbody></table>
  </form>
  <div id="noDataMessage" style="display:none;"></div>
  <div class="js-umr-error d-none"></div>
  <button type="submit" form="meterReadingSaveForm" class="js-umr-save" disabled>Save</button>
  <script src="${ORIGIN}/jquery.js"></script>
</body></html>`;

// The dialog's markup alone: what a reopen swaps in (jQuery stays loaded on the page).
const DIALOG_BODY = DIALOG.slice(DIALOG.indexOf("<body>") + 6, DIALOG.indexOf("<script"));

const ROW = {
  pk: 1, meter_slot: 1, house_no: "T-01", name: "Probe", last_reading_date: "31-07-2026",
  old_meter_no: "00000001", electric_meter_reading: 1000, baseline_min_reading: 1000,
};

async function mount(pw, { reading = "1005", inject = 1, ...opts } = {}) {
  const errors = [];
  const posts = [];
  const dialogs = [];
  const lists = [];
  pw.on("pageerror", (e) => errors.push(String(e)));
  pw.on("dialog", async (d) => { dialogs.push(d.message()); await d.dismiss(); });
  await pw.route(`${ORIGIN}/**`, (route) => {
    const req = route.request();
    const p = new URL(req.url()).pathname;
    if (p === "/dialog") return route.fulfill({ contentType: "text/html", body: DIALOG });
    if (p === "/jquery.js") return route.fulfill({ contentType: "application/javascript", body: fs.readFileSync(JQUERY, "utf8") });
    if (p === "/list") lists.push(req.url());
    if (p === "/list") return route.fulfill({ contentType: "application/json", body: JSON.stringify({ status: true, data: [ROW] }) });
    if (p === "/blocks" || p === "/unit-sub-types") return route.fulfill({ contentType: "application/json", body: JSON.stringify({ status: true, data: [] }) });
    if (p === "/store") {
      posts.push(new URLSearchParams(req.postData() || ""));
      return route.fulfill({ contentType: "application/json", body: JSON.stringify({ success: true, message: "Meter readings updated successfully." }) });
    }
    return route.fulfill({ status: 404, body: "" });
  });
  await pw.goto(`${ORIGIN}/dialog`);
  await pw.waitForFunction(() => window.jQuery && window.jQuery.isReady);
  // Injected into a page that is already loaded, exactly as the dialog's $.get().html() does.
  // inject > 1: the dialog opened again, which re-runs the partial's script on the same page.
  for (let i = 0; i < inject; i++) {
    if (i > 0) await pw.evaluate((html) => { document.body.innerHTML = html; }, DIALOG_BODY);
    await pw.addScriptTag({ content: scriptSource(opts) });
  }
  await pw.evaluate(() => {
    window.__saved = null;
    document.addEventListener("umr:readings-saved", (e) => { window.__saved = e.detail.message; });
  });
  await pw.fill("#bill_month", "2026-08");
  await pw.waitForSelector("#updateMeterReadingTable tbody tr[data-pk]");
  const row = pw.locator("#updateMeterReadingTable tbody tr[data-pk]").first();
  await row.locator(".row-check-master").check();
  await row.locator(".new-meter-reading").fill(reading);
  return { errors, posts, dialogs, lists, row };
}

test("dialog Save posts the month and reading date the server requires", async ({ page: pw }) => {
  const { errors, posts, dialogs } = await mount(pw);
  await pw.fill("#meter_reading_date", "2026-11-03");

  await pw.click(".js-umr-save");
  await expect.poll(() => posts.length).toBe(1);

  expect(posts[0].get("reading_bill_month")).toBe("2026-08");
  expect(posts[0].get("reading_current_date")).toBe("2026-11-03");
  expect(posts[0].get("readings[0][curr_month_elec_red]")).toBe("1005");
  await expect.poll(() => pw.evaluate(() => window.__saved)).toBe("Meter readings updated successfully.");
  expect(dialogs).toEqual([]);
  expect(errors).toEqual([]);
});

test("dialog Save does not post when the form's own validation refuses it", async ({ page: pw }) => {
  const { errors, posts, dialogs } = await mount(pw);
  // No meter reading date: validation alerts and blocks the submit.

  await pw.click(".js-umr-save");
  await expect.poll(() => dialogs.length).toBe(1);
  await pw.waitForTimeout(500);

  expect(dialogs[0]).toContain("Meter reading date is mandatory");
  expect(posts.length).toBe(0);
  expect(await pw.evaluate(() => window.__saved)).toBeNull();
  expect(errors).toEqual([]);
});

// F-010: the full page and the dialog run this one script; the page no longer carries its own copy.
test("Edit from List saves a reading below the previous one (the server exempts that row)", async ({ page: pw }) => {
  const { errors, posts, dialogs, row } = await mount(pw, { inModal: false, prefill: { reading_pk: 7 }, reading: "900" });
  expect(await row.locator(".new-meter-no").getAttribute("readonly")).toBeNull();
  await pw.fill("#meter_reading_date", "2026-11-03");

  await pw.click(".js-umr-save");
  await expect.poll(() => posts.length).toBe(1);

  expect(posts[0].get("readings[0][curr_month_elec_red]")).toBe("900");
  expect(dialogs).toEqual([]);
  expect(errors).toEqual([]);
});

test("the plain page still refuses a reading below the baseline, with one alert", async ({ page: pw }) => {
  const { errors, posts, dialogs, lists, row } = await mount(pw, { inModal: false, reading: "900" });
  expect(await row.locator(".new-meter-no").getAttribute("readonly")).not.toBeNull();
  expect(lists.length).toBe(1);
  // Leaving the reading field alerts once (two handler sets alerted twice).
  await pw.fill("#meter_reading_date", "2026-11-03");
  await pw.waitForTimeout(300);
  expect(dialogs.length).toBe(1);

  await pw.click(".js-umr-save");
  await pw.waitForTimeout(500);

  expect(posts.length).toBe(0);
  expect(dialogs.length).toBeGreaterThanOrEqual(1);
  for (const d of dialogs) expect(d).toContain("cannot be less than the minimum allowed baseline");
  expect(errors).toEqual([]);
});

test("the plain page saves a normal reading", async ({ page: pw }) => {
  const { errors, posts, dialogs } = await mount(pw, { inModal: false, reading: "1005" });
  await pw.fill("#meter_reading_date", "2026-11-03");

  await pw.click(".js-umr-save");
  await expect.poll(() => posts.length).toBe(1);

  expect(posts[0].get("readings[0][curr_month_elec_red]")).toBe("1005");
  expect(dialogs).toEqual([]);
  expect(errors).toEqual([]);
});

test("opening the dialog a second time does not double its handlers", async ({ page: pw }) => {
  const { errors, posts, dialogs } = await mount(pw, { inject: 2, reading: "900" });
  // Leaving the reading field alerts once, not once per earlier open.
  await pw.fill("#meter_reading_date", "2026-11-03");
  await pw.waitForTimeout(300);
  expect(dialogs.length).toBe(1);

  await pw.click(".js-umr-save");
  await pw.waitForTimeout(500);
  expect(posts.length).toBe(0);
  expect(errors).toEqual([]);
});
