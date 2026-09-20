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
  "feedRange",
  "groupEventsByTime",
  // The RENDERERS, not just the pure helpers. Asserting against a re-implementation of a
  // renderer proves only that the re-implementation agrees with itself: a defect can sit in
  // the real function while every scenario passes. These three are the ones that decide
  // which day a row is drawn on and which columns exist to draw it in.
  "renderWeekCards",
  "applyHiddenDays",
  "revealWeekendsForData",
];

/**
 * Index of the closing quote of the string or template literal starting at `start`.
 * A `${ ... }` hole inside a template may itself contain braces, quotes and further
 * templates, so it is walked rather than scanned for the next backtick.
 */
function skipLiteral(source, start) {
  const quote = source[start];
  for (let i = start + 1; i < source.length; i += 1) {
    const ch = source[i];
    if (ch === "\\") { i += 1; continue; }
    if (ch === quote) return i;
    if (quote === "`" && ch === "$" && source[i + 1] === "{") {
      let depth = 1;
      i += 2;
      for (; i < source.length && depth > 0; i += 1) {
        const c = source[i];
        if (c === "\\") { i += 1; continue; }
        if (c === "'" || c === '"' || c === "`") { i = skipLiteral(source, i); continue; }
        if (c === "{") depth += 1;
        else if (c === "}") depth -= 1;
      }
      i -= 1;
    }
  }
  return source.length;
}

/**
 * Lift `name(args) { ... }` out of the template by matching braces.
 *
 * Comments and string/template literals are skipped, because a brace inside one is
 * punctuation and not structure. Counting them is not hypothetical: a comment in
 * ot-index.blade.php quoting `forEach(i => {` left the depth permanently one too high,
 * so extracting renderWeekCards() from that file ran past the end of the method and
 * returned a fragment that does not parse — with a syntax error that names neither the
 * comment nor the file.
 *
 * Known limit: a regular-expression literal is not tracked, so an unbalanced brace
 * inside one (`/\}/`) would still be counted. None of the extracted methods contains one.
 */
