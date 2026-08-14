# Flow — how a listing page actually works

What happens between a click on **Master → State** and the row count in the
bottom-right corner. Written so you can explain it without opening the code.

Companions: [decisions.md](decisions.md) (*why* it is built this way),
[new-design-index-page.md](new-design-index-page.md) (the chrome spec, §-refs
below), [design.md](design.md) (tokens), [master.md](master.md) (the layout).

---

## The one thing to understand first

**Two completely different mechanisms can put a pager and a search box on a
Sargam grid, and they must never both run on the same table.**

| | Variant A — DataTables | Variant B — Laravel |
|---|---|---|
| Who fetches rows | DataTables, over AJAX | The controller |
| Who paginates | DataTables | `->paginate()` |
| Who renders the footer | `datatable-global-ui.js` | your Blade |
| Footer markup | an **empty** div it fills | the `x-programme-dt-footer` component |
| Table needs | `data-dt-footer-for="<id>"` | `data-sargam-dt-ui="false"` |

Everything in this doc is **variant B** unless it says otherwise. Mixing them is
the single most expensive mistake available here — see *Failure modes* at the end.

---

## The request, end to end

```
  GET /master/state?search=raj&per_page=25&page=2
        │
        ▼
  ┌─────────────────────────────────────────────────────────────┐
  │ CONTROLLER            uses PaginatesListings                │
  │                                                             │
  │   $search  = $this->searchTerm($request)      "raj"         │
  │   $perPage = $this->resolvePerPage($request)  25            │
  │             └─ whitelist 10/25/50/100/200/all               │
  │                anything else → the default                  │
  │                'all' → 100000 (a cap, not "no limit")       │
  │                                                             │
  │   $query = State::query();                                  │
  │   $this->applySearch($query, $search, ['state_name']);      │
  │             └─ ORs are wrapped in ONE closure so the        │
  │                search cannot leak past an existing filter   │
  │                                                             │
  │   $states = $query->paginate($perPage)->withQueryString();  │
  │                                        └─ or page 2 loses   │
  │                                           search + per_page │
  └─────────────────────────────────────────────────────────────┘
        │  LengthAwarePaginator
        ▼
  ┌─────────────────────────────────────────────────────────────┐
  │ VIEW                                                        │
  │                                                             │
  │   <x-programme-dt-toolbar>      ← §2  search (right)        │
  │   <div class="programme-dt-panel">                          │
  │     <table class="… programme-dt-table"                     │
  │            data-sargam-dt-ui="false">   ← §5 opt-out        │
  │       <td>{{ $states->firstItem() + $index }}</td>          │
  │     </table>                                                │
  │   </div>                                                    │
  │   <x-programme-dt-footer :paginator="$states" />  ← §4B     │
  └─────────────────────────────────────────────────────────────┘
        │  HTML
        ▼
  ┌─────────────────────────────────────────────────────────────┐
  │ BROWSER                                                     │
  │   datatable-global-ui.js sees data-sargam-dt-ui="false"     │
  │   and leaves this table alone.                              │
  │                                                             │
  │   One delegated handler watches the per-page <select>:      │
  │     set ?per_page=…  AND reset ?page=1  → reload            │
  └─────────────────────────────────────────────────────────────┘
```

Result, bottom of the card:

```
  ‹ 1 [2] 3 ›                              Showing [25 ▾] of 37 items
  └ programme-dt-pagination                └ programme-dt-count
```

---

## The three moving parts you will touch

### 1. `PaginatesListings` (the trait)

`app/Http/Controllers/Concerns/PaginatesListings.php`

```php
$search  = $this->searchTerm($request);            // trimmed, or ''
$perPage = $this->resolvePerPage($request, '25');  // whitelisted int
$this->applySearch($query, $search, ['col_a', 'col_b']);
```

`applySearch()` is only for plain columns on the queried table. Anything
involving a relation is written out longhand in the controller, because the
right shape depends on the data — see *Reaching a relation* below.

### 2. `x-programme-dt-toolbar` (the search row)

```blade
<x-programme-dt-toolbar :action="url()->current()"
    placeholder="Search state" label="Search by state name" per-page="10" />
```

- `:action="url()->current()"` — the path with **no** query string, so the
  Clear (×) link returns to page 1 of the unfiltered list.
- It emits a hidden `per_page` so searching doesn't reset the page size.
- Any other grid state (a category filter, a form id) goes in the default slot
  as hidden inputs, or submitting a search silently drops it.
- Pass a `filters` slot to get the grey **Filters** label and a left-hand group.
  Leave it out and the search sits alone on the right.

### 3. `x-programme-dt-footer` (pager + count)

```blade
<x-programme-dt-footer :paginator="$states" per-page-id="statePerPage" default="25" />
```

**`default` must equal the controller's default**, or the dropdown displays a
page size the server never used.

---

## Where the search filter goes

Not one answer. Pick by how the page gets its rows.

