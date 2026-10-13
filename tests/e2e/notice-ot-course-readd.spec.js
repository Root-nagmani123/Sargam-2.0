/**
 * Regression test for PR #334 F-083.
 *
 * Removing and re-adding the course of a Personal Officer Trainee notice reset
 * Recipients to All and cleared the named trainee, and the save stored the notice for
 * the whole course.
 *
 * Unlike the lifted-script specs, this drives the real edit page: the controller renders
 * it for a stored notice (tests/e2e/support/NoticeOtEditPageFixture.php), Choices.js
 * loads from its CDN with the page's integrity hash, and the chips are clicked. The
 * lookups answer with what the real endpoints returned. The body the browser posts is
 * then replayed through update() and the stored audience asserted. All rolled back.
 *
 * Run: PHP_BINARY=/path/to/php npx playwright test tests/e2e/notice-ot-course-readd.spec.js --project=chrome
 * Red run: NOTICE_AUDIENCE_SCRIPTS_REF=<commit> renders that commit's audience_scripts partial.
 */
const { test, expect } = require("@playwright/test");
const { execFileSync } = require("child_process");
const fs = require("fs");
const os = require("os");
const path = require("path");

const ROOT = path.resolve(__dirname, "..", "..");
const PHP = process.env.PHP_BINARY || "php";
const PARTIAL = "resources/views/admin/NoticeNotification/partials/audience_scripts.blade.php";

function fixture(phase, out) {
  const env = { ...process.env, E2E_OUT: out, E2E_PHASE: phase };
  const ref = process.env.NOTICE_AUDIENCE_SCRIPTS_REF;
  if (ref) {
    const overlay = path.join(out, "overlay");
    const target = path.join(overlay, PARTIAL.replace("resources/views/", ""));
    fs.mkdirSync(path.dirname(target), { recursive: true });
    fs.writeFileSync(target, execFileSync("git", ["show", `${ref}:${PARTIAL}`], { cwd: ROOT }));
    env.E2E_VIEW_OVERLAY = overlay;
  }
  execFileSync(PHP, ["vendor/bin/phpunit", "tests/e2e/support/NoticeOtEditPageFixture.php"], { cwd: ROOT, env, stdio: "pipe" });
}

/** [name, value] pairs of a multipart/form-data body. */
function multipart(raw) {
  return raw.split(/\r?\n--/).map((part) => {
    const m = part.match(/name="([^"]+)"\r?\n\r?\n([\s\S]*)$/);
    return m ? [m[1], m[2].replace(/\r?\n$/, "")] : null;
  }).filter(Boolean);
}

async function openEditPage(page, out) {
  fixture("render", out);
  const fx = JSON.parse(fs.readFileSync(path.join(out, "fixture.json"), "utf8"));
  const host = new URL(fx.editUrl).host;
  const posts = [];

  await page.route("**/*", (route) => {
    const req = route.request();
    const u = new URL(req.url());
    if (u.host === "cdn.jsdelivr.net") return route.continue();
    if (u.host !== host) return route.abort();
    if (req.method() === "GET" && u.href === fx.editUrl) {
      return route.fulfill({ body: fs.readFileSync(path.join(out, "edit.html")), contentType: "text/html" });
    }
    for (const name of ["courses", "groupTypes", "students"]) {
      if (u.pathname === fx.paths[name]) return route.fulfill({ body: JSON.stringify(fx[name]), contentType: "application/json" });
    }
    if (req.method() === "POST") {
      posts.push(req.postData() || "");
      return route.fulfill({ body: "<p>saved</p>", contentType: "text/html" });
    }
    const file = path.join(ROOT, "public", decodeURIComponent(u.pathname));
    if (fs.existsSync(file) && fs.statSync(file).isFile()) return route.fulfill({ body: fs.readFileSync(file) });
    return route.fulfill({ status: 404, body: "" });
  });

  await page.goto(fx.editUrl);
  const submit = page.locator("form.notice-form button[type=submit]");
  await expect(submit).toBeEnabled();
  return { fx, posts, submit };
}