function extractMethod(source, name) {
  // `async ` may precede the name: loadListView() is declared async, and without this
  // the extractor reports "method not found" for it - a failure that looks like a missing
  // method rather than an unsupported declaration form.
  const signature = new RegExp(`^[ \\t]*(?:async\\s+)?${name}\\s*\\(`, "m");
  const at = source.search(signature);
  if (at === -1) throw new Error(`Method ${name}() not found in ${BLADE}`);

  const open = source.indexOf("{", at);
  if (open === -1) throw new Error(`Method ${name}() has no body`);

  let depth = 0;
  for (let i = open; i < source.length; i += 1) {
    const ch = source[i];
    const next = source[i + 1];

    if (ch === "/" && next === "/") {
      const eol = source.indexOf("\n", i);
      if (eol === -1) break;
      i = eol;
      continue;
    }
    if (ch === "/" && next === "*") {
      const end = source.indexOf("*/", i + 2);
      if (end === -1) break;
      i = end + 1;
      continue;
    }
    if (ch === "'" || ch === '"' || ch === "`") {
      i = skipLiteral(source, i);
      continue;
    }

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
  "isAllDayEvent",
  "eventStartDateTime",
  "toYmd",
  // The single shared rule. The OT calendar used to carry three inline copies of this,
  // which is how one of them kept a UTC date parse after the other two were corrected.
  "resolveWeekendDisplay",
  "weekendDisplayForEvents",
  "hiddenDaysFor",
  // Both call sites that push the rule into FullCalendar, so a re-duplication is caught by
  // the test below rather than by the next production incident.
  //
  // handleWeekendVisibility() used to be listed here as a third "call site". It never was
  // one — it had no caller anywhere in the repository — so that third of the assertion
  // guarded nothing in production. The method is gone; if a lifecycle hook ever needs it
  // back, add it AND its caller, and restore it to this list.
  "revealWeekendsForData",
  "updateWeekendVisibility",
  // The OT list view decides its own columns here, separately from the grid above. This is
  // the function that reads the decision, so this is the one the rule has to be checked at.
  "computeActiveDays",
  "defaultWeekdays",
  "groupEventsByTime",
  // The real renderer, so the all-day label and the card set are asserted against what is
  // actually drawn rather than against a re-implementation.
  "renderWeekCards",
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

    // The OT calendar decides hiddenDays from two different places. They must reach the
    // same answer for the same week, or the columns change depending on which one fired
    // last - which is exactly the state that let one copy keep a UTC date parse while the
    // other was fixed.
    test("both OT call sites agree on the same week", async ({ page }) => {
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
          revealWeekendsForData: sort(b.read()),
          updateWeekendVisibility: sort(c.read()),
        };
      });

      // A Sunday row opens both columns, so none may be hidden - by either route.
      expect(answers.revealWeekendsForData).toEqual([]);
      expect(answers.updateWeekendVisibility).toEqual(answers.revealWeekendsForData);
    });

    /**
     * The OT LIST VIEW decides its columns in computeActiveDays(), which is a separate
     * decision from the grid's hiddenDays above and was the last place in that file still
     * reading a bare feed date through the Date constructor.
     *
     * The failure this guards is not a misplaced row but a MISSING one: renderListView()
     * emits a <td> only for a day in activeDays, so if the column rule resolves a row to a
     * different weekday than groupEventsByTime() files it under, the row has no cell to be
     * drawn in and disappears without trace.
     */
    test("OT list view: the column rule and the row bucketing agree, so no row is dropped", async ({
      page,
    }) => {
      await page.evaluate(buildOtClassSource());
      const result = await page.evaluate(() => {
        const r = new OtWeekendRule();
        const weekStart = new Date(2026, 8, 14); // Mon 14 Sep 2026, local midnight
        const feed = [
          { title: "Wed timed", start: "2026-09-16T09:00:00" },
          { title: "Sat all-day", start: "2026-09-19" },
          { title: "Sun all-day", start: "2026-09-20" },
        ];

        const activeDays = r.computeActiveDays(feed, weekStart);
        const slots = r.groupEventsByTime(feed);

        // Reproduce renderListView()'s cell emission: only active day columns are drawn.
        const rendered = [];
        Object.values(slots).forEach((dayEvents) => {
          activeDays.forEach((day) => {
            if (dayEvents[day.short]) dayEvents[day.short].forEach((e) => rendered.push(e.title));
          });
        });

        return {
          columns: activeDays.map((d) => d.short),
          bucketed: Object.values(slots).flatMap((de) => Object.keys(de)),
          dropped: feed.map((e) => e.title).filter((t) => !rendered.includes(t)),
        };
      });

      expect(
        result.dropped,
        `rows lost because computeActiveDays() never opened their column: ${result.dropped.join(", ")}`
      ).toEqual([]);
      // Every day a row was filed under must be a day the column rule opened.
      for (const day of result.bucketed) {
        expect(result.columns, `row filed under ${day}, which has no column`).toContain(day);
      }
      // A Sunday row opens Saturday too - no gap after Friday, the same rule as the grid.
      expect(result.columns).toEqual(["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"]);
    });

    /**
     * The OT week cards follow the same weekend rule as the OT table above them.
     *
     * Product owner decision, 2026-09-20: adopt the rule on the cards. Until then the cards
     * rendered Mon–Sun unconditionally while the grid and the table hid empty weekend days,
     * so one screen showed a Saturday card that the two beside it had already decided was
     * not worth a column.
     *
     * The cards are driven by the SAME computeActiveDays() result the header and the table
     * use — deliberately not by a second copy of the rule. A second copy is what produced
     * F-011 and F-013, and a rule that cannot disagree with itself is the only kind that
     * stays fixed.
     */
    test("OT week cards render the same days the OT table does", async ({ page }) => {
      await page.evaluate(buildOtClassSource());
      const cases = await page.evaluate(() => {
        const weekStart = new Date(2026, 8, 14); // Mon 14 Sep 2026
        const run = (feed) => {
          document.body.innerHTML = '<div id="weekCards"><div class="row"></div></div>';
          const r = new OtWeekendRule();
          const activeDays = r.computeActiveDays(feed, weekStart);
          r.renderWeekCards(feed, weekStart, activeDays);
          const labels = Array.from(
            document.querySelectorAll("#weekCards .week-day-card .fw-bold")
          ).map((el) => el.textContent.trim().split(" ")[0]);
          const drawn = Array.from(document.querySelectorAll("#weekCards .mini-event")).map((el) =>
            el.getAttribute("aria-label")
          );
          return {
            tableColumns: activeDays.map((d) => d.short),
            cardDays: labels,
            drawn,
            dropped: feed.map((e) => e.title).filter((t) => !drawn.some((a) => a && a.startsWith(t))),
          };
        };
        return {
          weekdayOnly: run([{ title: "Wed class", start: "2026-09-16T09:00:00" }]),
          saturday: run([
            { title: "Wed class", start: "2026-09-16T09:00:00" },
            { title: "Sat session", start: "2026-09-19T10:00:00" },
          ]),
          sunday: run([
            { title: "Wed class", start: "2026-09-16T09:00:00" },
            { title: "Sun remedial", start: "2026-09-20T09:00:00" },
          ]),
        };
      });

      // Nothing on either weekend day: Mon-Fri, on the cards as well as the table.
      expect(cases.weekdayOnly.cardDays).toEqual([
        "Monday", "Tuesday", "Wednesday", "Thursday", "Friday",
      ]);

      // A Saturday session opens Saturday and leaves Sunday shut.
      expect(cases.saturday.cardDays).toEqual([
        "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday",
      ]);

      // A Sunday session opens both - no gap after Friday.
      expect(cases.sunday.cardDays).toEqual([
        "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday",
      ]);

      // The cards and the table must never disagree about which days exist, and no row may
      // be lost to a card that was not rendered.
      const dayOfShort = { Mon: "Monday", Tue: "Tuesday", Wed: "Wednesday", Thu: "Thursday",
                           Fri: "Friday", Sat: "Saturday", Sun: "Sunday" };
      for (const [name, c] of Object.entries(cases)) {
        expect(c.cardDays, `cards vs table disagree for: ${name}`)
          .toEqual(c.tableColumns.map((s) => dayOfShort[s]));
        expect(c.dropped, `rows lost by renderWeekCards for: ${name}`).toEqual([]);
      }
    });

    /**
     * An all-day row must not be labelled with the time its bare date happens to parse to.
     * groupEventsByTime() was corrected for the time-SLOT label; the per-event chip inside
     * renderWeekCards() kept the old parse, so the same row carried "All Day" in one place
     * and "05:30 am" in the other - including in the aria-label read to assistive tech.
     *
     * Bound to BOTH files: the chip is duplicated, and a test that reads one proves nothing
     * about the other.
     */
    for (const which of ["admin", "OT"]) {
      test(`${which} week card labels an all-day row without inventing a clock time`, async ({
        page,
      }) => {
        await page.evaluate(which === "OT" ? buildOtClassSource() : buildClassSource());
        const card = await page.evaluate((isOt) => {
          document.body.innerHTML = '<div id="weekCards"><div class="row"></div></div>';
          const r = isOt ? new OtWeekendRule() : new WeekendRule();
          // Admin renderWeekCards() consults the weekend rule; OT renders Mon-Sun always.
          r.weekendDisplay = { showSat: true, showSun: true };
          const feed = [{ title: "Gandhi Jayanti", start: "2026-09-19", type: "holiday", allDay: true }];
          r.renderWeekCards(feed, new Date(2026, 8, 14));
          const chip = document.querySelector("#weekCards .mini-event");
          const time = chip && chip.querySelector(".mini-time");
          return {
            slotLabel: Object.keys(r.groupEventsByTime(feed))[0],
            chipTime: time ? time.textContent.trim() : "",
            aria: chip ? chip.getAttribute("aria-label") : null,
          };
        }, which === "OT");

        // The label the slot already gets right, for comparison.
        expect(card.slotLabel).toBe("All Day");
        // The chip must not contradict it with a fabricated time.
        expect(card.chipTime, "week-card chip shows a clock time for an all-day row").not.toMatch(
          /\d{1,2}:\d{2}/
        );
        expect(card.aria, "aria-label announces a clock time for an all-day row").not.toMatch(
          /\d{1,2}:\d{2}/
        );
      });
    }
  });
});

