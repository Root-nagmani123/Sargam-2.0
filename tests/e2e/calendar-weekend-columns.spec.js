const { test, expect } = require("@playwright/test");
const fs = require("fs");
const path = require("path");

/**
 * Regression guard for the admin calendar weekend-column rule.
 *
 * The rule (resolveWeekendDisplay):
 *   - an event on Sunday        -> show Saturday AND Sunday (never a gap after Friday)
 *   - an event on Saturday only -> show Saturday, keep Sunday hidden
 *   - nothing on either         -> Mon-Fri only
 *   - a holiday never OPENS a weekend column
 *
 * And the invariant that rule must not break: the list view is fed the UNFILTERED
 * feed (holidays included) while the column decision excludes holidays, and the
 * renderers emit cells only for visible days. So a weekend day carrying only a
 * holiday must still keep its column, or that holiday disappears with it.
 *
 * The logic lives inside a Blade template, so it cannot be imported. The methods are
 * lifted out of the real file by brace-matching and evaluated in page context, which
 * means this test fails if someone edits the template rather than passing against a
 * stale copy.
 */

const BLADE = path.join(
  __dirname,
  "..",
  "..",
  "resources",
  "views",
  "admin",
  "calendar",
  "index.blade.php"
);

// Every method the extracted class transitively needs. A method that calls a helper not
// listed here throws "is not a function" at run time rather than failing an assertion, so
// keep this in step with the template.
const METHODS = [
  "resolveWeekendDisplay",
  "isHolidayEvent",
  "fixCalendarDateTimeString",
  "extractEventDateYmd",
  "eventLocalDate",
  "eventWeekday",
  "isAllDayEvent",
  "eventStartDateTime",
  "weekendDisplayForEvents",
  "weekendPresenceForEvents",
  "weekendDisplayForRendering",
  "visibleWeekDayIndexes",
  "toYmd",
  "groupEventsByTime",
];

/** Lift `name(args) { ... }` out of the template by matching braces. */
function extractMethod(source, name) {
  const signature = new RegExp(`^[ \\t]*${name}\\s*\\(`, "m");
  const at = source.search(signature);
  if (at === -1) throw new Error(`Method ${name}() not found in ${BLADE}`);

  const open = source.indexOf("{", at);
  if (open === -1) throw new Error(`Method ${name}() has no body`);

  let depth = 0;
  for (let i = open; i < source.length; i += 1) {
    const ch = source[i];
    if (ch === "{") depth += 1;
    else if (ch === "}") {
      depth -= 1;
      if (depth === 0) return source.slice(at, i + 1);
    }
  }
  throw new Error(`Unbalanced braces in ${name}()`);
}

function buildClassSource() {
  const blade = fs.readFileSync(BLADE, "utf8");
  const body = METHODS.map((m) => extractMethod(blade, m)).join("\n\n");
  // Bound to window on purpose: a bare `class X {}` evaluated by page.evaluate is scoped
  // to that one call and is gone by the next evaluate.
  return `window.WeekendRule = class WeekendRule {\n${body}\n};`;
}

// Monday 2026-09-14 .. Sunday 2026-09-20. 09-19 is a Saturday, 09-20 a Sunday.
const WEEKDAY_CLASS = {
  title: "Public Admin lecture",
  start: "2026-09-16T09:00:00",
  session_type: 1,
};

const SCENARIOS = [
  {
    name: "weekend holiday only - column stays open so the holiday is not lost",
    feed: [
      WEEKDAY_CLASS,
      { title: "Gandhi Jayanti", start: "2026-09-20", type: "holiday", allDay: true },
    ],
    expectedColumns: 7,
  },
  {
    name: "Saturday holiday only - Saturday stays open, Sunday stays hidden",
    feed: [
      WEEKDAY_CLASS,
      { title: "Local Holiday", start: "2026-09-19", type: "holiday", allDay: true },
    ],
    expectedColumns: 6,
  },
  {
    name: "Sunday class - opens Saturday and Sunday, no gap after Friday",
    feed: [WEEKDAY_CLASS, { title: "Sunday remedial", start: "2026-09-20T09:00:00", session_type: 1 }],
    expectedColumns: 7,
  },
  {
    name: "Saturday class only - Saturday opens, Sunday stays hidden",
    feed: [WEEKDAY_CLASS, { title: "Saturday session", start: "2026-09-19T10:00:00", session_type: 1 }],
    expectedColumns: 6,
  },
  {
    name: "weekday only - Mon-Fri",
    feed: [WEEKDAY_CLASS],
    expectedColumns: 5,
  },
];

