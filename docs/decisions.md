# Decision log — listing chrome (pagination · search · count)

Every non-obvious decision taken while giving 48 listing pages their missing
pagination, search box and record count, and **why** — including the options
rejected and the measurements that settled it.

Read [flow.md](flow.md) first if you want the mental model of how a listing
request actually works. This file is the *why*; that one is the *what*.

Companion docs: [design.md](design.md) (tokens),
[new-design-index-page.md](new-design-index-page.md) (the chrome spec this all
implements — section references like §4B below point there).

---

## The rule behind most of these decisions

> **The screen and its exports must agree, always.**

Almost every report in this app builds its Excel/PDF/Print from a *different*
query than the one behind the screen. Add a filter to only one of them and a
user downloads rows they just filtered away — a silent, invisible wrong answer.
That failure mode is worse than the missing control was, so where a search
couldn't be wired through every export cheaply, the decision was to wire it
through all of them or not add it at all.

---

## 1. Shared trait instead of copy-paste

**Decision.** `app/Http/Controllers/Concerns/PaginatesListings.php` — one trait
providing `resolvePerPage()`, `searchTerm()` and `applySearch()`, used by **23** controllers.

**Why.** The first two controllers got the whitelist logic inline, matching what
`MemoDisciplineController` already did. At the third it was still fine; at the
twelfth it would have been twelve places to fix a bug in. The trait went in
*before* the bulk pass, not after.

**Why a trait and not a base controller or a service.** These controllers
already extend the framework `Controller` and several extend module-specific
ones; a base class would have forced a hierarchy change across unrelated
modules. A service object would need injecting into every constructor for three
tiny pure functions. A trait is the smallest change that removes the
duplication.

**The one thing `applySearch()` must do.** Group the OR'd `LIKE`s inside a
nested closure:

```php
$query->where(function ($q) use ($columns, $like) {
    foreach ($columns as $column) { $q->orWhere($column, 'like', $like); }
});
```

Without the closure, `->where('active', 1)->orWhere('name', 'like', …)` compiles
to `active = 1 OR name LIKE …` and the search returns inactive rows. This is not
theoretical — see decision 12.

---

## 2. Blade components instead of a copied footer block

**Decision.** Three components —
`x-programme-dt-toolbar`, `x-programme-dt-search`, `x-programme-dt-footer`.

**Why.** §4B's footer is ~20 lines of markup that has to be identical on every
grid or the pages visibly drift. The doc itself says: *"the moment a sibling
needs the same look, extract it."* **32 views** now share the footer and **26** the toolbar, from one definition each.

**Why not `x-datatable-chrome`** (the existing component)? It packages the
**variant A** footer — an empty div that `datatable-global-ui.js` fills for a
DataTables grid. Every page in this work is **variant B**: Laravel paginates and
the footer is hand-written. Different mechanism, so a different component.

**Gotcha this cost us.** The toolbar first guarded its filters slot with
`filled($filters)`. A `ComponentSlot` is an **object**, and `blank()`/`filled()`
only trim *strings*, so `filled()` is true even for an empty slot — every
filter-less grid rendered a stray "Filters" label with nothing beside it. Cast
first: `trim((string) $filters) !== ''`.

---

## 3. `vendor.pagination.custom`, never `pagination::bootstrap-5`

**Decision.** All variant-B footers render `links('vendor.pagination.custom')`.

**Why.** The Bootstrap view prints its own *"Showing 1 to 10 of 1105 results"*
above the links. Beside the design-system count that reads **"Showing 1 to 10 of
1105 results … Showing 10 of 1,105 items"** — the count twice, in two different
formats. Caught on `purchase-sale-quantity` only by looking at the rendered
footer. `vendor.pagination.custom` emits links and nothing else, and uses the
same `‹ ›` glyphs as the JS pager so both footer variants match.

---

## 4. Where the search filter goes (three different answers)

Not one rule — it depends on how the page gets its rows.

| Shape | Where the filter goes | Example |
|---|---|---|
| One query behind count + total + page | The **shared base query** | `stock-purchase-details` |
| Cached *page snapshot* | Inside, **and folded into the cache key** | Faculty Expertise, Faculty Type, Venue Master |
| Cached *full dataset* | **After** the cache read | `stock-summary`, `visitor-pass` |

**Why the split.** A key built from `page` alone hands every search the cached
**unfiltered** rows (§4B warns about exactly this). But when the cache holds the
*whole* set rather than one page, filtering after the read is better: one cache
entry keeps serving every search term instead of one entry per term.

