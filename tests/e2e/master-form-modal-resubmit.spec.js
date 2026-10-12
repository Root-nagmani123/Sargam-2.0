const { test, expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");

/**
 * Regression guard for the shared master Add/Edit modal (PR #335 F-020).
 *
 * public/js/master-admin.js posts the modal form, and on a validation failure re-reads the
 * form URL and re-renders the form with its errors. It set formModal.busy before the POST and
 * never cleared it on that re-render, so after one server-side rejection every later Save was
 * silently dropped until the modal was closed (and the input lost). All 15 data-mst-form-root
 * forms share this path.
 *
 * The real master-admin.js runs against a stubbed server: the first POST is rejected (302 back,
 * the form re-renders with a field error), the corrected second POST is accepted. No
 * application server is needed. jQuery comes from the bundle the admin layout loads; Bootstrap
 * from the npm copy, because the layout's CDN copy would need the network.
 */

const ROOT = path.join(__dirname, "..", "..");
const ORIGIN = "http://mst.test";
const SCRIPTS = {
  "/vendor.js": path.join(ROOT, "public", "admin_assets", "js", "vendor.min.js"),
  "/bootstrap.js": require.resolve("bootstrap/dist/js/bootstrap.bundle.min.js"),
  "/master-admin.js": path.join(ROOT, "public", "js", "master-admin.js"),
};

/** The form page as the City create view renders it, with or without a field error. */
function formPage(error) {
  return `<!doctype html><html><body>
<div data-mst-form-root data-mst-form-title="Add City">
  <form action="${ORIGIN}/master/city/store" method="POST">
    <input type="hidden" name="_token" value="t">
    <input type="text" name="city_name" value="Agra"
      class="form-control mst-control${error ? " is-invalid" : ""}">
    ${error ? '<div class="invalid-feedback d-block">The city name has already been taken.</div>' : ""}
    <button type="submit" class="btn btn-primary">Save</button>
  </form>
</div></body></html>`;
}

const INDEX = `<!doctype html><html><head><meta name="csrf-token" content="t"></head><body>
<a href="${ORIGIN}/master/city/create" data-mst-modal-form>Add City</a>
<script src="/vendor.js"></script><script src="/bootstrap.js"></script><script src="/master-admin.js"></script>
</body></html>`;

test("a corrected Save after a server-side rejection sends a second POST", async ({ page }) => {
  const posts = [];
  let rejectNext = true;
  let showError = false;

  await page.route("**/*", (route) => {
    const req = route.request();
    const url = new URL(req.url());
    if (url.origin !== ORIGIN) {
      return route.abort();
    }
    if (SCRIPTS[url.pathname]) {
      return route.fulfill({ contentType: "application/javascript", body: fs.readFileSync(SCRIPTS[url.pathname]) });
    }
    if (req.method() === "POST" && url.pathname === "/master/city/store") {
      posts.push(req.postDataBuffer().toString());
      showError = rejectNext;
      rejectNext = false;
      // Laravel answers both outcomes with a redirect; the modal re-reads the form to tell which.
      return route.fulfill({ status: 302, headers: { Location: `${ORIGIN}/master/city/create` } });
    }
    if (url.pathname === "/master/city/create") {
      return route.fulfill({ contentType: "text/html", body: formPage(showError) });
    }
    return route.fulfill({ contentType: "text/html", body: INDEX });
  });

  await page.goto(`${ORIGIN}/master/city`);
  await page.click("a[data-mst-modal-form]");
  const modal = page.locator("#mstFormModal");
  const save = modal.locator("button[type=submit]");
  await expect(save).toBeVisible();

  await save.click();
  await expect(modal.locator(".invalid-feedback")).toHaveText("The city name has already been taken.");
  expect(posts).toHaveLength(1);
  await expect(save).toBeEnabled();

  const reload = page.waitForEvent("load");
  await modal.locator("input[name=city_name]").fill("Agra Cantt");
  await save.click();
  await expect.poll(() => posts.length).toBe(2);
  expect(posts[1]).toContain("Agra Cantt");
  // The second answer has no errors, so the modal treats it as saved and reloads the page.
  await reload;
});
