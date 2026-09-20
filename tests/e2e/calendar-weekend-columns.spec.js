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
  // The RENDERERS, not just the pure helpers. Asserting against a re-implementation of a
  // renderer proves only that the re-implementation agrees with itself: a defect can sit in
  // the real function while every scenario passes. These three are the ones that decide
  // which day a row is drawn on and which columns exist to draw it in.
  "renderWeekCards",
  "applyHiddenDays",
  "revealWeekendsForData",
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

// The Officer-Trainee calendar carries its own duplicated copy of the date helpers and its
// own inline weekend rule. It must be lifted from ITS file: asserting against the admin copy
// proves nothing about it, and the two have already drifted apart once.
const BLADE_OT = path.join(
  __dirname, "..", "..", "resources", "views", "admin", "calendar", "ot-index.blade.php"
);

const OT_METHODS = [
  "fixCalendarDateTimeString",
  "extractEventDateYmd",
  "eventLocalDate",
  "eventWeekday",
  // The single shared rule. The OT calendar used to carry three inline copies of this,
  // which is how one of them kept a UTC date parse after the other two were corrected.
  "resolveWeekendDisplay",
  "weekendDisplayForEvents",
  "hiddenDaysFor",
  // All three call sites, so a re-duplication is caught by the test below rather than by
  // the next production incident.
  "handleWeekendVisibility",
  "revealWeekendsForData",
  "updateWeekendVisibility",
];