/**
 * F-015: both symbols were deleted from index.blade.php in an earlier round and left in
 * ot-index.blade.php. A grep is the closure evidence the finding asked for, so it is the
 * assertion here - source-level, not behavioural, because dead code has no behaviour.
 */
test("no calendar view keeps the uncalled handleWeekendVisibility() or its dead flag", () => {
  for (const file of [BLADE, BLADE_OT]) {
    const source = fs.readFileSync(file, "utf8");
    expect(source, `${path.basename(file)} still defines handleWeekendVisibility()`).not.toMatch(
      /^\s*handleWeekendVisibility\s*\(/m
    );
    expect(source, `${path.basename(file)} still carries the dead eventsLoaded flag`).not.toContain(
      "eventsLoaded"
    );
  }
});

/**
 * F-016: the extractor must survive a comment that quotes unbalanced code. ot-index's
 * renderWeekCards() carries exactly such a comment, which is what made this necessary.
 */
test("extractMethod lifts every listed method from both templates", () => {
  const cases = [
    [BLADE, METHODS],
    [BLADE_OT, OT_METHODS],
  ];
  for (const [file, methods] of cases) {
    const source = fs.readFileSync(file, "utf8");
    for (const name of methods) {
      const body = extractMethod(source, name);
      expect(() => new Function(`return class T {${body}}`)()).not.toThrow();
    }
  }
});
/**
 * F-018: the list view's weekend columns are decided from the feed it fetched, so the feed
 * has to cover the week being DRAWN. loadListView() sent only course_id, and the endpoint
 * defaults a missing range to the CURRENT CALENDAR MONTH
 * (CalendarController::fullCalendarDetails: Carbon::now()->startOfMonth()/endOfMonth(), then
 * whereDate('START_DATE','>=',start) and whereDate('END_DATE','<=',end)). Every week the user
 * can page to outside that month therefore came back empty - not because the week was empty,
 * but because it was never requested - and weekendDisplayForRendering([]) then hid Saturday
 * and Sunday for it.
 *
 * Proved against the live schema before the fix: with no start/end the endpoint returns
 * 2026-09-04..2026-09-20; with start=2026-10-12&end=2026-10-18 it returns the Sunday holiday
 * on 2026-10-18 that the list view could not previously see.
 *
 * This asserts the REQUEST, because that is where the defect is. The URL is captured from a
 * stubbed fetch while the real loadListView() runs, so it cannot pass against a
 * re-implementation of the method.
 */
function buildListViewSource(bladePath, className) {
  const blade = fs.readFileSync(bladePath, "utf8");
  const body = ["loadListView", "toYmd", "mondayOf"]
    .map((m) => extractMethod(blade, m))
    .join("\n\n");
  return `window.${className} = class ${className} {\n${body}\n};`;
}

/** Monday of the week `offset` weeks from today, as YYYY-MM-DD, computed in the test. */
function expectedWeekBounds(offset) {
  const today = new Date();
  const dow = today.getDay();
  const monday = new Date(
    today.getFullYear(),
    today.getMonth(),
    today.getDate() - dow + (dow === 0 ? -6 : 1)
  );
  monday.setDate(monday.getDate() + offset * 7);
  const sunday = new Date(monday);
  sunday.setDate(sunday.getDate() + 6);
  const ymd = (d) =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(
      d.getDate()
    ).padStart(2, "0")}`;
  return { start: ymd(monday), end: ymd(sunday) };
}

for (const [label, bladePath, className] of [
  ["admin", BLADE, "AdminListViewFetch"],
  ["OT", BLADE_OT, "OtListViewFetch"],
]) {
  // offset 0 is this week; +6 and -6 are far enough to leave the current calendar month
  // whatever day the suite runs on, which is the whole point of the finding.
  for (const offset of [0, 6, -6]) {
    test(`${label} loadListView requests the displayed week, not the current month (offset ${offset})`, async ({
      page,
    }) => {
      await page.setContent("<!doctype html><title>list view fetch</title>");
      await page.evaluate(buildListViewSource(bladePath, className));

      const result = await page.evaluate(
        async ({ cls, weekOffset }) => {
          const requested = [];
          const errors = [];
          const realError = console.error;
          console.error = (...a) => errors.push(a.map(String).join(" "));

          window.CalendarConfig = {
            api: { events: "https://example.invalid/calendar/full-calendar-details" },
          };
          window.fetch = (url) => {
            requested.push(String(url));
            return Promise.resolve({ ok: true, json: async () => [] });
          };
          document.getElementById = () => null;

          const inst = Object.create(window[cls].prototype);
          inst.listViewWeekOffset = weekOffset;
          inst.selectedCourseId = null;
          // Everything loadListView calls after the fetch. Stubbed so this test is about the
          // REQUEST and fails for one reason only.
          inst.getEventsForWeek = () => [];
          inst.computeActiveDays = () => [];
          inst.weekendDisplayForRendering = () => ({ showSat: false, showSun: false });
          inst.updateTableHeader = () => {};
          inst.renderListView = () => {};
          inst.renderWeekCards = () => {};
          inst.updateWeekRangeText = () => {};
          inst.updatePortalToolbarTitle = () => {};

          await inst.loadListView();
          console.error = realError;
          return { requested, errors };
        },
        { cls: className, weekOffset: offset }
      );

      expect(result.errors, "loadListView threw and its catch swallowed it").toEqual([]);
      expect(result.requested, "loadListView did not call fetch exactly once").toHaveLength(1);

      const url = new URL(result.requested[0]);
      const { start, end } = expectedWeekBounds(offset);

      expect(
        url.searchParams.get("start"),
        "no start param: the endpoint will default to the current calendar month"
      ).toBe(start);
      expect(
        url.searchParams.get("end"),
        "no end param: the endpoint will default to the current calendar month"
      ).toBe(end);
    });
  }
}

/**
 * F-019: the GRID's feed was requested one day early, and this PR made that feed decide the
 * weekend columns.
 *
 * fetchEvents() formatted FullCalendar's range with `info.start.toISOString().split('T')[0]`.
 * At a POSITIVE UTC offset - IST, +05:30, which is where this runs - a local-midnight Date
 * serialises to the PREVIOUS day, so the feed was requested from one day before the view.
 * Before this PR that only over-fetched; `hiddenDays` was the fixed [0,6] and
 * updateWeekendVisibility() was an empty no-op, so no layout depended on it. Once
 * revealWeekendsForData(filteredData) began deciding the columns from that same feed, a
 * session on the day BEFORE the view could open a weekend column INSIDE it.
 *
 * Proved against the live schema before the fix: viewing Mon 2026-06-29 .. Sun 2026-07-05
 * (which has no weekend session at all), the request went out as start=2026-06-28 - a Sunday -
 * and the endpoint returned the 2026-06-28 class "Inaugration", so the real
 * weekendDisplayForEvents() answered {showSat:true, showSun:true} instead of
 * {showSat:false, showSun:false}.
 *
 * The end bound is asserted too, and that is not padding. `info.end` is EXCLUSIVE, and the
 * UTC shift was performing the exclusive->inclusive conversion BY ACCIDENT. A fix that swaps
 * toYmd() in on both lines without decrementing the end repairs the start and breaks the end
 * in the same stroke, and only an assertion on both catches that.
 */
function buildFetchEventsSource(bladePath, className) {
  const blade = fs.readFileSync(bladePath, "utf8");
  // feedRange() is part of fetchEvents' surface since F-021; without it the lifted
  // fetchEvents throws "this.feedRange is not a function" and the assertions never run.
  const body = ["fetchEvents", "toYmd", "feedRange"]
    .map((m) => extractMethod(blade, m))
    .join("\n\n");
  return `window.${className} = class ${className} {\n${body}\n};`;
}

/** Run the real fetchEvents() for a view range and return the URL it actually requested. */
async function requestedRangeFor(page, className, startParts, endParts) {
  return page.evaluate(
    async ({ cls, s, e }) => {
      const requested = [];
      const errors = [];
      const realError = console.error;
      console.error = (...a) => errors.push(a.map(String).join(" "));

      window.CalendarConfig = {
        api: { events: "https://example.invalid/calendar/full-calendar-details" },
      };
      window.fetch = (url) => {
        requested.push(String(url));
        return Promise.resolve({ ok: true, json: async () => [] });
      };

      const inst = Object.create(window[cls].prototype);
      inst.selectedCourseId = null;
      // The admin file guards on this before it builds the URL; the OT file has no such guard.
      inst.calendarRequiresCourses = false;
      inst.courses = [];
      // Everything fetchEvents touches after the response. Stubbed so this test is about the
      // REQUEST and fails for one reason only.
      inst.normalizeEventForTimeGrid = (x) => x;
      inst.revealWeekendsForData = () => {};
      inst.showNotification = () => {};

      // Dates are built from parts, NOT parsed from a string: `new Date("2026-06-29")` is UTC
      // midnight, which is the very confusion this test exists to pin down. FullCalendar hands
      // fetchEvents LOCAL midnight.
      const info = {
        start: new Date(s[0], s[1], s[2]),
        end: new Date(e[0], e[1], e[2]),
      };

      await new Promise((resolve) => {
        let settled = false;
        const done = () => { if (!settled) { settled = true; resolve(); } };
        inst.fetchEvents(info, done, done);
        setTimeout(done, 1000);
      });

      console.error = realError;
      return { requested, errors };
    },
    { cls: className, s: startParts, e: endParts }
  );
}

for (const [label, bladePath, className] of [
  ["admin", BLADE, "AdminFetchEvents"],
  ["OT", BLADE_OT, "OtFetchEvents"],
]) {
  test.describe(`${label} fetchEvents asks for the range it is showing`, () => {
    // IST. The defect is invisible at a negative offset, so a suite that only ever ran in
    // America/New_York would call this code correct.
    test.use({ timezoneId: "Asia/Kolkata" });

    test(`${label} fetchEvents requests the displayed range, not one day early (IST)`, async ({
      page,
    }) => {
      await page.setContent("<!doctype html><title>fetch range</title>");
      await page.evaluate(buildFetchEventsSource(bladePath, className));

      // timeGridWeek on Mon 29 Jun 2026: activeStart = Mon 29 Jun local midnight,
      // activeEnd = Mon 6 Jul local midnight, EXCLUSIVE.
      const result = await requestedRangeFor(page, className, [2026, 5, 29], [2026, 6, 6]);

      expect(result.errors, "fetchEvents threw and its catch swallowed it").toEqual([]);
      expect(result.requested, "fetchEvents did not call fetch exactly once").toHaveLength(1);

      const url = new URL(result.requested[0]);
      expect(
        url.searchParams.get("start"),
        "start is a day early: toISOString() rolls IST local midnight back to the previous day, " +
          "and that day's sessions now open weekend columns inside a week that has none"
      ).toBe("2026-06-29");
      expect(
        url.searchParams.get("end"),
        "end must be the last VISIBLE day: info.end is exclusive, so it needs a -1 day, " +
          "not a raw local format"
      ).toBe("2026-07-05");
    });

    test(`${label} fetchEvents formats a month view's range the same way (IST)`, async ({
      page,
    }) => {
      await page.setContent("<!doctype html><title>fetch range</title>");
      await page.evaluate(buildFetchEventsSource(bladePath, className));

      // dayGridMonth over July 2026: first visible cell Mon 29 Jun, activeEnd Mon 3 Aug.
      const result = await requestedRangeFor(page, className, [2026, 5, 29], [2026, 7, 3]);
      const url = new URL(result.requested[0]);

      expect(url.searchParams.get("start")).toBe("2026-06-29");
      expect(url.searchParams.get("end")).toBe("2026-08-02");
    });
  });

  test.describe(`${label} fetchEvents range at a negative UTC offset`, () => {
    // The same assertions must hold where toISOString() happened to agree, so the fix is not
    // a positive-offset special case.
    test.use({ timezoneId: "America/New_York" });

    test(`${label} fetchEvents requests the displayed range (New York)`, async ({ page }) => {
      await page.setContent("<!doctype html><title>fetch range</title>");
      await page.evaluate(buildFetchEventsSource(bladePath, className));

      const result = await requestedRangeFor(page, className, [2026, 5, 29], [2026, 6, 6]);
      const url = new URL(result.requested[0]);

      expect(url.searchParams.get("start")).toBe("2026-06-29");
      expect(url.searchParams.get("end")).toBe("2026-07-05");
    });
  });
}