**The trap inside the third case.** `stock-summary` also cached its **grand
totals**, computed over the whole report. Filtering without dropping
`$cachedTotals` prints totals that contradict the rows on screen. The fix is one
line — `$cachedTotals = null;` when a term is active — and it is the sort of
thing that looks right in review and is wrong on screen.

---

## 5. Pre-resolved id lists instead of `whereHas()` — measured

**Decision.** On `/security/visitor-pass`, resolve the two relation matches to id
lists first, then `whereIn`.

**Why — numbers, not taste.** The table holds **192,974** rows. Both shapes
return the identical 4,876 results:

| Shape | Query | Page |
|---|---|---|
| Two `whereHas()` OR-ed with the `LIKE`s | 2.91 s | 6.84 s |
| Two lookups + `whereIn` | **0.38 s** | **1.38 s** |

OR-ing `EXISTS` subqueries alongside `LIKE`s defeats the index and runs the
correlated subqueries per row.

**Why this isn't the default everywhere.** It trades a bounded extra query for
an `IN` list, which is only sensible when that list stays small — checked before
adopting: worst case here is 1,742 employee ids. On the small master grids
(≤1,664 rows) plain `whereHas` is instant and clearer, so they keep it.

---

## 6. `LengthAwarePaginator` over a collection, not `->paginate()`

**Decision.** Faculty Average and the Documents report build a paginator by hand
from a Collection.

**Why.** Two different reasons that both rule out `->paginate()`:

- **Faculty Average** — the query is a `GROUP BY` aggregate. Laravel's paginator
  wraps the query in a `COUNT` that grouped queries break, and the percentages
  are computed in PHP *after* the fetch anyway.
- **Documents report** — its `doc_status` filter runs in PHP once uploads are
  loaded, so it cannot be pushed into SQL. Paginating in SQL would page the
  *unfiltered* set.

Slicing the processed collection keeps the SQL byte-identical to before and puts
a real pager on top. It does not reduce what the DB returns — that was already
the behaviour; it bounds what the *browser* renders.

---

## 7. `data-sargam-dt-ui="false"` whenever Laravel paginates

**Decision.** Every hand-written footer sits on a table carrying this attribute.

**Why.** `datatable-global-ui.js` sets `dom` app-wide to a `d-none` row and then
relocates the controls. On a Laravel-paginated page there is nothing to relocate,
so `enhance()` injects an **empty** `.programme-dt-footer` — which CSS renders as
a 52 px white bar with a top border and nothing in it. Visible on
`/admin/memo-notice` before the opt-out went on (measured: 1 footer, 1 of them
empty; after: 1 footer, 0 empty).

**The other half of the rule.** Opting out means the enhancer no longer supplies
*anything* — including the search box styling and the `info` wording. An opted-out
page must bring its own. `student_courselist` needed both:

- scoped CSS for `.dataTables_filter input` (the `.programme-dt-search` rules are
  all keyed on `.dataTables_filter`, which only exists on enhanced pages);
- its own `info` / `infoFiltered`, because DataTables **appends** `infoFiltered`
  to `info` and the global defaults set both to *"of _N_ items"*, rendering
  **"of 0 items of 187 items"**.

---

## 8. When *not* to paginate

Three pages deliberately got a count and no pager. Each for a concrete reason,
each measured first.

**`/registration/forms` — it's a tree.** Parent rows with expandable children.
Slicing a flattened tree by row strips children away from the parent that owns
them. Measured: 32 top-level forms, 71 rows total. Nothing to page. The count
also updates as the client-side filter hides rows, because a static count beside
a filtered table is worse than no count.

**`/admin/travel/slots` — 5 rows, and each is an inline edit form**, not a table
row. A pager adds friction and pages nothing.

**Estate bulk-entry grids** (`update-meter-reading`, `generate-estate-bill`) —
left completely alone. The code says why: *"For this grid we avoid DataTables to
keep typing smooth and prevent focus jumps."* Paging a bulk-entry grid breaks
multi-row save.

**And one where pagination existed but had to be *loosened*.** The send-notice
list was `paginate(30)` with **no pager rendered** — on the largest group 157 of
187 students were unreachable and "Select all" silently covered 30. Fixed by
rendering the footer *and* raising the default to 100, because the average group
is 31: nearly every group now fits on one page, so select-all still means what
the user expects.

---

## 9. Server-side search, not client-side, where selection matters

**Decision.** The send-notice list searches on the server.

**Why.** A client-side filter hides rows with CSS — but hidden checkboxes still
submit. On a page whose job is picking students to notify, that means "Select
all" quietly includes people you filtered out. Server-side search means only
matching rows exist in the DOM at all, so select-all covers exactly what is on
screen.

