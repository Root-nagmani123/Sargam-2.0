const { test, expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");
const { extractMethod } = require("./support/blade-methods");

/**
 * Regression guard for the weekly-view card (renderListEvent) in the admin calendar.
 *
 * The card writes the event's group name, title and a summary label into quoted
 * attributes (data-group, aria-label, title). Group names come from the group master
 * and topics from the timetable, both typed by users, so a '"' in either must stay
 * inside the attribute. An escaper that only handles & < > lets
 *   A" onfocus="..." autofocus x="
 * add an event handler that runs as soon as the card is drawn.
 *
 * The methods are lifted out of the real template (see support/blade-methods.js), so
 * this fails if the template regresses rather than passing against a stale copy.
 */

const BLADE = path.join(__dirname, "..", "..", "resources", "views", "admin", "calendar", "index.blade.php");

const METHODS = [
  "escapeHtml",
  "renderListEvent",
  "isBreakEvent",
  "isAllDayEvent",
  "fixCalendarDateTimeString",
  "eventStartDateTime",
];

function buildClassSource() {
  const blade = fs.readFileSync(BLADE, "utf8");
  const body = METHODS.map((m) => extractMethod(blade, m, BLADE)).join("\n\n");
  return `window.WeekCard = class WeekCard {\n${body}\n};`;
}

const HOSTILE_GROUP = 'A" onfocus="window.__injected=1" autofocus tabindex="0" x="';
const HOSTILE_TITLE = 'x" onmouseover="window.__injected=2" data-z="';
const QUOTED_TITLE = 'Session on "Ethics" and the \'Code\'';

const CASES = [
  { name: "a group name carrying a quote", group: HOSTILE_GROUP, title: "Normal session" },
  { name: "a topic carrying a quote", group: "A", title: HOSTILE_TITLE },
  { name: "an ordinary topic with quotes", group: "Full Group", title: QUOTED_TITLE },
];

test.describe("calendar week-view card escaping", () => {
  test.beforeEach(async ({ page }) => {
    await page.setContent("<!doctype html><title>week card</title>");
    await page.evaluate(buildClassSource());
  });

  test("escapeHtml escapes quotes as well as & < >", async ({ page }) => {
    const out = await page.evaluate(() => new window.WeekCard().escapeHtml(`a"b'<c>&`));
    expect(out).toBe("a&quot;b&#39;&lt;c&gt;&amp;");
  });

  for (const c of CASES) {
    test(`${c.name} stays inside its attribute`, async ({ page }) => {
      const result = await page.evaluate(async ({ group, title }) => {
        const event = {
          id: 101,
          title,
          start: "2026-07-20T09:30:00",
          end: "2026-07-20T10:30:00",
          extendedProps: { group_name: group, faculty_name: "Faculty", venue_name: "VH" },
        };
        const host = document.createElement("div");
        host.innerHTML = new window.WeekCard().renderListEvent(event);
        document.body.appendChild(host);
        // autofocus is applied asynchronously; give an injected handler the chance to run.
        await new Promise((r) => setTimeout(r, 300));
        const card = host.querySelector("article.tt-card");
        return {
          names: Array.from(card.attributes).map((a) => a.name).sort(),
          dataGroup: card.getAttribute("data-group"),
          ariaLabel: card.getAttribute("aria-label"),
          titleAttr: card.getAttribute("title"),
          visibleTitle: card.querySelector(".tt-card-title").textContent,
          injected: window.__injected ?? null,
        };
      }, c);

      expect(result.names).toEqual(["aria-label", "class", "data-group", "data-id", "role", "tabindex", "title"]);
      expect(result.dataGroup).toBe(c.group);
      expect(result.ariaLabel.startsWith(c.title)).toBe(true);
      expect(result.titleAttr).toBe(result.ariaLabel);
      expect(result.visibleTitle).toBe(c.title);
      expect(result.injected).toBeNull();
    });
  }
});
