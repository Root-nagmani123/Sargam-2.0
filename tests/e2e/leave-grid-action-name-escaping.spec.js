const { test, expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");

/**
 * Stationed Leave Master and Exemption Master grids: the row actions' title and
 * aria-label ("Edit <course>").
 *
 * The feed HTML-escapes every column (config/datatables.php 'escape' => '*'), and
 * renderActions() escapes the name again for the attribute, so "A & B" was shown as
 * "Edit A &amp; B". The name is now decoded to text first and escaped once.
 *
 * renderActions() and every lm*() helper are lifted out of the real templates, so
 * this runs the code that ships, whichever helpers it has.
 */

const VIEWS = path.join(__dirname, "..", "..", "resources", "views", "admin");
const GRIDS = [
  { name: "stationed leave master", file: path.join(VIEWS, "stationed_leave_master", "index.blade.php") },
  { name: "exemption master", file: path.join(VIEWS, "exemption_master", "index.blade.php") },
];

// The server-rendered action template, reduced to the attributes under test.
const TEMPLATE = '<a class="mst-act" href="__LM_EDIT__" title="Edit __LM_NAME__" aria-label="Edit __LM_NAME__">Edit</a>';

/**
 * `function name(...) {` up to the `}` at the same indentation.
 *
 * Not support/blade-methods.js extractMethod(): it does not track regular-expression
 * literals, and lmEscape() holds /'/g, whose quote it reads as an unterminated string.
 */
function extractFunction(source, name, file) {
  const lines = source.split(/\r?\n/);
  const header = new RegExp(`^[ \\t]*function ${name}\\s*\\(`);
  const start = lines.findIndex((l) => header.test(l));
  if (start === -1) throw new Error(`function ${name}() not found in ${file}`);
  const indent = lines[start].match(/^[ \t]*/)[0];
  const end = lines.findIndex((l, i) => i > start && l === `${indent}}`);
  if (end === -1) throw new Error(`function ${name}() has no closing brace at its indentation in ${file}`);
  return lines.slice(start, end + 1).join("\n");
}

/** renderActions() plus the helpers it calls, as plain functions. */
function harness(file) {
  const blade = fs.readFileSync(file, "utf8");
  const names = new Set(["isActiveRow", "renderActions"]);
  for (const m of blade.matchAll(/function (lm\w+)\s*\(/g)) names.add(m[1]);
  names.delete("lmTemplate"); // needs the page's <template>; the template is supplied below
  const fns = [...names].map((n) => extractFunction(blade, n, file)).join("\n\n");
  // One expression: page.evaluate() runs a string as an expression, not a script.
  return `(() => {\n${fns}\nconst tpl = { actionsOn: ${JSON.stringify(TEMPLATE)}, actionsOff: ${JSON.stringify(TEMPLATE)} };\n`
    + "window.renderActions = renderActions;\n})()";
}

/** What the feed sends for a course name: HTML-escaped, as Laravel e() does. */
function escaped(text) {
  return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

for (const grid of GRIDS) {
  test.describe(`${grid.name} row-action label`, () => {
    test.beforeEach(async ({ page }) => {
      await page.setContent("<!doctype html><title>grid actions</title>");
      await page.evaluate(harness(grid.file));
    });

    async function render(page, courseName) {
      return page.evaluate(async (courseName) => {
        const host = document.createElement("div");
        host.innerHTML = window.renderActions('<a href="/edit/1">e</a>', "display", {
          pk: 1, active_inactive: 1, course_name: courseName,
        });
        document.body.appendChild(host);
        await new Promise((r) => setTimeout(r, 200));
        const a = host.querySelector("a.mst-act");
        return { title: a.getAttribute("title"), aria: a.getAttribute("aria-label"), injected: window.__injected ?? null };
      }, courseName);
    }

    test("an ampersand in the course name is shown once, not as &amp;", async ({ page }) => {
      const out = await render(page, escaped("A & B"));
      expect(out.title).toBe("Edit A & B");
      expect(out.aria).toBe("Edit A & B");
    });

    test("markup in the course name stays text and runs nothing", async ({ page }) => {
      const hostile = '"><img src=x onerror="window.__injected=1">';
      const out = await render(page, escaped(hostile));
      expect(out.title).toBe("Edit " + hostile);
      expect(out.injected).toBeNull();
    });
  });
}
