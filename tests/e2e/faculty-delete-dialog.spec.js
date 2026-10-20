const { test, expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");

/**
 * Regression guard for the Faculty delete confirmation (stored XSS, PR #308 F-021).
 *
 * FacultyDataTable HTML-escapes the faculty name into the Delete button's data-name, but the
 * browser decodes that attribute again, so whatever the handler reads back - attr() or data() -
 * is the raw stored name. The handler then builds Swal.fire({ html: ... }) from it. Unless the
 * handler re-escapes the name, a faculty named <img src=x onerror=...> runs script in the
 * session of the administrator who clicks Delete.
 *
 * The handler lives inside a Blade template, so it cannot be imported. Its <script> block is
 * lifted out of the real file and run in page context, which means this test fails if someone
 * edits the template rather than passing against a stale copy. No application server is needed.
 * jQuery comes from the bundle the admin layout loads (admin_assets/js/vendor.min.js, loaded by
 * admin/layouts/footer.blade.php, which master.blade.php includes before @stack('scripts'));
 * SweetAlert2 from the local copy in admin_assets/libs, because the layout's CDN copy would need
 * the network.
 */

const ROOT = path.join(__dirname, "..", "..");
const BLADE = path.join(ROOT, "resources", "views", "admin", "faculty", "index.blade.php");
const JQUERY_BUNDLE = path.join(ROOT, "public", "admin_assets", "js", "vendor.min.js");
const SWEETALERT = path.join(ROOT, "public", "admin_assets", "libs", "sweetalert2", "dist", "sweetalert2.min.js");

/** The one <script> block that binds the delete handler, exactly as shipped. */
function deleteHandlerScript() {
  const source = fs.readFileSync(BLADE, "utf8");
  const blocks = [...source.matchAll(/<script>([\s\S]*?)<\/script>/g)]
    .map((m) => m[1])
    .filter((body) => body.includes(".delete-faculty-btn"));
  if (blocks.length !== 1) {
    throw new Error(`Expected one <script> block binding .delete-faculty-btn in ${BLADE}, found ${blocks.length}`);
  }
  // Blade would rewrite these before the browser saw them; evaluating them raw would test
  // something that never ships.
  if (/\{\{|\{!!|(^|\s)@[a-z]/.test(blocks[0])) {
    throw new Error("The delete handler block now contains Blade syntax; this test cannot evaluate it raw");
  }
  return blocks[0];
}

/** PHP htmlspecialchars($s, ENT_QUOTES), as FacultyDataTable applies to the name. */
function htmlspecialchars(s) {
  return String(s)
    .replace(/&/g, "&amp;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

/** The Delete button as FacultyDataTable::dataTable() emits it for an inactive row. */
function deleteButton(name) {
  const safe = htmlspecialchars(name);
  return (
    '<button type="button" class="mst-act mst-act--del delete-faculty-btn"' +
    ' data-url="/faculty/delete/x"' +
    ` data-name="${safe}"` +
    ' data-token="t"' +
    ` title="Delete ${safe}">` +
    '<span class="mst-act__label">Delete</span></button>'
  );
}

async function openDeleteDialog(page, name) {
  // Nothing in this test may reach the network: a CDN fetch would make it flaky, and a
  // "delete" request must never be sent.
  await page.route("**/*", (route) => route.abort());
  await page.setContent('<!doctype html><title>faculty delete dialog</title><div id="cell"></div>');
  await page.addScriptTag({ content: fs.readFileSync(JQUERY_BUNDLE, "utf8") });
  await page.addScriptTag({ content: fs.readFileSync(SWEETALERT, "utf8") });
  await page.evaluate(() => {
    window.__x = undefined;
    window.__y = undefined;
    window.__ajaxCalls = 0;
    window.$.ajax = () => { window.__ajaxCalls += 1; };
  });
  await page.addScriptTag({ content: deleteHandlerScript() });
  // DataTables inserts rawColumns output as markup, so the attribute is parsed (and decoded)
  // exactly as on the real page.
  await page.evaluate((html) => window.$("#cell").html(html), deleteButton(name));
  await page.click(".delete-faculty-btn");
  await expect(page.locator(".swal2-html-container")).toBeVisible();
}

const NAMES = [
  { label: "an <img onerror> payload", name: "<img src=x onerror=window.__x=1>" },
  { label: "an attribute breakout", name: '"><svg onload=window.__y=1>' },
  { label: "a name that is literally an HTML entity", name: "&lt;b&gt;" },
  { label: "an ampersand (not double-escaped)", name: "Tom & Jerry" },
  { label: "both kinds of quote", name: "O'Brien \"Jr\"" },
  { label: "a numeric name", name: "12345" },
  { label: "an ordinary name", name: "Dr. Anita Sharma" },
];

test.describe("admin faculty - delete confirmation shows the stored name as text", () => {
  for (const { label, name } of NAMES) {
    test(`${label} is displayed verbatim and nothing runs`, async ({ page }) => {
      await openDeleteDialog(page, name);

      const shown = page.locator(".swal2-html-container strong");
      await expect(shown).toHaveText(name, { useInnerText: false });

      const result = await page.evaluate(() => ({
        injected: document.querySelectorAll(
          ".swal2-html-container img, .swal2-html-container svg, .swal2-html-container script, .swal2-html-container iframe"
        ).length,
        x: window.__x,
        y: window.__y,
        ajaxCalls: window.__ajaxCalls,
      }));
      expect(result.injected).toBe(0);
      expect(result.x).toBeUndefined();
      expect(result.y).toBeUndefined();
      // Opening the dialog must not delete anything; only confirming does.
      expect(result.ajaxCalls).toBe(0);
    });
  }
});