/**
 * F-021: `hiddenDays` trims the range FullCalendar asks the feed for, and this PR makes the
 * feed decide `hiddenDays`.
 *
 * DateProfileGenerator runs the range through trimHiddenDays(), which skips hidden days
 * inward from both ends. So in timeGridWeek - where both weekend days sit at the EDGES of a
 * one-week range - the feed is never asked for a Saturday or a Sunday while they are hidden,
 * revealWeekendsForData() never sees a weekend row, the column never opens, and the session
 * is not rendered at all. Once shut, the columns could not reopen: a one-way latch.
 *
 * These two tests drive the REAL shipped FullCalendar bundle, and that is the point. Every
 * other test in this file evaluates methods lifted from the template against hand-built
 * arrays, and NONE of them can observe this defect, because the defect is in the range the
 * bundle computes before the feed is ever called. A guard for F-021 has to let the bundle
 * compute that range.
 */
const FC_BUNDLE = path.join(
  __dirname, "..", "..", "public", "admin_assets", "libs", "fullcalendar", "index.global.min.js"
);

/** Methods each template needs for the feed request itself. */
const RANGE_METHODS = ["fetchEvents", "toYmd", "feedRange"];

/** Plus everything the weekend decision touches, per template. */
const LATCH_METHODS = {
  admin: [
    "fetchEvents", "toYmd", "feedRange", "revealWeekendsForData", "applyHiddenDays",
    "weekendDisplayForEvents", "resolveWeekendDisplay", "isHolidayEvent",
    "eventLocalDate", "eventWeekday", "extractEventDateYmd", "fixCalendarDateTimeString",
  ],
  OT: [
    "fetchEvents", "toYmd", "feedRange", "revealWeekendsForData", "hiddenDaysFor",
    "weekendDisplayForEvents", "resolveWeekendDisplay",
    "eventLocalDate", "eventWeekday", "extractEventDateYmd", "fixCalendarDateTimeString",
  ],
};