function state(page) {
  return page.evaluate(() => ({
    courses: Array.from(document.querySelectorAll("#courseSelect option:checked")).map((o) => o.value),
    scope: document.getElementById("otScopeSelect").value,
    students: Array.from(document.querySelectorAll("#studentSelect option:checked")).map((o) => o.value),
  }));
}

/** Submit, then replay the posted body through update(); returns the stored result. */
async function saveAndReplay(page, submit, posts, out) {
  await submit.click({ force: true });
  await expect.poll(() => posts.length).toBe(1);
  const parts = multipart(posts[0]);
  const values = (n) => parts.filter((p) => p[0] === n).map((p) => p[1]);
  const body = {
    course_master_pks: values("course_master_pks[]"),
    ot_scope: values("ot_scope")[0],
    student_pks: values("student_pks[]"),
    group_type_map_pks: values("group_type_map_pks[]"),
  };
  const confirmed = values("all_course_trainees_confirmed")[0];
  if (confirmed !== undefined) body.all_course_trainees_confirmed = confirmed;
  fs.writeFileSync(path.join(out, "posted.json"), JSON.stringify(body));
  fixture("replay", out);

  return { body, stored: JSON.parse(fs.readFileSync(path.join(out, "replay.json"), "utf8")) };
}

let out;
test.beforeEach(() => { out = fs.mkdtempSync(path.join(os.tmpdir(), "f083-")); });
test.afterEach(() => fs.rmSync(out, { recursive: true, force: true }));

test("removing and re-adding the course keeps Individual and the named trainee", async ({ page }) => {
  const { fx, posts, submit } = await openEditPage(page, out);
  await expect.poll(() => state(page)).toEqual({ courses: [fx.course], scope: "individual", students: [fx.student] });

  const chips = page.locator('xpath=//select[@id="courseSelect"]/ancestor::div[contains(concat(" ",normalize-space(@class)," ")," choices ")][1]');
  await chips.locator(`.choices__list--multiple [data-value="${fx.course}"] .choices__button`).click();
  await expect(page.locator("#otScopeBox")).toHaveClass(/d-none/);

  await chips.locator("input.choices__input--cloned").click();
  await chips.locator(`.choices__list--dropdown [data-value="${fx.course}"]`).click();
  await page.keyboard.press("Escape");
  await expect(page.locator("#studentBox")).not.toHaveClass(/d-none/);
  await expect(submit).toBeEnabled();
  await expect.poll(() => state(page)).toEqual({ courses: [fx.course], scope: "individual", students: [fx.student] });

  const { body, stored } = await saveAndReplay(page, submit, posts, out);
  expect(body).toMatchObject({ course_master_pks: [fx.course], ot_scope: "individual", student_pks: [fx.student] });
  expect(stored).toMatchObject({ errors: [], audience_mode: "individual", rows: [`C:${fx.course}`, `S:${fx.student}`], classmate_sees_it: false });
});

for (const [title, tick, expected] of [
  ["choosing All without the box is refused", false, (fx) => ({ errors: ["ot_scope"], audience_mode: "individual", rows: [`C:${fx.course}`, `S:${fx.student}`], classmate_sees_it: false })],
  ["choosing All with the box widens it to the course", true, (fx) => ({ errors: [], audience_mode: "all", rows: [`C:${fx.course}`], classmate_sees_it: true })],
]) {
  test(title, async ({ page }) => {
    const { fx, posts, submit } = await openEditPage(page, out);
    await expect.poll(() => state(page)).toEqual({ courses: [fx.course], scope: "individual", students: [fx.student] });

    await page.selectOption("#otScopeSelect", "all");
    if (tick) await page.check("#allCourseTraineesConfirmed");
    const { stored } = await saveAndReplay(page, submit, posts, out);
    expect(stored).toMatchObject(expected(fx));
  });
}