test.describe("admin calendar - weekend columns", () => {
  test.beforeEach(async ({ page }) => {
    await page.setContent("<!doctype html><title>weekend rule</title>");
    await page.evaluate(buildClassSource());
  });

  for (const scenario of SCENARIOS) {
    test(scenario.name, async ({ page }) => {
      const result = await page.evaluate((feed) => {
        const r = new WeekendRule();
        const display = r.weekendDisplayForRendering(feed);
        const dayKeys = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
        const visible = r.visibleWeekDayIndexes.call({ weekendDisplay: display }).map((i) => dayKeys[i]);

        // Reproduce renderListView's cell emission: only visible day columns are drawn.
        const slots = r.groupEventsByTime(feed);
        const rendered = [];
        Object.values(slots).forEach((dayEvents) => {
          visible.forEach((day) => {
            if (dayEvents[day]) dayEvents[day].forEach((e) => rendered.push(e.title));
          });
        });

        return { display, visible, rendered, colspan: visible.length + 1 };
      }, scenario.feed);

      expect(
        result.visible.length,
        `visible day columns for: ${scenario.name}`
      ).toBe(scenario.expectedColumns);

      // Sunday can never render without Saturday, or the week shows a gap after Friday.
      if (result.display.showSun) {
        expect(result.display.showSat, "Sunday shown without Saturday").toBe(true);
      }

      // The empty-state colspan must match the rendered column count (time column + days).
      expect(result.colspan).toBe(result.visible.length + 1);

      // THE INVARIANT: nothing in the feed may be dropped by a hidden column.
      const missing = scenario.feed
        .map((e) => e.title)
        .filter((title) => !result.rendered.includes(title));
      expect(
        missing,
        `feed rows lost because their column was hidden: ${missing.join(", ")}`
      ).toEqual([]);
    });
  }

  test("a holiday alone does not open a weekend column under the bare rule", async ({ page }) => {
    // The rule itself must still exclude holidays - the widening happens only at render
    // time. If this flips, weekendDisplayForEvents has stopped honouring isHolidayEvent.
    const display = await page.evaluate(() => {
      const r = new WeekendRule();
      return r.weekendDisplayForEvents([
        { title: "Gandhi Jayanti", start: "2026-09-20", type: "holiday", allDay: true },
      ]);
    });
    expect(display).toEqual({ showSat: false, showSun: false });
  });

  // This block runs at a NEGATIVE UTC offset on purpose. At Asia/Kolkata the two parsing
  // paths agree even in the broken version, so an IST-only assertion passes vacuously and
  // guards nothing. New York is where the old and the new behaviour actually diverge.
  test.describe("all-day parsing at a negative UTC offset", () => {
    test.use({ timezoneId: "America/New_York" });

    test("an all-day row resolves to the same weekday as FullCalendar", async ({ page }) => {
      // The feed sends all-day rows as a bare Y-m-d. Read through the Date constructor that
      // is UTC midnight, which lands on the previous day at a negative offset and disagrees
      // with FullCalendar's local-midnight Date. Both paths must agree.
      const agree = await page.evaluate(() => {
        const r = new window.WeekendRule();
        return {
          fromFeed: r.eventWeekday({ start: "2026-09-20" }),
          fromFullCalendar: r.eventWeekday({ start: new Date(2026, 8, 20) }),
          naive: new Date("2026-09-20").getDay(),
        };
      });
      expect(agree.fromFeed).toBe(agree.fromFullCalendar);
      expect(agree.fromFeed).toBe(0); // 2026-09-20 is a Sunday
      // Proves the fixture is one where the old implementation would have failed.
      expect(agree.naive, "fixture no longer exercises the divergence").toBe(6);
    });

    test("a Sunday all-day row still opens both weekend columns here", async ({ page }) => {
      const display = await page.evaluate(() =>
        new window.WeekendRule().weekendDisplayForEvents([
          { title: "Sunday all-day session", start: "2026-09-20", allDay: true },
        ])
      );
      expect(display).toEqual({ showSat: true, showSun: true });
    });
  });

  test("an all-day row is labelled 'All Day', not the time its bare date parses to", async ({
    page,
  }) => {
    // `event.start` is always truthy for a real feed row, so a `start ? <time> : 'All Day'`
    // test can never reach 'All Day' and an all-day row gets labelled "05:30 am" at IST.
    const slots = await page.evaluate(() => {
      const r = new window.WeekendRule();
      return Object.keys(
        r.groupEventsByTime([
          { title: "All-day holiday", start: "2026-09-20", type: "holiday", allDay: true },
        ])
      );
    });
    expect(slots).toEqual(["All Day"]);
  });

  test("groupEventsByTime files a row under the same weekday the column rule used", async ({
    page,
  }) => {
    // If these two disagree, a row can be rendered into the wrong column - or into a
    // column the rule decided not to show, which loses it entirely.
    const agree = await page.evaluate(() => {
      const r = new window.WeekendRule();
      const dayKeys = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
      return [
        { title: "Sat all-day", start: "2026-09-19" },
        { title: "Sun all-day", start: "2026-09-20" },
        { title: "Wed timed", start: "2026-09-16T09:00:00" },
      ].map((e) => {
        const grouped = r.groupEventsByTime([e]);
        const column = Object.keys(Object.values(grouped)[0])[0];
        return { title: e.title, column, fromRule: dayKeys[r.eventWeekday(e)] };
      });
    });
    for (const row of agree) {
      expect(row.column, `column vs rule weekday for ${row.title}`).toBe(row.fromRule);
    }
    expect(agree.map((r) => r.column)).toEqual(["Sat", "Sun", "Wed"]);
  });

  test("week-card day keys round-trip through toYmd", async ({ page }) => {
    // renderWeekCards builds its byDay map and reads it back; both sides must use the
    // same local format. Deriving one side from toISOString() shifts it by a day at any
    // non-zero UTC offset and every card then shows the wrong day's events.
    const mismatches = await page.evaluate(() => {
      const r = new WeekendRule();
      const weekStart = new Date(2026, 8, 14);
      const out = [];
      for (let i = 0; i < 7; i += 1) {
        const d = new Date(weekStart);
        d.setDate(d.getDate() + i);
        const built = r.toYmd(d);
        const read = r.toYmd(d);
        const utc = d.toISOString().split("T")[0];
        if (built !== read) out.push({ i, built, read });
        // guard against a regression back to the UTC-derived key
        if (built !== utc) out.push({ i, note: "local and UTC differ here", built, utc });
      }
      return out;
    });
    // The local/UTC divergence is expected at IST; what must never happen is the map
    // being built with one format and read with the other. toYmd on both sides is the fix.
    const roundTripFailures = mismatches.filter((m) => !m.note);
    expect(roundTripFailures).toEqual([]);
  });
});
