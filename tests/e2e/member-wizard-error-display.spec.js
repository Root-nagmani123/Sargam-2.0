/**
 * Regression test for F-055 / F-056.
 *
 * The member wizard validates one step at a time on the way forward, but the
 * final submit posts everything to `member.store`, which validates the UNION of
 * all five StoreMemberStep*Request rule sets. Its 422 can therefore name a field
 * that does not live on the last step.
 *
 * WHAT IS ACTUALLY UNDER TEST, and why an earlier version of this file did not
 * test it:
 *
 *   jQuery Steps keeps every step body in the DOM but HIDES all but the current
 *   one. jquery.steps.min.js K() calls _showAria(currentIndex === d), and
 *   _showAria(false) is this.hide()._aria("hidden","true") - inline
 *   display:none PLUS aria-hidden="true".
 *
 *   So "the message is in the DOM" and "the user can see the message" are
 *   different claims, and only the second one is the defect. A previous version
 *   of this spec built the five <section>s by hand with NO hiding and asserted
 *   with querySelectorAll(...).textContent, which is indifferent to visibility.
 *   Measured against the three implementations, that assertion could not tell
 *   the middle row from the last one:
 *
 *                            in DOM   toast   VISIBLE
 *     pre-fix (1a7457e19)       no      no       no
 *     scope-widening only       yes     no       no      <- still broken
 *     current implementation    yes     yes      yes
 *
 *   It went red pre-fix and green after the scope-widening change, so it looked
 *   like a valid regression test while the user still saw nothing.
 *
 * This version therefore drives the REAL plugin - real jQuery, the repository's
 * own jquery.steps.min.js, a real .steps() initialisation, and a real walk to
 * the last step - and asserts VISIBILITY, never mere presence.
 *
 * It must FAIL against the pre-fix blade AND against a scope-widening-only
 * implementation. Verify that before trusting it.
 */