---

## 10. Guard a broken column; never guess a replacement

**Decision.** Course List's Status Filter is wrapped in
`Schema::hasColumn('fc_registration_master', 'confirm_status')` and the control
is hidden while that is false.

**Why.** `confirm_status` lives on `form_submissions`, not on the table being
queried, so `?statusval=1` was *"Unknown column"* — a hard 500 since the filter
shipped. The obvious "fix" is to repoint it at `fc_registration_master.status`.
**That would be wrong**: `status` holds `'1'` or `NULL`, which does not carry the
confirm / not-confirm meaning the dropdown offers. Silently showing the wrong
rows is worse than the crash.

The guard makes the page safe, hides a control that cannot work, and lets the
filter reappear by itself the day the column exists. **Choosing between adding
the column and repointing the code is a schema decision and is still open.**

**Caste Category turned out NOT to need one — same symptom, different cause.**
The whole CRUD was written against `category_name`; the real column is
`Seat_name`. Unlike `confirm_status` there is no ambiguity here: `Seat_name`
plainly holds the caste category name (EWS / OBC / ST / SC / GENERAL), and
adding a second column for the same data would be wrong. Resolved with an
accessor + mutator on the model mapping `category_name` <-> `Seat_name`, which
keeps the form field, the validation key and the AJAX payload keys unchanged.

`Rule::unique()` queries the table directly and does **not** pass through an
accessor, so the validation rules name `Seat_name` explicitly — that one is easy
to miss and would have let duplicates through.

---

## 11. Not adding a control that duplicates one already there

**Decision.** `purchase-sale-quantity` got a count but no search box.

**Why.** Its Item filter is already a searchable Choices multi-select, beside
Category and Store. A free-text box would be a second way to do the same thing.
The audit flagged "search missing"; looking at the page, the honest gap was the
count — the footer rendered page links and never a total.

Recording this because the audit row and the shipped change deliberately differ.

---

## 12. Grouping an `orWhere` — the bug this whole pattern prevents

**Decision.** Wrapped the two alternatives on
`/security/employee-idcard-approval/all` in one closure.

**Why.** It read:

```php
$permQuery->whereHas('employee', …)->orWhere('id_card_no', 'like', "%{$search}%");
// … later …
$permQuery->where('id_status', PENDING)->whereNotIn('emp_id_apply', $subA1);
```

compiling to `… AND EXISTS(name) OR id_card_no LIKE ? AND id_status = ?`. AND
binds tighter than OR, so **any row matching on card number escaped every other
constraint** — searching a name while filtering by status returned rows in all
statuses.

**Proven, not assumed.** Both shapes built side by side against live data: with
the status filter allowing **1** row, the old shape returned **4**. The
contractual branch ten lines below had always grouped its version correctly.

---

## 13. Page sizes changed to match the dropdown

**Decision.** `/admin/memo-notice` 20 → 25; joining-documents report 20 → 25;
send-notice list 30 → 100.

**Why.** The footer's rows-per-page dropdown offers 10/25/50/100/200. A default
outside that list leaves the dropdown showing a size the server never used, and
on the joining-documents report `?per_page=` was ignored entirely because the
controller hard-coded `paginate(20)`. Either the list grows or the default joins
it; joining it was the smaller change.

**`'all'` is a cap, not a promise.** `resolvePerPage()` maps `'all'` to 100,000
rather than removing the limit. `paginate()` runs its own `COUNT` regardless, so
it never returns more rows than match, and a runaway page can't take the server
down.

---

## 14. Byte-level patching to protect line endings

**Decision.** Bulk edits were applied by Python scripts writing **bytes**, with
every anchor asserted to match exactly once.

**Why.** These files are CRLF. An editor that normalises endings turns a 2-line
change into 150 lines of diff and buries the real change. It bit us anyway: one
script emitted `\n` into 15 CRLF files, leaving 5 mixed-EOL lines each.
`git diff --numstat` looked clean — the check that caught it compares bytes:

```bash
diff <(git diff --numstat) <(git diff --ignore-cr-at-eol --numstat)
```

plus a per-file scan for `crlf > 0 && loneLF > 0`. Both run after every bulk pass.

**Assert, don't guess.** Each script checks `src.count(anchor) == 1` and aborts
*before writing* otherwise. That caught a payload anchor matching 3 places
instead of 1 — the file was never touched.

---

## 15. How this was verified, and why not PHPUnit

**Decision.** Behavioural suites that dispatch real requests through the HTTP
kernel in-process and assert on the rendered output.

