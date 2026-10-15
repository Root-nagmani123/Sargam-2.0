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

function formRootTemplates(dir = path.join(ROOT, "resources", "views")) {
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const file = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      return formRootTemplates(file);
    }
    if (!entry.name.endsWith(".blade.php")) {
      return [];
    }

    const source = fs.readFileSync(file, "utf8");
    const roots = [...source.matchAll(/<div\b[^>]*\bdata-mst-form-root\b[^>]*>/gs)];
    return roots.map((match) => ({
      file: path.relative(path.join(ROOT, "resources", "views"), file),
      markup: match[0].replace(/\{\{[\s\S]*?\}\}/g, "Fixture Form"),
    }));
  });
}

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

function sharedRootFormPage(root, action, error) {
  return `<!doctype html><html><body>
${root}
  <form action="${action}" method="POST">
    <input type="hidden" name="_token" value="t">
    <input type="text" name="form_value" value="Needs correction"${error ? ' class="is-invalid"' : ""}>
    ${error ? '<div class="invalid-feedback d-block">The value is invalid.</div>' : ""}
    <button type="submit">Save</button>
  </form>
</div></body></html>`;
}

const INDEX = `<!doctype html><html><head><meta name="csrf-token" content="t"></head><body>
<a href="${ORIGIN}/master/city/create" data-mst-modal-form>Add City</a>
<script src="/vendor.js"></script><script src="/bootstrap.js"></script><script src="/master-admin.js"></script>
</body></html>`;

function sharedRootIndex(roots) {
  return `<!doctype html><html><head><meta name="csrf-token" content="t"></head><body>
${roots.map((root, index) => `<a href="${ORIGIN}/form/${index}" data-mst-modal-form>${root.file}</a>`).join("")}
<script src="/vendor.js"></script><script src="/bootstrap.js"></script><script src="/master-admin.js"></script>
</body></html>`;
}

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

test("every Blade data-mst-form-root form can be corrected and saved in the open modal", async ({ page }) => {
  const roots = formRootTemplates();
  expect(roots.length).toBeGreaterThan(0);

  const posts = roots.map(() => []);
  const saves = new Set();
  const index = sharedRootIndex(roots);

  await page.route("**/*", (route) => {
    const req = route.request();
    const url = new URL(req.url());
    if (url.origin !== ORIGIN) {
      return route.abort();
    }
    if (SCRIPTS[url.pathname]) {
      return route.fulfill({ contentType: "application/javascript", body: fs.readFileSync(SCRIPTS[url.pathname]) });
    }
    const formMatch = /^\/form\/(\d+)$/.exec(url.pathname);
    const postMatch = /^\/store\/(\d+)$/.exec(url.pathname);
    if (postMatch && req.method() === "POST") {
      const formIndex = Number(postMatch[1]);
      const body = req.postDataBuffer().toString();
      posts[formIndex].push(body);
      if (body.includes(`Corrected value ${formIndex}`)) {
        saves.add(formIndex);
      }
      return route.fulfill({ status: 302, headers: { Location: `${ORIGIN}/form/${formIndex}` } });
    }
    if (formMatch && req.method() === "GET") {
      const formIndex = Number(formMatch[1]);
      const error = posts[formIndex].length === 1
        && !posts[formIndex][0].includes(`Corrected value ${formIndex}`);
      return route.fulfill({
        contentType: "text/html",
        body: sharedRootFormPage(roots[formIndex].markup, `${ORIGIN}/store/${formIndex}`, error),
      });
    }
    return route.fulfill({ contentType: "text/html", body: index });
  });

  await page.goto(`${ORIGIN}/forms`);
  for (let i = 0; i < roots.length; i += 1) {
    await page.click(`a[data-mst-modal-form][href="${ORIGIN}/form/${i}"]`);
    const modal = page.locator("#mstFormModal");
    const save = modal.locator("button[type=submit]");
    await expect(save).toBeVisible();

    await save.click();
    await expect(modal.locator(".invalid-feedback")).toHaveText("The value is invalid.");
    await expect(modal).toBeVisible();
    await expect(save).toBeEnabled();
    expect(posts[i]).toHaveLength(1);
    expect(posts[i][0]).toContain("Needs correction");

    const reload = page.waitForEvent("load");
    await modal.locator('input[name="form_value"]').fill(`Corrected value ${i}`);
    await save.click();
    await expect.poll(() => posts[i].length).toBe(2);
    expect(posts[i][1]).toContain(`Corrected value ${i}`);
    await reload;
    expect(saves.has(i), `successful save for ${roots[i].file}`).toBe(true);
    expect(posts[i], `exactly two requests for ${roots[i].file}`).toHaveLength(2);
  }

  expect(posts.flat()).toHaveLength(roots.length * 2);
});

// F-023: the re-read form URL answers with an error flash but no form root, so there is no
// form to re-render. The modal kept the old form with Save disabled on "Saving…" and showed nothing.
test("a rejected Save whose form page has only an error flash re-enables Save and shows the flash", async ({ page }) => {
  const posts = [];
  let rejected = false;

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
      rejected = true;
      return route.fulfill({ status: 302, headers: { Location: `${ORIGIN}/master/city/create` } });
    }
    if (url.pathname === "/master/city/create") {
      return route.fulfill({
        contentType: "text/html",
        body: rejected
          ? '<!doctype html><html><body><div class="alert alert-danger">Record could not be saved.<button type="button">x</button></div></body></html>'
          : formPage(false),
      });
    }
    return route.fulfill({ contentType: "text/html", body: INDEX });
  });

  await page.goto(`${ORIGIN}/master/city`);
  await page.click("a[data-mst-modal-form]");
  const modal = page.locator("#mstFormModal");
  const save = modal.locator("button[type=submit]");
  await expect(save).toBeVisible();

  await save.click();
  await expect(modal.locator(".mst-form-modal-alert")).toHaveText("Record could not be saved.");
  await expect(save).toBeEnabled();
  await expect(save).toHaveText("Save");
  expect(posts).toHaveLength(1);

  await save.click();
  await expect.poll(() => posts.length).toBe(2);
});
