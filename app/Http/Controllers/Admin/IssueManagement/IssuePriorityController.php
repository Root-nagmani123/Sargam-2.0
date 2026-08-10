<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Exports\IssuePriorityExport;
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
use Yajra\DataTables\Facades\DataTables;

class IssuePriorityController extends Controller
{
    private const LISTING_CACHE_EPOCH_KEY = 'admin_issue_priorities_index_list_epoch';

    /**
     * Kept because sibling controllers call it on mutation. The listing itself no
     * longer reads this epoch — the grid is server-side and queries per draw.
     */
    public static function bumpIndexListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssuePriorityController@index');
    }

    /**
     * Normalise the ?per_page= value against the whitelist (falls back to the default).
     */
    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', self::INDEX_PER_PAGE);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::INDEX_PER_PAGE;
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
     * Display a listing of issue priorities.
     *
     * Rows come from data() over ajax (server-side paging), so this action only
     * renders the shell — no rows, no pk snapshot, no cache read.
     */
    public function index()
    {
        return view('admin.issue_management.priorities.index');
    }

    /**
     * DataTables server-side feed for the Manage Priorities grid.
     *
     * Search and ordering run in SQL over the whole table; the browser only ever
     * receives the page it is showing.
     */
    public function data(Request $request)
    {
        // Only the columns the grid renders (G1) — this payload goes to the browser.
        $query = $this->indexFilteredQuery()
            ->select(['pk', 'priority', 'description', 'status'])
            ->reorder();   // drop the default sort; see below

        /* Only order here when DataTables sent none. An ORDER BY left on the query
           is applied FIRST and silently outranks the one Yajra appends, so the
           user's column sort would never take effect. */
        if (! $request->filled('order')) {
            $query->orderBy('priority');
        }

        return DataTables::of($query)
            ->addColumn('id', fn (IssuePriorityMaster $row) => (string) $row->pk)
            ->addColumn('priority_name', fn (IssuePriorityMaster $row) => (string) $row->priority)
            ->addColumn('description', fn (IssuePriorityMaster $row) => (string) ($row->description ?: '-'))
            ->addColumn('status', fn (IssuePriorityMaster $row) => (int) $row->status === 1
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-danger">Inactive</span>')
            ->addColumn('action', fn (IssuePriorityMaster $row) => view(
                'admin.issue_management.priorities._row_actions',
                ['priority' => $row]
            )->render())
            // Searching/ordering happen on the real columns, not the rendered ones.
            ->filterColumn('id', fn ($q, $keyword) => $q->where('pk', 'like', "%{$keyword}%"))
            ->filterColumn('priority_name', fn ($q, $keyword) => $q->where('priority', 'like', "%{$keyword}%"))
            ->filterColumn('description', fn ($q, $keyword) => $q->where('description', 'like', "%{$keyword}%"))
            ->orderColumn('id', 'pk $1')
            ->orderColumn('priority_name', 'priority $1')
            ->orderColumn('description', 'description $1')
            ->orderColumn('status', 'status $1')
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Download / print the full (filtered) priority list.
     *
     * Both formats share the same header + columns as the index grid.
     */
    /**
     * Every priority pk in display order — the full set backing the grid.
     */
    private function indexAllPks(): array
    {
        return $this->indexFilteredQuery()
            ->pluck('pk')
            ->map(fn ($pk) => (int) $pk)
            ->values()
            ->all();
    }

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
