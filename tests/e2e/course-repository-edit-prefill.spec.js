/**
 * Regression test for PR #334 F-066.
 *
 * Course Repository "Edit" resets the upload form, starts an async prefill
 * (cascading selects plus a wait(450)) and shows the modal at once. The video link
 * and the download switch are filled LAST. Saving inside that window posted the reset
 * defaults - an empty video_link (the server clears the stored link) and
 * video_download_enabled=1 - so the link was erased and a restricted video became
 * downloadable.
 *
 * Runs the shipped window.crDocEdit module, lifted verbatim from show.blade.php, in a
 * real browser against the form fields it reads. Only fetch() is stubbed, to record
 * what would be posted.
 *
 * Run: npx playwright test tests/e2e/course-repository-edit-prefill.spec.js --project=chrome
 */
const { test, expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");

const BLADE = path.resolve(
  __dirname, "..", "..", "resources", "views", "admin", "course-repository", "show.blade.php");

function crDocEditSource() {
  const src = fs.readFileSync(BLADE, "utf8").replace(/\r\n/g, "\n");
  const start = src.indexOf("window.crDocEdit = (function() {");
  if (start === -1) throw new Error("window.crDocEdit module not found in show.blade.php");
  const end = src.indexOf("\n})();", start);
  if (end === -1) throw new Error("end of window.crDocEdit module not found");
  return src
    .slice(start, end + "\n})();".length)
    .replace("@json($allowedUploadExtensions)", '["pdf"]')
    .replace("@json($perFileMaxBytes)", "10485760")
    .replace("{{ $uploadTypesLabel }}", "pdf");
}

// Every field crDocEdit reads or writes for a Course document, with the options the
// prefill selects already present (so the cascade resolves without network).
const FORM_HTML = `
  <div id="uploadModal"></div>
  <div id="uploadFormErrors" class="d-none"></div>
  <form id="uploadForm">
    <input type="hidden" name="_token" value="t">
    <input type="hidden" id="upload_edit_pk">
    <input type="radio" name="category" id="category_course" value="Course" checked>
    <input type="radio" name="category" id="category_other" value="Other">
    <input type="radio" name="category" id="category_institutional" value="Institutional">
    <select id="course_name"><option value="">Select</option><option value="11">Course 11</option></select>
    <input type="date" id="session_date">
    <select id="subject_name"><option value="">Select</option><option value="21">Subject 21</option></select>
    <select id="timetable_name"><option value="">Select</option><option value="31">Topic 31</option></select>
    <select id="author_name"><option value="">Select</option><option value="41">Author 41</option></select>
    <select id="sector_master"><option value="">Select</option><option value="51">Sector 51</option></select>
    <select id="ministry_master"><option value="">Select</option><option value="61">Ministry 61</option></select>
    <input id="keywords_course">
    <input type="url" id="video_link_course" name="video_link_course">
    <input type="checkbox" id="video_download_enabled_course" value="1" checked>
    <input name="attachment_titles[]">
    <input type="file" name="attachments[]">
    <button type="submit" id="uploadBtn">Add Document</button>
  </form>`;

const SAVED = {
  pk: 9001,
  category: "Course",
  file_title: "Lecture notes",
  upload_document: "notes.pdf",
  detail: {
    course_master_pk: 11, course_name: "Course 11", session_date: "2026-09-01",
    subject_pk: 21, subject_name: "Subject 21", topic_pk: 31, topic_name: "Topic 31",
    author_name: 41, author_label: "Author 41", sector_master_pk: 51, sector_name: "Sector 51",
    ministry_master_pk: 61, ministry_name: "Ministry 61", keyword: "k",
    videolink: "https://www.youtube.com/watch?v=saved", video_download_enabled: 0,
  },
};

async function mount(page, { prefillThrows = false } = {}) {
  await page.route("**/*", (r) => r.abort());
  await page.setContent(`<!doctype html><html><body>${FORM_HTML}</body></html>`);
  await page.evaluate((throws) => {
    // Script-scope helpers the module calls; they manage the Choices.js course list.
    window.applyCourseStatusChoices = () => {};
    window.getCheckedCourseStatus = () => "active";
    window.syncCourseChoiceForEdit = () => {
      if (throws) throw new Error("prefill failed");
    };
    window.__posted = [];
    window.__errors = [];
    window.fetch = (url, opts) => {
      const fields = {};
      for (const [k, v] of opts.body.entries()) fields[k] = typeof v === "string" ? v : "[file]";
      window.__posted.push({ url, fields });
      return Promise.resolve(new Response(JSON.stringify({ success: false, error: "stub" }), { status: 422 }));
    };
    // The page's own submit handler hands edit-mode submits to crDocEdit.submit().
    document.getElementById("uploadForm").addEventListener("submit", (e) => {
      e.preventDefault();
      if (window.crDocEdit.isEditing()) {
        window.crDocEdit.submit(e.target, { showError: (m) => window.__errors.push([].concat(m).join(" ")) });
      }
    });
  }, prefillThrows);
  await page.addScriptTag({ content: crDocEditSource() });
}

test.describe("Course Repository edit - save during prefill (F-066)", () => {
  test("Edit then an immediate save does not post reset defaults", async ({ page }) => {
    await mount(page);

    // Open Edit and submit in the same task, before any prefill step can run.
    const state = await page.evaluate((data) => {
      window.__done = window.crDocEdit.enter(data);
      document.getElementById("uploadForm").requestSubmit();
      return { btnDisabled: document.getElementById("uploadBtn").disabled };
    }, SAVED);

    const posted = await page.evaluate(() => window.__posted);
    for (const p of posted) {
      // If anything was posted it must carry the saved values, never the form defaults.
      expect(p.fields.video_link, "posted video_link").toBe(SAVED.detail.videolink);
      expect(p.fields.video_download_enabled, "posted video_download_enabled").toBe("0");
    }
    expect(posted, "no update may be posted while prefill is pending").toHaveLength(0);
    expect(state.btnDisabled, "Update is disabled while prefill is pending").toBe(true);
    expect(await page.evaluate(() => window.__errors.join("|"))).toContain("still loading");

    // Positive control: once prefill finishes, saving posts the stored values.
    await page.evaluate(() => window.__done);
    expect(await page.locator("#uploadBtn").isDisabled()).toBe(false);
    await page.locator("#uploadBtn").click();
    const after = await page.evaluate(() => window.__posted);
    expect(after).toHaveLength(1);
    expect(after[0].url).toBe("/course-repository/document/9001/update");
    expect(after[0].fields.video_link).toBe(SAVED.detail.videolink);
    expect(after[0].fields.video_download_enabled).toBe("0");
  });

  test("a failed prefill never posts defaults", async ({ page }) => {
    await mount(page, { prefillThrows: true });

    await page.evaluate((data) => {
      try {
        window.__done = Promise.resolve(window.crDocEdit.enter(data)).catch(() => {});
      } catch (e) {
        window.__done = Promise.resolve();
      }
    }, SAVED);
    await page.evaluate(() => window.__done);
    await page.evaluate(() => document.getElementById("uploadForm").requestSubmit());

    expect(await page.evaluate(() => window.__posted), "nothing posted after a failed prefill").toHaveLength(0);
    expect(await page.evaluate(() => window.__errors.join("|"))).toContain("could not be loaded");
  });
});
