<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Exports\IssuePriorityExport;
use App\Http\Controllers\Concerns\NormalisesDataTablesRequest;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\IssuePriorityMaster;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use App\Support\ExportCsvHeader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IssuePriorityController extends Controller
{
    use NormalisesDataTablesRequest;

    private const LISTING_CACHE_EPOCH_KEY = 'admin_issue_priorities_index_list_epoch';

    private const INDEX_PER_PAGE = 10;

    /**
     * Page sizes the grid feed accepts — this list MUST mirror the lengthMenu
     * datatable-global-ui.js installs, or picking one it offers falls back to
     * INDEX_PER_PAGE and the footer says 25 while 10 rows are shown.
     */
    private const DATA_PER_PAGE_OPTIONS = [10, 25, 50, 100, 200];

    /** Sortable grid headers → the column ordered by. */
    private const SORTABLE_COLUMNS = [
        'priority' => 'priority',
        'description' => 'description',
        'status' => 'status',
    ];

    public static function bumpIndexListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssuePriorityController@index');
    }

    private function resolveSearch(Request $request): string
    {
        return trim((string) $request->query('q', ''));
    }

    /**
     * Whitelisted sort keys → the expression the query orders by.
     *
     * @return array{key: string, dir: string}
     */
    private function resolveSort(Request $request): array
    {
        $key = (string) $request->query('sort', 'priority');
        if (! array_key_exists($key, self::SORTABLE_COLUMNS)) {
            $key = 'priority';
        }

        $dir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        return ['key' => $key, 'dir' => $dir];
    }

    private function indexFilteredQuery(string $search = '', string $sortKey = 'priority', string $sortDir = 'asc'): Builder
    {
        $sortColumn = self::SORTABLE_COLUMNS[$sortKey] ?? self::SORTABLE_COLUMNS['priority'];
        $sortDir = $sortDir === 'desc' ? 'desc' : 'asc';

        return IssuePriorityMaster::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
                $query->where(function (Builder $inner) use ($like, $search) {
                    $inner->where('priority', 'like', $like)
                        ->orWhere('description', 'like', $like);

                    // The grid searches in the browser, so it also matches the
                    // rendered Status pill. The export runs this query instead —
                    // match the pill here too, or a searched export comes back
                    // empty while the screen shows rows.
                    $statuses = DataTableSearchHelper::statusPillMatches($search);
                    if ($statuses !== []) {
                        $inner->orWhereIn('status', $statuses);
                    }
                });
            })
            ->orderBy($sortColumn, $sortDir)
            // pk tiebreaker — priority is not unique, and without it rows can
            // duplicate/vanish between pages of the snapshot pagination.
            ->orderBy('pk');
    }

    /**
     * @return array{total: int, ids: array<int, int>}
     */
    private function indexPageSnapshot(int $page, int $perPage, string $search, string $sortKey, string $sortDir): array
    {
        $base = $this->indexFilteredQuery($search, $sortKey, $sortDir);
        $total = (int) (clone $base)->toBase()->getCountForPagination();
        $ids = [];
        if ($total > 0) {
            $ids = (clone $base)->forPage($page, $perPage)->pluck('pk')->values()->all();
            $ids = array_map('intval', $ids);
        }

        return ['total' => $total, 'ids' => $ids];
    }

    /**
     * @param  array<int, int>  $ids
     * @return \Illuminate\Support\Collection<int, IssuePriorityMaster>
     */
    private function hydratePrioritiesByOrderedPks(array $ids): \Illuminate\Support\Collection
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return collect();
        }
        // issue_logs_count drives the Delete guard — destroy() refuses a priority
        // that is referenced by any issue log.
        $byPk = IssuePriorityMaster::query()
            ->withCount('issueLogs')
            ->whereIn('pk', $ids)
            ->get()
            ->keyBy(fn (IssuePriorityMaster $m) => (int) $m->pk);

        return collect($ids)
            ->map(fn (int $id) => $byPk->get($id))
            ->filter()
            ->values();
    }

    /**
     * Display a listing of issue priorities.
     *
     * Rows come from data() over ajax, so this action only renders the shell.
     */
    public function index()
    {
        return view('admin.issue_management.priorities.index');
    }

    /**
     * DataTables server-side feed for the Manage Priorities grid.
     *
     * Searching, sorting and paging are all SQL: only the page on screen ever
     * crosses the wire. The request is normalised to the same ?q / ?sort / ?dir
     * the export reads, so a download always matches what is displayed.
     */
    public function data(Request $request)
    {
        $paging = $this->normaliseDataTablesRequest($request, self::INDEX_PER_PAGE, self::DATA_PER_PAGE_OPTIONS);
        $search = $this->resolveSearch($request);
        $sort = $this->resolveSort($request);

        $snapshot = $this->indexCachedSnapshot($paging['page'], $paging['perPage'], $search, $sort);

        // Only the pk order is cached; the rows themselves are hydrated fresh so
        // a status toggle shows up on the very next draw.
        $rows = $this->hydratePrioritiesByOrderedPks($snapshot['ids'])
            ->values()
            ->map(fn (IssuePriorityMaster $priority, int $i) => [
                'sno' => $paging['start'] + $i + 1,
                'priority' => e((string) $priority->priority),
                'description' => e((string) ($priority->description ?: '—')),
                'status' => view('admin.issue_management.priorities._row_status', compact('priority'))->render(),
                'action' => view('admin.issue_management.priorities._row_actions', compact('priority'))->render(),
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $snapshot['total'],
            'recordsFiltered' => $snapshot['total'],
            'data' => $rows,
        ]);
    }

    /**
     * Cached {total, ids} snapshot for one page of the grid query.
     *
     * @param  array{key: string, dir: string}  $sort
     * @return array{total: int, ids: array<int, int>}
     */
    private function indexCachedSnapshot(int $page, int $perPage, string $search, array $sort): array
    {
        $epoch = DataTableRedisCache::readListEpoch(self::LISTING_CACHE_EPOCH_KEY);
        // Every input that changes the slice is in the key — a page cached under
        // one search term must never be served for another.
        $cacheKey = 'admin_issue_priorities_index:v3:' . md5(json_encode([
            'epoch' => $epoch,
            'search' => $search,
            'sort' => $sort,
            'page' => $page,
            'per_page' => $perPage,
        ]));

        $snapshot = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'ISSUE_PRIORITY_INDEX_CACHE_ENABLED',
                'seconds' => 'ISSUE_PRIORITY_INDEX_CACHE_SECONDS',
            ],
            'IssuePriorityController@data',
            fn () => $this->indexPageSnapshot($page, $perPage, $search, $sort['key'], $sort['dir'])
        );

        if (! is_array($snapshot) || ! isset($snapshot['total']) || ! isset($snapshot['ids']) || ! is_array($snapshot['ids'])) {
            $snapshot = $this->indexPageSnapshot($page, $perPage, $search, $sort['key'], $sort['dir']);
        }

        return [
            'total' => (int) $snapshot['total'],
            'ids' => array_map('intval', $snapshot['ids']),
        ];
    }

    /**
     * Download / print the full (filtered) priority list.
     *
     * Both formats share the same header + columns as the index grid.
     */

    /**
     * Canonical export columns, in order. Keys must match IP_EXPORT_COLUMN_KEYS
     * in priorities/index.blade.php.
     */
    private function exportColumnDefs(): array
    {
        return [
            'sno' => [
                'heading' => 'S. No.',
                'class' => 'col-sno',
                'value' => fn ($row, int $index) => $index + 1,
            ],
            'priority' => [
                'heading' => 'Priority',
                'class' => 'col-priority',
                'value' => fn ($row) => $row->priority,
            ],
            'description' => [
                'heading' => 'Description',
                'class' => 'col-desc',
                'value' => fn ($row) => $row->description ?: '-',
            ],
            'status' => [
                'heading' => 'Status',
                'class' => 'col-status',
                'value' => fn ($row) => ((int) $row->status === 1) ? 'Active' : 'Inactive',
            ],
        ];
    }

    /**
     * Intersect ?cols= against the canonical list so a hand-edited value cannot
     * reorder the report or inject a column. Empty => every column.
     */
    private function resolveExportColumns(Request $request): array
    {
        $defs = $this->exportColumnDefs();
        $wanted = array_filter(array_map('trim', explode(',', (string) $request->query('cols', ''))));

        if ($wanted === []) {
            return $defs;
        }

        $keys = array_values(array_intersect(array_keys($defs), $wanted));

        return $keys === [] ? $defs : array_intersect_key($defs, array_flip($keys));
    }

    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $search = $this->resolveSearch($request);
        $sort = $this->resolveSort($request);
        $rows = $this->indexFilteredQuery($search, $sort['key'], $sort['dir'])->get();

        $columns = $this->resolveExportColumns($request);
        $header = array_values(array_map(fn ($col) => $col['heading'], $columns));
        $exportDate = now()->format('d-m-Y h:i A');
        $stamp = now()->format('YmdHis');

        if ($format === 'print') {
            return view('admin.issue_management.priorities.export_print', [
                'columns' => $columns,
                'header' => $header,
                'rows' => $rows,
                'search' => $search,
                'exportDate' => $exportDate,
            ]);
        }

        if ($format === 'excel') {
            return Excel::download(
                new IssuePriorityExport($rows, $columns, $exportDate, $search),
                'ManagePriorities_' . $stamp . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            return Pdf::loadView('admin.issue_management.priorities.export_pdf', [
                'columns' => $columns,
                'rows' => $rows,
                'search' => $search,
                'exportDate' => $exportDate,
            ])
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                ])
                ->download('ManagePriorities_' . $stamp . '.pdf');
        }

        $filename = 'ManagePriorities_' . $stamp . '.csv';

        // Same band the .xlsx and the print/PDF headers carry, so the CSV names
        // the applied filters too.
        $csvBand = ExportCsvHeader::rows(
            'Manage Priorities',
            $search !== '' ? 'Search: ' . $search : null,
            $exportDate,
            $rows->count()
        );

        return response()->streamDownload(function () use ($columns, $header, $rows, $csvBand) {
            $handle = fopen('php://output', 'w');
            // BOM so Excel opens the UTF-8 file with the right encoding.
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($csvBand as $bandRow) {
                fputcsv($handle, $bandRow);
            }
            fputcsv($handle, $header);

            foreach ($rows as $index => $row) {
                fputcsv($handle, array_values(array_map(
                    fn ($col) => $col['value']($row, $index),
                    $columns
                )));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Store a newly created priority in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'priority' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        IssuePriorityMaster::create([
            'priority' => $request->priority,
            'description' => $request->description,
            'created_by' => Auth::id(),
            'created_date' => now(),
            'status' => 1,
        ]);

        static::bumpIndexListCacheEpoch();

        return redirect()->route('admin.issue-priorities.index')
            ->with('success', 'Priority added successfully.');
    }

    /**
     * Update the specified priority in storage.
     */
    public function update(Request $request, $id)
    {
        $priority = IssuePriorityMaster::findOrFail($id);

        $request->validate([
            'priority' => 'required|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:0,1',
        ]);

        $priority->update([
            'priority' => $request->priority,
            'description' => $request->description,
            'status' => $request->status,
            'modified_by' => Auth::id(),
            'modified_date' => now(),
        ]);

        static::bumpIndexListCacheEpoch();

        return redirect()->route('admin.issue-priorities.index')
            ->with('success', 'Priority updated successfully.');
    }

    /**
     * Remove the specified priority from storage.
     */
    public function destroy($id)
    {
        $priority = IssuePriorityMaster::findOrFail($id);

        if ($priority->issueLogs()->count() > 0) {
            return back()->with('error', 'Cannot delete priority with associated issues.');
        }

        $priority->delete();

        static::bumpIndexListCacheEpoch();

        return redirect()->route('admin.issue-priorities.index')
            ->with('success', 'Priority deleted successfully.');
    }
}