```
  Does one query feed the count, the total AND the page?
    └─ YES → put the filter in that shared base query.
             count / total / page then cannot disagree.
             (stock-purchase-details)

  Is the result cached?
    ├─ cache holds ONE PAGE  → filter inside, and add the term to the CACHE KEY
    │                          (Faculty Expertise, Faculty Type, Venue Master)
    │                          …or every search gets the cached unfiltered rows
    │
    └─ cache holds the WHOLE SET → filter AFTER the cache read
                                   (stock-summary, visitor-pass)
                                   one entry keeps serving every term
                                   ⚠ drop any cached TOTALS — they were
                                     computed over the unfiltered set

  Is the set filtered in PHP, or a GROUP BY aggregate?
    └─ build a LengthAwarePaginator over the collection
       (faculty_average, documents report)
```

---

## Reaching a relation

A column rendered from a relation still has to be searchable, but the shape
depends on size:

| Table size | Shape | Why |
|---|---|---|
| Small (≤ a few thousand) | `orWhereHas('rel', …)` | instant, and it reads clearly |
| Large | resolve ids first, then `whereIn` | `EXISTS` OR-ed with `LIKE`s defeats the index |

Measured on `visitor-pass` (192,974 rows), identical results both ways:
**2.91 s → 0.38 s**. Before reaching for the second shape, check the id list
stays small.

---

## Exports — the part that is easy to forget

**Reports build their downloads from a different query than the screen.** A
filter added to only one produces a download containing rows the user filtered
away.

```
   Screen  ──►  query A  ──►  rows on page
   Excel   ──►  query B  ──┐
   PDF     ──►  query B  ──┼──►  must apply the SAME filter
   Print   ──►  query B  ──┘
```

Two ways this is handled here:

- **The link passes everything.** `route('…', request()->query())` carries any
  new param automatically — the mess reports work this way.
- **The link has an allow-list.** `http_build_query(['course_type' => …, …])`
  only carries what is named, so a new param must be added in every builder:
  the server-rendered link, the JS that rewrites it, and the print URL. Faculty
  Average needed all three.

**Check which kind you have before adding a filter.** And where several exports
share a filter, put it in one helper both call —
`applyFacultyAverageTopicFilter()` is shared by the screen and all three exports
so they physically cannot drift.

---

## Failure modes, and what each looks like

| Symptom on screen | Cause |
|---|---|
| A 52 px empty white bar under the table | Laravel paginates but the table lacks `data-sargam-dt-ui="false"`; the enhancer injected an empty footer |
| Search, pager and count all invisible | Table opts out (`dt-legacy-layout` / `data-sargam-dt-ui="false"`) **and** supplies no `dom`, so the global default hides them in a `d-none` row |
| Pager looks fine, later pages are empty or unreachable | A client-side DataTable over a server paginator — or, as on the send-notice list, a server paginator whose pager was never rendered |
| "of 0 items of 187 items" | Opted-out table using the global language: DataTables **appends** `infoFiltered` to `info` |
| The count appears twice | `pagination::bootstrap-5` prints its own "Showing…" above the links |
| Search returns rows an active filter excludes | An `orWhere` outside the group — AND binds tighter than OR |
| Search box does nothing | Cached page snapshot whose key omits the term |
| Grand totals contradict the visible rows | Cached totals reused with a filter active |
| Page 2 forgets the search | `->withQueryString()` missing |
| S.No restarts at 1 on page 2 | Using `$loop->index` instead of `$paginator->firstItem() + $index` |
| A stray "Filters" label with nothing beside it | `filled($slot)` on a `ComponentSlot` — always true; cast to string first |

---

## When *not* to add chrome

Measure before assuming a grid needs a pager.

- **Trees.** Slicing a flattened parent→child tree by row orphans children.
  `/registration/forms` — 32 parents, 71 rows. Count only.
- **Bulk-entry grids.** Paging breaks multi-row save. The estate meter-reading
  grids say so in a comment; leave them.
- **Bulk-select grids.** If a page has "Select all", paging makes it cover only
  the visible page. The send-notice list is paginated at **100** for exactly
  this reason — average group is 31, so nearly all fit on one page.
- **Tiny grids.** 5–16 rows: the count is the honest gap, a pager is ceremony.

And if you do filter a bulk-select grid, **filter on the server** — a
client-side filter only hides rows, and hidden checkboxes still submit.

---

## Verifying a change

Render the page and assert on numbers, not on the presence of markup.

```php
// dispatch through the real kernel
$r = Illuminate\Http\Request::create($uri, 'GET', $queryArrayNotAString);
$r->setLaravelSession(app('session.store'));
$html = (string) $kernel->handle($r)->getContent();
```

Worth asserting: the count equals the DB row count · a real term narrows it · an
impossible term gives 0 · the term survives paging · a bogus `per_page` falls
back · **the export shrinks by the same filter**.

Four traps that will otherwise hand you a confident wrong answer:

- Yajra binds `datatables.request` as a **singleton** — call
  `app()->forgetInstance('datatables.request')` between requests or every
  DataTables call after the first reuses the first one's search.
- Pass query params as an **array**; a hand-built string with unencoded `[]`
  silently arrives empty.
- Binary downloads have an empty `getContent()` — `ob_start()` + `sendContent()`.
- Sessions expire. Playwright follows the redirect and reports **HTTP 200 on the
  login page**; re-mint the cookie before a visual pass.

And before blaming your change: `git stash push -- app resources`, re-run,
`git stash pop`. Several "failures" in this work were pre-existing, and two were
the test's own bad input.