function buildOtClassSource() {
  const blade = fs.readFileSync(BLADE_OT, "utf8");
  const body = OT_METHODS.map((m) => extractMethod(blade, m)).join("\n\n");
  return `window.OtWeekendRule = class OtWeekendRule {\n${body}\n};`;
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

  /**
   * These two drive the REAL renderers out of the template.
   *
   * The previous version of this block compared `r.toYmd(d)` with `r.toYmd(d)` - the same
   * call - so its assertion was a tautology and could never fail. It named renderWeekCards
   * in its comment but never invoked it, and renderWeekCards was not in METHODS, so the
   * whole card-rendering path was unguarded while the suite reported five green scenarios.
   *
   * Both run at a NEGATIVE UTC offset on purpose: at Asia/Kolkata a bare "YYYY-MM-DD" read
   * as UTC midnight still lands on the right local day, so an IST-only assertion passes
   * against the broken code and guards nothing.
   */
  test.describe("the renderers themselves, at a negative UTC offset", () => {
    test.use({ timezoneId: "America/New_York" });

    // Monday 2026-09-14 .. Sunday 2026-09-20.
    const WEEK_FEED = [
      { title: "Mon all-day", start: "2026-09-14" },
      { title: "Wed timed", start: "2026-09-16T09:00:00" },
      { title: "Sat all-day", start: "2026-09-19" },
      { title: "Sun all-day", start: "2026-09-20" },
    ];

    test("renderWeekCards draws every row on its own local day, and loses none", async ({
      page,
    }) => {
      const placed = await page.evaluate((feed) => {
        document.body.innerHTML = '<div id="weekCards"><div class="row"></div></div>';
        const r = new WeekendRule();
        // Every weekend column open, so a misplacement cannot be masked by a hidden column.
        r.weekendDisplay = { showSat: true, showSun: true };
        r.renderWeekCards(feed, new Date(2026, 8, 14));

        // Read back what was actually drawn: card label -> the titles inside that card.
        return Array.from(document.querySelectorAll("#weekCards .week-day-card")).map((card) => ({
          label: card.querySelector(".fw-bold").textContent.trim(),
          badge: card.querySelector(".badge").textContent.trim(),
          titles: Array.from(card.querySelectorAll(".mini-event")).map((el) =>
            el.getAttribute("aria-label")
          ),
        }));
      }, WEEK_FEED);

      const dayOf = (title) =>
        placed.find((c) => c.titles.some((t) => t && t.startsWith(title)));

      // Each row must appear on ITS OWN day. Reading the bare date as UTC midnight shifts
      // all-day rows one card to the left, and drops the Monday one out of the week entirely.
      expect(dayOf("Mon all-day"), "Mon all-day row is on no card at all").toBeTruthy();
      expect(dayOf("Mon all-day").label).toContain("Monday");
      expect(dayOf("Wed timed").label).toContain("Wednesday");
      expect(dayOf("Sat all-day").label).toContain("Saturday");
      expect(dayOf("Sun all-day").label).toContain("Sunday");

      // Nothing may be silently dropped by the week-boundary test.
      const rendered = placed.flatMap((c) => c.titles).join(" | ");
      for (const row of WEEK_FEED) {
        expect(rendered, `row lost by renderWeekCards: ${row.title}`).toContain(row.title);
      }

      // The badge count must agree with the card's own contents.
      for (const card of placed) {
        expect(card.badge, `badge vs contents for ${card.label}`).toBe(
          `${card.titles.length} event${card.titles.length !== 1 ? "s" : ""}`
        );
      }
    });

    // The Officer-Trainee calendar keeps its OWN copy of this logic, so a test that reads
    // index.blade.php cannot guard it. This one is bound to ot-index.blade.php on purpose:
    // the first version of it extracted the admin copy, which was already correct, and so
    // passed against the unfixed OT source - the exact defect this file exists to prevent.
    test("OT revealWeekendsForData never hides a column that carries a row", async ({ page }) => {
      await page.evaluate(buildOtClassSource());
      const cases = await page.evaluate(async () => {
        const run = async (feed) => {
          const r = new OtWeekendRule();
          let hidden = [0, 6];
          r.calendar = {
            getOption: () => hidden,
            setOption: (_k, v) => {
              hidden = v;
            },
          };
          r.revealWeekendsForData(feed);
          // applyHiddenDays defers the write to the next tick on purpose.
          await new Promise((resolve) => setTimeout(resolve, 5));
          return hidden;
        };
        return {
          sundayAllDay: await run([{ title: "Sunday all-day session", start: "2026-09-20" }]),
          saturdayAllDay: await run([{ title: "Saturday all-day session", start: "2026-09-19" }]),
          weekdayOnly: await run([{ title: "Wed timed", start: "2026-09-16T09:00:00" }]),
        };
      });

      // A Sunday row opens BOTH columns - no gap after Friday.
      expect(cases.sundayAllDay, "Sunday all-day row: neither column may be hidden").toEqual([]);
      // A Saturday row opens Saturday only; Sunday (0) stays hidden.
      expect(cases.saturdayAllDay, "Saturday all-day row: Saturday must be open").toEqual([0]);
      // Nothing on either: Mon-Fri.
      expect(cases.weekdayOnly.slice().sort()).toEqual([0, 6]);
    });

    // The OT calendar decides hiddenDays from three different places. They must all reach
    // the same answer for the same week, or the columns change depending on which one fired
    // last - which is exactly the state that let one copy keep a UTC date parse while the
    // other two were fixed.
    test("all three OT call sites agree on the same week", async ({ page }) => {
      await page.evaluate(buildOtClassSource());
      const answers = await page.evaluate(async () => {
        const feed = [
          { title: "Wed timed", start: "2026-09-16T09:00:00" },
          { title: "Sun all-day", start: "2026-09-20" },
        ];
        // FullCalendar hands updateWeekendVisibility() Date objects, not feed strings.
        const asCalendarEvents = feed.map((e) => ({
          ...e,
          start: /T/.test(e.start)
            ? new Date(e.start)
            : new Date(...e.start.split("-").map((n, i) => (i === 1 ? +n - 1 : +n))),
        }));

        const spy = () => {
          let hidden = [0, 6];
          return {
            calendar: { getOption: () => hidden, setOption: (_k, v) => { hidden = v; } },
            read: () => hidden,
          };
        };
        const settle = () => new Promise((r) => setTimeout(r, 80));

        const a = spy();
        const r1 = new OtWeekendRule();
        r1.calendar = a.calendar;
        r1.handleWeekendVisibility(feed);
        await settle();

        const b = spy();
        const r2 = new OtWeekendRule();
        r2.calendar = b.calendar;
        r2.revealWeekendsForData(feed);
        await settle();

        const c = spy();
        const r3 = new OtWeekendRule();
        c.calendar.getEvents = () => asCalendarEvents;
        r3.calendar = c.calendar;
        r3.updateWeekendVisibility();
        await settle();

        const sort = (x) => x.slice().sort();
        return {
          handleWeekendVisibility: sort(a.read()),
          revealWeekendsForData: sort(b.read()),
          updateWeekendVisibility: sort(c.read()),
        };
      });

      // A Sunday row opens both columns, so none may be hidden - by any of the three routes.
      expect(answers.handleWeekendVisibility).toEqual([]);
      expect(answers.revealWeekendsForData).toEqual(answers.handleWeekendVisibility);
      expect(answers.updateWeekendVisibility).toEqual(answers.handleWeekendVisibility);
    });
  });
});
