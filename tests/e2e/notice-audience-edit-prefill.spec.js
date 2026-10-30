/**
 * Regression test for PR #334 F-071.
 *
 * The notice edit form's course list is empty until an AJAX call fills it with the
 * notice's saved courses, and an empty course list is how "every course" is posted.
 * Saving inside that window, or after the call failed, turned a notice for one
 * trainee into a notice for every Officer Trainee.
 *
 * Runs the shipped audience_scripts module, lifted from its Blade partial, in a real
 * browser against the fields it drives, with jQuery loaded. The AJAX
 * endpoints and the form POST are intercepted. Choices.js is not loaded, so the
 * module's plain-<select> branch runs (the live page uses Choices for display).
 *
 * Run: npx playwright test tests/e2e/notice-audience-edit-prefill.spec.js --project=chrome
 */
const { test, expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");

const ROOT = path.resolve(__dirname, "..", "..");
// AUDIENCE_SCRIPTS_PARTIAL: run the spec against another commit's copy (the red run).
const PARTIAL = process.env.AUDIENCE_SCRIPTS_PARTIAL || path.join(ROOT, "resources", "views", "admin", "NoticeNotification", "partials", "audience_scripts.blade.php");
// The layout loads jQuery 3.7.1 from code.jquery.com; public/js/jquery-3.7.1.min.js is an empty
// file. 3.6.1 (vendored by phpunit) has the same $.getJSON / Deferred API the module uses.
const JQUERY = path.join(ROOT, "vendor", "phpunit", "php-code-coverage", "src", "Report", "Html", "Renderer", "Template", "js", "jquery.min.js");
const ORIGIN = "http://notice.test";

// A saved individual notice: course 11, trainee 2 (the F-071 probe's shape).
const PRESET = {
  target_audience: "Office trainee",
  course_master_pks: ["11"],
  ot_scope: "individual",
  group_type_map_pks: [],
  student_pks: ["2"],
  department_master_pks: [],
  staff_scope: "all",
  employee_pks: [],
};

function moduleSource() {
  const src = fs.readFileSync(PARTIAL, "utf8").replace(/\r\n/g, "\n");
  const start = src.indexOf("<script>");
  const end = src.lastIndexOf("</script>");
  if (start === -1 || end === -1) throw new Error("<script> block not found in audience_scripts.blade.php");
  const js = src
    .slice(start + "<script>".length, end)
    .replace(/@json\(route\('admin\.notice\.(\w+)'\)\)/g, (_, name) => JSON.stringify(`${ORIGIN}/ajax/${name}`))
    .replace("@json(\\App\\Models\\NoticeNotification::MODE_ALL)", '"all"')
    .replace("@json(\\App\\Models\\NoticeNotification::MODE_GROUP)", '"group"')
    .replace("@json(\\App\\Models\\NoticeNotification::MODE_INDIVIDUAL)", '"individual"')
    .replace("@json($audiencePreset)", JSON.stringify(PRESET));
  if (/@json|\{\{/.test(js)) throw new Error("an unreplaced Blade expression is left in the module");
  return js;
}

// Every element audience_scripts reads or writes, as audience_fields renders them.
const PAGE = `<!doctype html><html><body>
  <form id="noticeForm" method="POST" action="${ORIGIN}/save">
    <select name="target_audience" id="targetAudience">
      <option value="All">All</option>
      <option value="Office trainee">Office trainee</option>
      <option value="Staff/Faculty">Staff/Faculty</option>
    </select>
    <div id="audienceLoadStatus" class="d-none"></div>
    <div id="otAudienceBox" class="d-none">
      <span id="courseCount"></span>
      <select name="course_master_pks[]" id="courseSelect" multiple></select>
      <div id="otScopeBox" class="d-none">
        <select name="ot_scope" id="otScopeSelect">
          <option value="all">All</option><option value="group">Group</option><option value="individual">Individual</option>
        </select>
      </div>
      <div id="otGroupBox" class="d-none"><span id="otGroupCount"></span><select name="group_type_map_pks[]" id="otGroupSelect" multiple></select></div>
      <div id="studentBox" class="d-none"><span id="studentCount"></span><select name="student_pks[]" id="studentSelect" multiple></select></div>
      <div id="otGroupPreviewBox" class="d-none"><span id="otGroupPreviewCount"></span><div id="otGroupPreview"></div></div>
    </div>
    <div id="staffAudienceBox" class="d-none">
      <span id="departmentCount"></span>
      <select name="department_master_pks[]" id="departmentSelect" multiple><option value="5">Dept 5</option></select>
      <div id="staffScopeBox" class="d-none"><select name="staff_scope" id="staffScopeSelect"><option value="all">All</option><option value="individual">Individual</option></select></div>
      <div id="employeeBox" class="d-none"><span id="employeeCount"></span><select name="employee_pks[]" id="employeeSelect" multiple></select></div>
    </div>
    <button type="submit" id="saveBtn">Update</button>
  </form>
  <script src="${ORIGIN}/jquery.js"></script>
  <script src="${ORIGIN}/module.js"></script>
</body></html>`;

/**
 * Serves the page and intercepts its requests. `courses` is "hold" (answered only
 * when release() is called), "fail" (HTTP 500) or "ok".
 */
async function mount(page, { courses }) {
  const posted = [];
  let releaseCourses = () => {};
  let resolveAsked;
  const coursesAsked = new Promise((r) => { resolveAsked = r; });

  await page.route(`${ORIGIN}/**`, async (route) => {
    const url = new URL(route.request().url());
    const json = (body, status = 200) => route.fulfill({ status, contentType: "application/json", body: JSON.stringify(body) });

    if (url.pathname === "/edit") return route.fulfill({ contentType: "text/html", body: PAGE });
    if (url.pathname === "/jquery.js") return route.fulfill({ contentType: "application/javascript", body: fs.readFileSync(JQUERY, "utf8") });
    if (url.pathname === "/module.js") return route.fulfill({ contentType: "application/javascript", body: moduleSource() });
    if (url.pathname === "/save") {
      posted.push(new URLSearchParams(route.request().postData() || ""));
      return route.fulfill({ contentType: "text/html", body: "<p>saved</p>" });
    }
    if (url.pathname === "/ajax/getCourses") {
      resolveAsked();
      const answer = () => json({ data: [{ pk: 11, course_name: "Course 11" }, { pk: 12, course_name: "Course 12" }] });
      if (courses === "fail") return json({ message: "Server Error" }, 500);
      if (courses === "hold") {
        await new Promise((r) => { releaseCourses = r; });
      }
      return answer();
    }
    if (url.pathname === "/ajax/getStudents") return json({ data: [{ pk: 2, label: "OT 2 (A-02)" }, { pk: 3, label: "OT 3 (A-03)" }] });
    if (url.pathname === "/ajax/getGroupTypes") return json({ data: [] });
    if (url.pathname === "/ajax/getEmployees") return json({ data: [] });
    return route.abort();
  });

  await page.goto(`${ORIGIN}/edit`);
  await coursesAsked;

  return { posted, release: () => releaseCourses() };
}

/** Submit the form the way a click on Update would, even if the button is disabled. */
async function submit(page) {
  await page.evaluate(() => document.getElementById("noticeForm").requestSubmit());
  await page.waitForTimeout(400);
}

test.describe("Notice edit - save while the audience lists load (F-071)", () => {
  test("saving before the course list loads posts nothing; after it loads the saved audience is posted", async ({ page }) => {
    const { posted, release } = await mount(page, { courses: "hold" });

    const disabledWhileLoading = await page.locator("#saveBtn").isDisabled();
    await submit(page);
    for (const p of posted) {
      // If anything was posted it must carry the saved course, never an empty list.
      expect(p.getAll("course_master_pks[]"), "posted courses").toEqual(["11"]);
    }
    expect(posted, "nothing may be posted while the course list is loading").toHaveLength(0);
    expect(disabledWhileLoading, "Update is disabled while the course list loads").toBe(true);

    // Positive control: once the lists are in, Update posts the stored audience.
    release();
    await expect(page.locator("#saveBtn")).toBeEnabled();
    await expect(page.locator("#studentSelect option:checked")).toHaveCount(1);
    await page.locator("#saveBtn").click();
    await expect.poll(() => posted.length).toBe(1);
    expect(posted[0].getAll("course_master_pks[]")).toEqual(["11"]);
    expect(posted[0].get("ot_scope")).toBe("individual");
    expect(posted[0].getAll("student_pks[]")).toEqual(["2"]);
  });

  test("a failed course request never lets the form save", async ({ page }) => {
    const { posted } = await mount(page, { courses: "fail" });
    await page.waitForTimeout(300); // let the 500 reach the module

    await submit(page);
    for (const p of posted) {
      expect(p.getAll("course_master_pks[]"), "posted courses").toEqual(["11"]);
    }
    expect(posted, "nothing posted after the course list failed to load").toHaveLength(0);
    await expect(page.locator("#audienceLoadStatus")).toContainText("could not be loaded");
    expect(await page.locator("#saveBtn").isDisabled(), "Update stays disabled after a failure").toBe(true);
  });
});