**Why.** The bugs in this work are integration bugs — a cache key, a query
precedence, a Blade component, an opted-out enhancer. A unit test around a
controller method would have missed every one. Each suite asserts things a
screenshot can't: *the count equals the DB row count*, *a term narrows the total*,
*an impossible term returns 0*, *the export shrinks by the same filter*.

**PHP lint is not enough.** Two controllers used `$request` without a
`Request $request` parameter and one assigned `$subTypes` where the code read
`$query`. **`php -l` passed on all three.** They were runtime failures the
behavioural suite caught immediately.

### Harness traps worth knowing (each produced a convincing false result)

| Trap | Symptom | Fix |
|---|---|---|
| Yajra binds `datatables.request` as a **singleton** | Every DataTables call after the first reuses the first request's search → grid looks unfiltered | `app()->forgetInstance('datatables.request')` per request |
| `Request::create()` with unencoded `[]` in a query string | Params silently dropped → same false "search broken" | Pass params as an **array** |
| Binary responses | `getContent()` is empty for a download → "export unchanged: 0 < 0 bytes" | `ob_start()` + `sendContent()` |
| PDF/Excel bytes contain `\r` | Overwrote 12 of 16 report lines in the terminal | Buffer the response |
| Expired session cookie | Playwright follows the redirect, reports HTTP 200 on the **login page** → "0 search boxes" | Re-mint before each visual pass |
| Counting every `<tr>` | Header rows inflate the count → "print=2 screen=1" | Count `<tbody>` rows only |

**Always check the clean tree before blaming yourself — or the code.** Four
"failures" turned out to be pre-existing (`view:cache`, the setup-page JS parse
error, the stock-summary PDF OOM, an unguarded `->timetable`), and two turned out
to be my own test using invalid ids. `git stash push -- app resources`, re-run,
`git stash pop`.

---

## Resolved after the first pass

- **Caste Category write path** — see decision 10. Accessor + mutator; full CRUD
  verified end to end (create, duplicate rejected, edit pre-filled, update
  persisted, test row cleaned up).
- **Stock Summary PDF OOM** — decision 16.

---

## 16. A measured ceiling for the Stock Summary PDF, not a bigger number

**Decision.** Raise that one action to `1024M` **and** refuse past a named row
cap with a message.

**Why the number.** DomPDF builds a frame per element and this template is 13
columns wide, so peak memory tracks row count almost linearly. Measured:

| Rows | Peak | Rows | Peak |
|---|---|---|---|
| 100 | 80 MB | 600 | 420 MB |
| 300 | 186 MB | 855 | 686 MB |

~0.8 MB per row over a ~10 MB baseline. The old `512M` covered about 640 rows,
so the live 1,105-item catalogue died with a **fatal error and a blank page** —
no message, nothing in the UI.

**Why not just raise the limit.** A bigger number alone moves the cliff without
removing it, and the failure past it is still a white page. `1024M` covers the
real catalogue with headroom; `STOCK_SUMMARY_PDF_MAX_ROWS = 1200` (~960 MB at
the measured rate) turns anything beyond into a sentence telling the user to
narrow the filter or use Excel — which has no such cost (79 KB for the same
data) and stays uncapped.

**Why not chunk the PDF.** DomPDF holds every frame for the whole document
regardless of how the HTML is assembled, so chunking the *markup* saves nothing.
Splitting into several files changes what the button does.

**Verified the way production runs it**: base limit 256M, controller raises to
1024M, real 742 KB PDF at 704 MB peak — the exact case that used to die.

---

## Still open — these need a decision from a human

1. **Course List Status Filter.** See decision 10. The dropdown offers
   confirm (1) / not-confirm (2). `confirm_status` exists on **no live table** —
   the `form_submissions` migration that defines it has never run. Nothing on
   `fc_registration_master` carries that meaning either: `status` is `'1'`/NULL
   (485 rows), `final_submit` is `'2'`/`'0'` (466/19), `refund_status` is
   `'0'`/NULL. **Add the column, or say which existing one means "confirmed"?**
   Guarded and hidden until then, so the page cannot 500.
2. **`/fc/status/data`** — the only grid in its tier without search. It is a
   tabbed AJAX fragment fed by `FcRegistrationStatusService::participantsForTab()`,
   so it needs a service-signature change plus threading the term through the
   tab/paging JS. Different shape from the other 17; deferred deliberately.
3. **Sortable headers on `/admin/memo-notice`** are page-local — the DataTable
   reorders the current page, not the result set. Real sorting needs server-side
   sort links (§4B).