const { test, expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");

const ROOT = path.resolve(__dirname, "..", "..");
const STEPS_JS = path.join(
  ROOT, "public", "admin_assets", "libs", "jquery-steps", "build", "jquery.steps.min.js");

/**
 * jQuery is not vendored in this repository: `public/js/jquery-3.7.1.min.js` is
 * tracked but ZERO BYTES (also zero at the merge-base, so this is long-standing
 * and not something PR #309 introduced), and the pages pull jQuery from
 * code.jquery.com - see resources/views/admin/layouts/timetable.blade.php.
 *
 * So: use a local copy when there is a real one, otherwise the same CDN the
 * application itself uses. Never silently substitute a stub - the behaviour
 * under test IS the plugin's, so a fake would test the fake.
 */
const LOCAL_JQUERY = [
  path.join(ROOT, "public", "js", "jquery-3.7.1.min.js"),
  path.join(ROOT, "node_modules", "jquery", "dist", "jquery.min.js"),
].find((p) => {
  try {
    return fs.statSync(p).size > 1024;
  } catch {
    return false;
  }
});

const CDN_JQUERY = "https://code.jquery.com/jquery-3.7.1.min.js";

/** The real page shape: five <h3>/<section> pairs carrying real field names. */
const WIZARD_HTML = `
  <form id="member-form">
  <div id="wizard">
    <h3>Member Information</h3>
    <section id="step-1" class="step-section"><input name="first_name"></section>
    <h3>Employment Details</h3>
    <section id="step-2" class="step-section"><input name="id"></section>
    <h3>Role Assignment</h3>
    <section id="step-3" class="step-section">
      <input type="checkbox" name="userrole[]" value="1">
    </section>
    <h3>Contact Information</h3>
    <section id="step-4" class="step-section"><input name="permanentaddress"></section>
    <h3>Additional Details</h3>
    <section id="step-5" class="step-section"><input type="file" name="picture"></section>
  </div>
  </form>`;

/** Pull a named function out of the blade by brace matching. */
function extractFunction(source, name) {
  const start = source.indexOf("function " + name + "(");
  if (start < 0) throw new Error(`function ${name}() not found in blade`);
  let depth = 0;
  for (let j = source.indexOf("{", start); j < source.length; j++) {
    if (source[j] === "{") depth++;
    else if (source[j] === "}") {
      depth--;
      if (depth === 0) return source.slice(start, j + 1);
    }
  }
  throw new Error(`unbalanced braces reading ${name}()`);
}

const BLADES = [
  ["create", path.join(ROOT, "resources/views/admin/member/create.blade.php")],
  ["edit", path.join(ROOT, "resources/views/admin/member/edit.blade.php")],
];

for (const [label, bladePath] of BLADES) {
  test.describe(`member ${label} wizard - final-submit error display (F-055/F-056)`, () => {
    test.beforeEach(async ({ page }) => {
      await page.setContent(`<!doctype html><html><head><meta charset="utf-8"><style>
        .wizard > .content > .body { position: relative; width: 100%; height: auto; }
      </style></head><body>${WIZARD_HTML}</body></html>`);

      if (LOCAL_JQUERY) {
        await page.addScriptTag({ path: LOCAL_JQUERY });
      } else {
        await page.addScriptTag({ url: CDN_JQUERY });
      }
      const ok = await page.evaluate(() => typeof window.jQuery === "function");
      // A skip is visible in the report; a pass with no jQuery would be a lie.
      test.skip(!ok, `jQuery unavailable (no local copy, and ${CDN_JQUERY} unreachable)`);

      // The REAL plugin, from this repository.
      await page.addScriptTag({ path: STEPS_JS });

      // Initialise as the blade does. onStepChanging is stubbed to allow forward
      // movement, because the real one posts to /member/validate-step and this
      // spec runs without a server; the hiding behaviour under test belongs to
      // the plugin and is unaffected by that stub.
      await page.evaluate(() => {
        $("#wizard").steps({
          headerTag: "h3",
          bodyTag: "section",
          transitionEffect: "slideLeft",
          stepsOrientation: "vertical",
          autoFocus: true,
          enablePagination: true,
          onStepChanging: function () { return true; },
        });
        window.__toasts = [];
        window.toastr = { error: function (m) { window.__toasts.push(String(m)); } };
      });

      // Walk to the LAST step, the way a user completing the wizard does.
      for (let i = 0; i < 4; i++) {
        await page.evaluate(() => $("#wizard").steps("next"));
        await page.waitForTimeout(350);
      }
      expect(await page.evaluate(() => $("#wizard").steps("getCurrentIndex"))).toBe(4);

      // Inject the blade's real error-display code.
      const blade = fs.readFileSync(bladePath, "utf8");
      const names = ["clearErrors", "showErrors"];
      for (const extra of ["stepIndexOf", "stepTitleAt", "goToStep"]) {
        if (blade.indexOf("function " + extra + "(") >= 0) names.push(extra);
      }
      await page.addScriptTag({
        content: [
          ...names.map((n) => extractFunction(blade, n)),
          // Exactly how the blade selects the target on the 422 branch.
          "window.__lastStep = function () { return $('.wizard .step-section').last(); };",
        ].join("\n"),
      });
    });

    /** Render a 422 payload and report what the USER can see. */
    const render = async (page, errors) => {
      await page.evaluate((errs) => {
        window.__toasts = [];
        showErrors(window.__lastStep(), errs);
      }, errors);
      await page.waitForTimeout(1200); // let any slide transition settle
      return {
        visible: await page.locator(".text-danger:visible").count(),
        toasts: await page.evaluate(() => window.__toasts),
        stepIndex: await page.evaluate(() => $("#wizard").steps("getCurrentIndex")),
        inDom: await page.evaluate(() =>
          Array.from(document.querySelectorAll(".text-danger")).map((n) => n.textContent)),
      };
    };

    test("a 422 for a field on an EARLIER step is VISIBLE to the user (the defect)", async ({ page }) => {
      const out = await render(page, { id: ["This employee ID already exists"] });

      // The assertion that matters: not "is it in the DOM", but "can it be seen".
      expect(out.visible, "the message for a step-2 field was not visible to the user").toBeGreaterThan(0);
      await expect(page.locator(".text-danger").first()).toBeVisible();

      // And the wizard should have brought the offending step into view.
      expect(out.stepIndex, "wizard did not move to the step owning the error").toBe(1);
      expect(out.inDom.join(" ")).toContain("This employee ID already exists");
    });

    test("the user is told even if navigation is refused - a toast names the step", async ({ page }) => {
      const out = await render(page, { id: ["This employee ID already exists"] });
      expect(out.toasts.join(" ")).toContain("This employee ID already exists");
      expect(out.toasts.join(" "),
        "the toast should name the step the field lives on").toContain("Employment Details");
    });

    test("still surfaces a 422 for a field on the LAST step (no regression)", async ({ page }) => {
      const out = await render(page, { picture: ["Picture size must not exceed 2MB."] });
      expect(out.visible).toBeGreaterThan(0);
      await expect(page.locator(".text-danger").first()).toBeVisible();
      // Already on the last step - nothing should move.
      expect(out.stepIndex).toBe(4);
    });

    test("handles the array-input key shape used by userrole[]", async ({ page }) => {
      const out = await render(page, { "userrole.0": ["The userrole field is required."] });
      expect(out.visible + out.toasts.length,
        "a dotted array key produced nothing the user could see").toBeGreaterThan(0);
      expect(out.visible, "the userrole[] message should be visible after navigation").toBeGreaterThan(0);
      expect(out.stepIndex).toBe(2);
    });

    test("never swallows a message whose field is not on the page at all", async ({ page }) => {
      const out = await render(page, { some_server_only_field: ["Something went wrong."] });
      expect(out.toasts.join(" ")).toContain("Something went wrong.");
    });

    test("clears earlier errors instead of stacking them across submits", async ({ page }) => {
      await render(page, { id: ["This employee ID already exists"] });
      const second = await render(page, { id: ["This employee ID already exists"] });
      expect(second.inDom.filter((m) => m.includes("already exists"))).toHaveLength(1);
    });
  });
}
