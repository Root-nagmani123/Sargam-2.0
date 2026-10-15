const { test, expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");

const ROOT = path.resolve(__dirname, "..", "..");
const ORIGIN = "http://leave-export.test";
const VIEW = path.join(ROOT, "resources", "views", "admin", "leave", "on_behalf", "index.blade.php");
const ASSETS = {
  "/jquery.js": path.join(ROOT, "vendor", "phpunit", "php-code-coverage", "src", "Report", "Html", "Renderer", "Template", "js", "jquery.min.js"),
  "/datatables.js": path.join(ROOT, "public", "admin_assets", "libs", "datatables.net", "js", "jquery.dataTables.min.js"),
};

function dateAssets() {
  const source = fs.readFileSync(VIEW, "utf8");
  const paths = [
    ...source.matchAll(/<(?:script|link)[^>]+(?:src|href)="\{\{ asset\('([^']+)'\) \}\}"/g),
  ].map((match) => match[1]);
  expect(paths).toEqual([
    "admin_assets/libs/daterangepicker/daterangepicker.css",
    "admin_assets/js/extra-libs/moment/moment.min.js",
    "admin_assets/libs/daterangepicker/daterangepicker.js",
  ]);
  expect(source).not.toContain("/latest/");
  const icons = source.match(/<link[^>]+href="(https:\/\/cdn[^"]+)"[^>]*>/g);
  expect(icons).toHaveLength(1);
  expect(icons[0]).toContain("bootstrap-icons@1.11.3/");
  expect(icons[0]).toContain('integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+"');
  expect(icons[0]).toContain('crossorigin="anonymous"');
  return paths;
}

function scriptSource() {
  const source = fs.readFileSync(VIEW, "utf8");
  const start = source.indexOf("<script>", source.indexOf("@push('scripts')"));
  const end = source.indexOf("</script>", start);
  if (start < 0 || end < 0) throw new Error("Leave on Behalf script block not found");
  const script = source.slice(start + "<script>".length, end)
    .replace("{{ route('admin.leave-on-behalf.export') }}", `${ORIGIN}/export`)
    .replace("{{ route('admin.leave-on-behalf.index') }}", `${ORIGIN}/grid`);
  if (/\{\{|\{!!|@json/.test(script)) throw new Error("Unresolved Blade expression");
  return script;
}

for (const format of ["excel", "pdf"]) {
  test(`${format} download sends the grid search and clears it after reset`, async ({ page }) => {
    const datePaths = dateAssets();
    const errors = [];
    page.on("pageerror", (error) => errors.push(String(error)));
    await page.route(`${ORIGIN}/**`, (route) => {
      const url = new URL(route.request().url());
      if (datePaths.includes(url.pathname.slice(1))) {
        return route.fulfill({
          contentType: url.pathname.endsWith(".css") ? "text/css" : "application/javascript",
          body: fs.readFileSync(path.join(ROOT, "public", ...url.pathname.slice(1).split("/")), "utf8"),
        });
      }
      if (ASSETS[url.pathname]) {
        return route.fulfill({ contentType: "application/javascript", body: fs.readFileSync(ASSETS[url.pathname], "utf8") });
      }
      if (url.pathname === "/page.js") {
        return route.fulfill({ contentType: "application/javascript", body: scriptSource() });
      }
      if (url.pathname === "/grid") {
        return route.fulfill({ json: { draw: Number(url.searchParams.get("draw")), recordsTotal: 0, recordsFiltered: 0, data: [] } });
      }
      if (url.pathname === "/export") {
        return route.fulfill({ body: "download fixture", headers: { "Content-Disposition": `attachment; filename=leave.${format === "excel" ? "xlsx" : "pdf"}` } });
      }
      return route.fulfill({
        contentType: "text/html",
        body: `<!doctype html><html><head><meta charset="utf-8"></head><body>
          <select id="courseFilter"><option value="42" selected>Course</option><option value="">All</option></select>
          <input id="timePeriodFilter"><button id="resetFilters">Reset Filters</button>
          <button class="lob-export-option" data-format="${format}">Download</button>
          <table id="leave-on-behalf-table"><thead><tr>${"<th>Column</th>".repeat(13)}</tr></thead></table>
          ${["/jquery.js", "/datatables.js"].map((asset) => `<script src="${asset}"></script>`).join("")}
          ${datePaths.map((asset) => asset.endsWith(".css")
            ? `<link rel="stylesheet" href="/${asset}">`
            : `<script src="/${asset}"></script>`).join("")}
          <script src="/page.js"></script></body></html>`,
      });
    });
    await page.goto(`${ORIGIN}/list`);
    expect(await page.evaluate(() => moment.version)).toBe("2.30.1");
    expect(fs.readFileSync(path.join(ROOT, "public", ...datePaths[2].split("/")), "utf8")).toContain("@version: 3.1");
    const search = "F082 & + /";
    await page.locator('input[type="search"]').fill(search);
    await expect.poll(() => page.evaluate(() => $("#leave-on-behalf-table").DataTable().search())).toBe(search);
    await page.locator("#timePeriodFilter").click();
    await expect(page.locator(".daterangepicker")).toBeVisible();
    await page.locator('.daterangepicker [data-range-key="Custom Range"]').click();
    await page.evaluate(() => {
      const picker = $("#timePeriodFilter").data("daterangepicker");
      picker.setStartDate("01-10-2026");
      picker.setEndDate("14-10-2026");
      picker.updateView();
    });
    await page.locator(".daterangepicker .applyBtn").click();
    await expect(page.locator("#timePeriodFilter")).toHaveValue("01-10-2026 – 14-10-2026");

    const request = page.waitForRequest((request) => new URL(request.url()).pathname === "/export");
    await page.getByRole("button", { name: "Download", exact: true }).click();
    const params = new URL((await request).url()).searchParams;
    expect(params.get("search[value]")).toBe(search);
    expect(params.get("course_filter")).toBe("42");
    expect(params.get("from_date")).toBe("2026-10-01");
    expect(params.get("to_date")).toBe("2026-10-14");
    expect(params.get("format")).toBe(format);

    await page.locator("#timePeriodFilter").click();
    await page.locator('.daterangepicker [data-range-key="Custom Range"]').click();
    await page.locator(".daterangepicker .cancelBtn").click();
    await expect(page.locator("#timePeriodFilter")).toHaveValue("");
    await page.getByRole("button", { name: "Reset Filters" }).click();
    const resetRequest = page.waitForRequest((request) => new URL(request.url()).pathname === "/export");
    await page.getByRole("button", { name: "Download", exact: true }).click();
    const resetParams = new URL((await resetRequest).url()).searchParams;
    for (const key of ["search[value]", "course_filter", "from_date", "to_date"]) {
      expect(resetParams.get(key)).toBe("");
    }

    expect(errors).toEqual([]);
  });
}