function buildClassFrom(bladePath, className, methods) {
  const blade = fs.readFileSync(bladePath, "utf8");
  const body = methods.map((m) => extractMethod(blade, m)).join("\n\n");
  return `window.${className} = class ${className} {\n${body}\n};`;
}

for (const [label, bladePath] of [["admin", BLADE], ["OT", BLADE_OT]]) {
  test.describe(`${label} fetchEvents asks for the whole week, not only the visible days`, () => {
    test.use({ timezoneId: "Asia/Kolkata" });

    test(`${label} timeGridWeek at hiddenDays [0,6] still requests Saturday and Sunday`, async ({ page }) => {
      await page.setContent("<!doctype html><title>f021 range</title><div id=\"cal\"></div>");
      await page.addScriptTag({ path: FC_BUNDLE });
      await page.evaluate(buildClassFrom(bladePath, "F021Range", RANGE_METHODS));

      const requested = await page.evaluate(() => new Promise((resolve) => {
        const seen = [];
        window.CalendarConfig = { api: { events: "https://example.invalid/feed" } };
        window.fetch = (url) => {
          seen.push(String(url));
          return Promise.resolve({ ok: true, json: async () => [] });
        };

        const inst = Object.create(window.F021Range.prototype);
        inst.selectedCourseId = null;
        inst.calendarRequiresCourses = false;
        inst.courses = [];
        inst.normalizeEventForTimeGrid = (x) => x;
        inst.revealWeekendsForData = () => {};
        inst.showNotification = () => {};

        const cal = new FullCalendar.Calendar(document.getElementById("cal"), {
          initialView: "timeGridWeek",
          initialDate: "2026-09-16",   // a Wednesday; its week is Sun 09-13 .. Sat 09-19
          hiddenDays: [0, 6],          // the calendar's shipped initial state
          events: (info, ok, fail) => inst.fetchEvents(info, ok, fail),
        });
        inst.calendar = cal;
        cal.render();
        setTimeout(() => resolve(seen), 400);
      }));

      expect(requested, "fetchEvents did not reach fetch").not.toHaveLength(0);
      const url = new URL(requested[0]);
      expect(
        url.searchParams.get("start"),
        "the request starts on Monday: hiddenDays trimmed Sunday out of the range, so the " +
          "feed can never return a Sunday row and the Sunday column can never open"
      ).toBe("2026-09-13");
      expect(
        url.searchParams.get("end"),
        "the request ends on Friday: hiddenDays trimmed Saturday out of the range, so the " +
          "feed can never return a Saturday row and the Saturday column can never open"
      ).toBe("2026-09-19");
    });

    test(`${label} a Saturday class opens the weekend column in Week view even when it starts hidden`, async ({ page }) => {
      await page.setContent("<!doctype html><title>f021 latch</title><div id=\"cal\"></div>");
      await page.addScriptTag({ path: FC_BUNDLE });
      await page.evaluate(buildClassFrom(bladePath, "F021Latch", LATCH_METHODS[label]));

      const out = await page.evaluate(() => new Promise((resolve) => {
        // One Saturday class, and a server that honours the range it is given.
        const DB = [{
          id: 1, title: "Saturday makeup class", start: "2026-09-26T10:00:00",
          end: "2026-09-26T11:00:00", allDay: false, type: "class",
        }];
        window.CalendarConfig = { api: { events: "https://example.invalid/feed" } };
        const requested = [];
        window.fetch = (url) => {
          const q = new URL(String(url)).searchParams;
          const start = q.get("start"), end = q.get("end");
          requested.push({ start, end });
          const rows = DB.filter((r) => r.start.slice(0, 10) >= start && r.start.slice(0, 10) <= end);
          return Promise.resolve({ ok: true, json: async () => rows });
        };

        const inst = Object.create(window.F021Latch.prototype);
        inst.selectedCourseId = null;
        inst.calendarRequiresCourses = false;
        inst.courses = [];
        inst.hiddenDaysTimer = null;
        inst.normalizeEventForTimeGrid = (x) => x;
        inst.showNotification = () => {};

        const cal = new FullCalendar.Calendar(document.getElementById("cal"), {
          initialView: "timeGridWeek",
          initialDate: "2026-09-16",   // the week BEFORE, which has no weekend session
          hiddenDays: [0, 6],
          events: (info, ok, fail) => inst.fetchEvents(info, ok, fail),
        });
        inst.calendar = cal;
        cal.render();

        // Navigate to the week that HOLDS the Saturday class.
        setTimeout(() => {
          requested.length = 0;   // only the requests for the NAVIGATED week
          cal.next();
          setTimeout(() => resolve({
            hiddenDays: cal.getOption("hiddenDays") || [],
            rendered: cal.getEvents().length,
            requestedAfterNav: requested.slice(),
          }), 600);
        }, 500);
      }));

      expect(
        out.hiddenDays,
        "Saturday is still hidden in the week that contains a Saturday class - the feed was " +
          "never asked for that day, so the rule never saw the row. This is the latch."
      ).not.toContain(6);
      expect(
        out.rendered,
        "the Saturday class was not rendered at all: its column does not exist, so there is " +
          "nowhere to draw it"
      ).toBeGreaterThan(0);

      // Undoing the trim must not turn into over-fetching. A request that reaches back into
      // the PRECEDING week is F-019 again by another route: a session in that week would
      // open a weekend column inside this one.
      for (const r of out.requestedAfterNav) {
        expect(
          r.start,
          `the feed for the week of 2026-09-20 reached back to ${r.start} - a session in the ` +
            "preceding week would open a weekend column inside this one"
        ).toBe("2026-09-20");
        expect(r.end).toBe("2026-09-26");
      }
    });
  });
}
