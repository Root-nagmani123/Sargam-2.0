/**
 * Regression test for PR #334 F-076.
 *
 * The trainee's read-only View page of a leave reuses the apply form. Its script ran the
 * apply form's one-day mirror on load, so a three-day leave recorded on the trainee's
 * behalf (13-15 Oct, 3 days) showed as 13-13 Oct, 1 day. The stored row was right.
 *
 * Runs the shipped script, lifted from apply.blade.php, in a real browser against the
 * date fields it drives, with jQuery loaded.
 *
 * Run: npx playwright test tests/e2e/leave-view-multiday-dates.spec.js --project=chrome
 */
const { test, expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");

const ROOT = path.resolve(__dirname, "..", "..");
// LEAVE_APPLY_VIEW: run the spec against another commit's copy (the red run).
const VIEW = process.env.LEAVE_APPLY_VIEW || path.join(ROOT, "resources", "views", "admin", "leave", "apply.blade.php");
// Same vendored jQuery as notice-audience-edit-prefill.spec.js (public/js/jquery-3.7.1.min.js is empty).
const JQUERY = path.join(ROOT, "vendor", "phpunit", "php-code-coverage", "src", "Report", "Html", "Renderer", "Template", "js", "jquery.min.js");
const ORIGIN = "http://leave.test";

function scriptSource() {
  const src = fs.readFileSync(VIEW, "utf8").replace(/\r\n/g, "\n");
  const start = src.indexOf("<script>", src.indexOf("@push('scripts')"));
  const end = src.indexOf("</script>", start);
  if (start === -1 || end === -1) throw new Error("@push('scripts') <script> block not found in apply.blade.php");
  const js = src.slice(start + "<script>".length, end);
  if (/@json|\{\{|\{!!/.test(js)) throw new Error("a Blade expression in the script needs replacing here");
  return js;
}

// The date fields as apply.blade.php renders them; readOnly mirrors $isReadOnly.
function page({ from, to, total, readOnly }) {
  return `<!doctype html><html><body>
  <form id="leave-apply-form" method="POST" action="${ORIGIN}/save">
    <input type="date" id="from_date_input" value="${from}" ${readOnly ? "readonly" : ""}>
    <input type="hidden" name="from_date" id="from_date" value="${from}">
    <input type="hidden" name="to_date" id="to_date" value="${to}">
    <input type="date" id="to_date_input" value="${to}" readonly>
    <input type="text" id="total_days_display" value="${total}" readonly>
    <div id="leave-attachments-wrap"></div>
  </form>
  <script src="${ORIGIN}/jquery.js"></script>
  <script src="${ORIGIN}/page.js"></script>
</body></html>`;
}

async function mount(pw, fields) {
  const errors = [];
  pw.on("pageerror", (e) => errors.push(String(e)));
  await pw.route(`${ORIGIN}/**`, (route) => {
    const p = new URL(route.request().url()).pathname;
    if (p === "/view") return route.fulfill({ contentType: "text/html", body: page(fields) });
    if (p === "/jquery.js") return route.fulfill({ contentType: "application/javascript", body: fs.readFileSync(JQUERY, "utf8") });
    if (p === "/page.js") return route.fulfill({ contentType: "application/javascript", body: scriptSource() });
    return route.fulfill({ status: 404, body: "" });
  });
  await pw.goto(`${ORIGIN}/view`);
  await pw.waitForFunction(() => window.jQuery && window.jQuery.isReady);
  return errors;
}

const shown = (pw) => pw.evaluate(() => ({
  from: document.getElementById("from_date_input").value,
  to: document.getElementById("to_date_input").value,
  hiddenTo: document.getElementById("to_date").value,
  total: document.getElementById("total_days_display").value,
}));

test("View page keeps a multi-day leave's stored dates and total", async ({ page: pw }) => {
  const errors = await mount(pw, { from: "2026-10-13", to: "2026-10-15", total: "3.0", readOnly: true });
  expect(await shown(pw)).toEqual({ from: "2026-10-13", to: "2026-10-15", hiddenTo: "2026-10-15", total: "3.0" });
  expect(errors).toEqual([]);
});

test("View page keeps a one-day leave as one day", async ({ page: pw }) => {
  const errors = await mount(pw, { from: "2026-10-13", to: "2026-10-13", total: "1.0", readOnly: true });
  expect(await shown(pw)).toEqual({ from: "2026-10-13", to: "2026-10-13", hiddenTo: "2026-10-13", total: "1.0" });
  expect(errors).toEqual([]);
});

test("control: the apply form still mirrors Date To to Date From", async ({ page: pw }) => {
  const errors = await mount(pw, { from: "2026-10-13", to: "2026-10-15", total: "0", readOnly: false });
  // On load the editable form is one day, as before the fix.
  expect(await shown(pw)).toEqual({ from: "2026-10-13", to: "2026-10-13", hiddenTo: "2026-10-13", total: "1" });

  await pw.fill("#from_date_input", "2026-10-20");
  await pw.dispatchEvent("#from_date_input", "change");
  expect(await shown(pw)).toEqual({ from: "2026-10-20", to: "2026-10-20", hiddenTo: "2026-10-20", total: "1" });
  expect(errors).toEqual([]);
});
