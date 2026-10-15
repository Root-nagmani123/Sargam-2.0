/**
 * Regression test for PR #334 F-079.
 *
 * Changing the departments of a Staff/Faculty notice reset Recipients to All and
 * cleared the named employees, so the next save posted staff_scope=all and the
 * personal notice went to everyone in the selected departments.
 *
 * Same harness as notice-audience-edit-prefill.spec.js: the shipped audience_scripts
 * module, lifted from its Blade partial, runs in a real browser against the fields it
 * drives; the AJAX endpoints and the form POST are intercepted and the POSTed body is
 * what is asserted.
 *
 * Run: npx playwright test tests/e2e/notice-staff-department-change.spec.js --project=chrome
 */
const { test, expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");

const ROOT = path.resolve(__dirname, "..", "..");
// AUDIENCE_SCRIPTS_PARTIAL: run the spec against another commit's copy (the red run).
const PARTIAL = process.env.AUDIENCE_SCRIPTS_PARTIAL || path.join(ROOT, "resources", "views", "admin", "NoticeNotification", "partials", "audience_scripts.blade.php");
const JQUERY = path.join(ROOT, "vendor", "phpunit", "php-code-coverage", "src", "Report", "Html", "Renderer", "Template", "js", "jquery.min.js");
const ORIGIN = "http://notice.test";

const EMPLOYEES = { 5: [{ pk: 901, label: "Emp 901" }, { pk: 902, label: "Emp 902" }], 6: [{ pk: 903, label: "Emp 903" }] };

function moduleSource(preset) {
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
    .replace("@json($audiencePreset)", JSON.stringify(preset));
  if (/@json|\{\{/.test(js)) throw new Error("an unreplaced Blade expression is left in the module");
  return js;
}

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
      <select name="department_master_pks[]" id="departmentSelect" multiple><option value="5">Dept 5</option><option value="6">Dept 6</option></select>
      <div id="staffScopeBox" class="d-none"><select name="staff_scope" id="staffScopeSelect"><option value="all">All</option><option value="individual">Individual</option></select></div>
      <div id="employeeBox" class="d-none"><span id="employeeCount"></span><select name="employee_pks[]" id="employeeSelect" multiple></select></div>
    </div>
    <button type="submit" id="saveBtn">Update</button>
  </form>
  <script src="${ORIGIN}/jquery.js"></script>
  <script src="${ORIGIN}/module.js"></script>
</body></html>`;

async function mount(page, preset) {
  const posted = [];

  await page.route(`${ORIGIN}/**`, async (route) => {
    const url = new URL(route.request().url());
    const json = (body) => route.fulfill({ contentType: "application/json", body: JSON.stringify(body) });

    if (url.pathname === "/edit") return route.fulfill({ contentType: "text/html", body: PAGE });
    if (url.pathname === "/jquery.js") return route.fulfill({ contentType: "application/javascript", body: fs.readFileSync(JQUERY, "utf8") });
    if (url.pathname === "/module.js") return route.fulfill({ contentType: "application/javascript", body: moduleSource(preset) });
    if (url.pathname === "/save") {
      posted.push(new URLSearchParams(route.request().postData() || ""));
      return route.fulfill({ contentType: "text/html", body: "<p>saved</p>" });
    }
    if (url.pathname === "/ajax/getEmployees") {
      const departments = url.searchParams.getAll("department_master_pks[]");
      return json({ data: departments.flatMap((d) => EMPLOYEES[d] || []) });
    }
    if (url.pathname === "/ajax/getCourses") return json({ data: [] });
    return route.abort();
  });

  await page.goto(`${ORIGIN}/edit`);
  await expect(page.locator("#saveBtn")).toBeEnabled();

  return posted;
}

async function changeDepartmentsAndSave(page, posted, departments) {
  await page.selectOption("#departmentSelect", departments);
  await expect(page.locator("#saveBtn")).toBeEnabled();
  await page.locator("#saveBtn").click();
  await expect.poll(() => posted.length).toBe(1);

  return posted[0];
}

const STAFF = { target_audience: "Staff/Faculty", course_master_pks: [], ot_scope: "all", group_type_map_pks: [], student_pks: [] };

test.describe("Notice edit - changing departments keeps Recipients (F-079)", () => {
  test("a personal notice keeps Individual and its employee when a department is added", async ({ page }) => {
    const posted = await mount(page, { ...STAFF, department_master_pks: ["5"], staff_scope: "individual", employee_pks: ["901"] });
    await expect(page.locator("#employeeSelect option:checked")).toHaveCount(1);

    const body = await changeDepartmentsAndSave(page, posted, ["5", "6"]);

    expect(body.getAll("department_master_pks[]")).toEqual(["5", "6"]);
    expect(body.get("staff_scope"), "posted Recipients").toBe("individual");
    expect(body.getAll("employee_pks[]"), "posted employees").toEqual(["901"]);
  });

  test("a personal notice is not widened when its employee's department is removed", async ({ page }) => {
    const posted = await mount(page, { ...STAFF, department_master_pks: ["5"], staff_scope: "individual", employee_pks: ["901"] });
    await expect(page.locator("#employeeSelect option:checked")).toHaveCount(1);

    const body = await changeDepartmentsAndSave(page, posted, ["6"]);

    expect(body.get("staff_scope"), "posted Recipients").toBe("individual");
    expect(body.getAll("employee_pks[]"), "the named employee stays, shown as no longer listed").toEqual(["901"]);
  });

  test("control: a notice for All stays All and saves normally", async ({ page }) => {
    const posted = await mount(page, { ...STAFF, department_master_pks: ["5"], staff_scope: "all", employee_pks: [] });

    const body = await changeDepartmentsAndSave(page, posted, ["5", "6"]);

    expect(body.getAll("department_master_pks[]")).toEqual(["5", "6"]);
    expect(body.get("staff_scope")).toBe("all");
    expect(body.getAll("employee_pks[]")).toEqual([]);
  });
});

// PR #334 F-088: emptying the departments reset Recipients to All and cleared the
// employees, so re-adding the department saved a personal notice for all of it.
test.describe("Notice - removing and re-adding the department keeps Individual (F-088)", () => {
  async function clearAndReAdd(page, posted, departments) {
    await page.selectOption("#departmentSelect", []);
    await expect(page.locator("#staffScopeBox")).toHaveClass(/d-none/);
    expect(await page.locator("#staffScopeSelect").inputValue(), "Recipients while no department is selected").toBe("individual");

    await page.selectOption("#departmentSelect", departments);
    await expect(page.locator("#employeeBox")).not.toHaveClass(/d-none/);
    await expect(page.locator("#saveBtn")).toBeEnabled();
    await page.locator("#saveBtn").click();
    await expect.poll(() => posted.length).toBe(1);
    return posted[0];
  }

  test("edit: a personal notice keeps Individual and its employee", async ({ page }) => {
    const posted = await mount(page, { ...STAFF, department_master_pks: ["5"], staff_scope: "individual", employee_pks: ["901"] });
    await expect(page.locator("#employeeSelect option:checked")).toHaveCount(1);

    const body = await clearAndReAdd(page, posted, ["5"]);

    expect(body.getAll("department_master_pks[]")).toEqual(["5"]);
    expect(body.get("staff_scope"), "posted Recipients").toBe("individual");
    expect(body.getAll("employee_pks[]"), "posted employees").toEqual(["901"]);
  });

  test("create: the employee picked before the department was removed stays picked", async ({ page }) => {
    const posted = await mount(page, { ...STAFF, department_master_pks: [], staff_scope: "all", employee_pks: [] });
    await page.selectOption("#departmentSelect", ["5"]);
    await page.selectOption("#staffScopeSelect", "individual");
    await expect(page.locator('#employeeSelect option[value="902"]')).toHaveCount(1);
    await page.selectOption("#employeeSelect", ["902"]);

    const body = await clearAndReAdd(page, posted, ["5"]);

    expect(body.get("staff_scope"), "posted Recipients").toBe("individual");
    expect(body.getAll("employee_pks[]"), "posted employees").toEqual(["902"]);
  });

  test("control: switching the target audience away still resets the staff picks", async ({ page }) => {
    await mount(page, { ...STAFF, department_master_pks: ["5"], staff_scope: "individual", employee_pks: ["901"] });
    await expect(page.locator("#employeeSelect option:checked")).toHaveCount(1);

    await page.selectOption("#targetAudience", "Office trainee");

    expect(await page.locator("#staffScopeSelect").inputValue()).toBe("all");
    await expect(page.locator("#employeeSelect option")).toHaveCount(0);
  });
});
