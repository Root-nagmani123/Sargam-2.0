<?php

namespace App\DataTables;

use App\Models\PeerColumn;
use App\Models\PeerGroupMember;
use App\Support\PeerCourseStatusScope;
use App\Support\PeerGroupSource;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Peer Evaluation -> Evaluation Reports.
 *
 * One row per evaluated OT (a peer_group_members row). The score columns are
 * DYNAMIC - one per visible peer_columns row - so they are built once from
 * criteria() and used by both html() and dataTable(). See the note on criteria()
 * about why that list is not narrowed by the current filter.
 *
 * Every number is an AVERAGE of the scores the OT RECEIVED (peer_scores rows
 * where member_id = this OT). Scores are ints 1..10, so the averages stay on that
 * scale - a sum would grow with the number of evaluators rather than measure
 * performance.
 */
class PeerEvaluationReportDataTable extends DataTable
{
    /** Cache so criteria() isn't queried twice per request. */
    private static $criteriaCache = null;

    /**
     * The criteria columns, in a fixed order.
     *
     * Deliberately NOT scoped to the current course/event filter: DataTables
     * renders its column config server-side once, and every later filter change is
     * an AJAX reload against that same config. A criteria list that changed with
     * the filter would leave the header and the payload disagreeing. An OT whose
     * group doesn't use a given criterion simply shows a dash.
     *
     * @return \Illuminate\Support\Collection<int, PeerColumn>
     */
    public static function criteria()
    {
        if (self::$criteriaCache === null) {
            self::$criteriaCache = PeerColumn::query()->visible()->orderBy('id')->get();
        }

        return self::$criteriaCache;
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $table = (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('ot_name', function ($row) {
                $name = $row->user_name ?: 'Unnamed';
                $url = route('admin.peer.reports.show', ['member' => $row->id]);

                return '<a href="' . e($url) . '" class="per-ot-link">' . e($name) . '</a>';
            })
            ->addColumn('ot_code', fn ($row) => e($row->ot_code ?: '-'))
            ->addColumn('course_name', fn ($row) => e($row->course_name ?: '-'))
            ->addColumn('group_name', fn ($row) => e($row->group_name ?: '-'))
            ->addColumn('evaluators', fn ($row) => (int) ($row->evaluators_count ?? 0))
            ->addColumn('status', function ($row) {
                $submitted = (int) ($row->has_submitted ?? 0) === 1;

                return '<span class="status-pill badge rounded-1 ' . ($submitted ? 'bg-success-subtle' : 'bg-danger-subtle') . '">'
                    . ($submitted ? 'Submitted' : 'Pending')
                    . '</span>';
            })
            ->addColumn('overall', function ($row) {
                return $row->overall_score === null
                    ? '<span class="per-score per-score--empty">-</span>'
                    : '<span class="per-score">' . number_format((float) $row->overall_score, 2) . '</span>';
            });

        foreach (self::criteria() as $criterion) {
            $key = 'crit_' . $criterion->id;
            $table->addColumn($key, function ($row) use ($key) {
                return $row->{$key} === null ? '-' : number_format((float) $row->{$key}, 2);
            });
        }

        return $table
            ->filterColumn('ot_name', function ($query, $keyword) {
                $query->where('peer_group_members.user_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('ot_code', function ($query, $keyword) {
                $query->where('peer_group_members.ot_code', 'like', "%{$keyword}%");
            })
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->where('course_master.course_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('group_name', function ($query, $keyword) {
                $query->where('peer_groups.group_name', 'like', "%{$keyword}%");
            })
            ->orderColumn('ot_name', 'peer_group_members.user_name $1')
            ->orderColumn('ot_code', 'peer_group_members.ot_code $1')
            ->orderColumn('course_name', 'course_master.course_name $1')
            ->orderColumn('group_name', 'peer_groups.group_name $1')
            ->orderColumn('evaluators', 'evaluators_count $1')
            ->orderColumn('overall', 'overall_score $1')
            ->orderColumn('status', 'has_submitted $1')
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (! empty($searchValue)) {
                    $query->where(function ($sub) use ($searchValue) {
                        $sub->where('peer_group_members.user_name', 'like', "%{$searchValue}%")
                            ->orWhere('peer_group_members.ot_code', 'like', "%{$searchValue}%")
                            ->orWhere('course_master.course_name', 'like', "%{$searchValue}%")
                            ->orWhere('peer_groups.group_name', 'like', "%{$searchValue}%");
                    });
                }
            }, true)
            ->rawColumns(['ot_name', 'status', 'overall'])
            ->setRowId('id');
    }

    public function query(PeerGroupMember $model): QueryBuilder
    {
        return self::baseQuery($model, [
            'course' => request('course_filter'),
            'event' => request('event_filter'),
            'group' => request('group_filter'),
            'submission' => request('submission_filter'),
            'status' => request('status_filter'),
        ]);
    }

    /**
     * The one query the grid, the tiles and every export share.
     *
     * @param  array{course?:mixed,event?:mixed,group?:mixed,submission?:mixed,status?:mixed}  $filters
     */
    public static function baseQuery(PeerGroupMember $model, array $filters = []): QueryBuilder
    {
        $query = $model->newQuery()
            ->join('peer_groups', 'peer_groups.id', '=', 'peer_group_members.group_id')
            ->leftJoin('course_master', 'course_master.pk', '=', 'peer_groups.course_id')
            ->leftJoin('peer_events', 'peer_events.id', '=', 'peer_groups.event_id')
            ->select([
                'peer_group_members.id',
                'peer_group_members.user_name',
                'peer_group_members.ot_code',
                'peer_group_members.user_id',
                'peer_group_members.group_id',
                'peer_groups.group_name',
                'peer_groups.course_id',
                'peer_groups.event_id',
                'course_master.course_name as course_name',
                'peer_events.event_name as event_name',
            ]);

        // How many distinct people scored this OT.
        $query->selectSub(
            DB::table('peer_scores')
                ->selectRaw('COUNT(DISTINCT evaluator_id)')
                ->whereColumn('peer_scores.member_id', 'peer_group_members.id')
                ->whereColumn('peer_scores.group_id', 'peer_group_members.group_id'),
            'evaluators_count'
        );

        // Overall = mean of every score this OT received, across all criteria.
        $query->selectSub(
            DB::table('peer_scores')
                ->selectRaw('AVG(score)')
                ->whereColumn('peer_scores.member_id', 'peer_group_members.id')
                ->whereColumn('peer_scores.group_id', 'peer_group_members.group_id'),
            'overall_score'
        );

        // Has this OT submitted their OWN evaluation of peers? peer_scores stores
        // the evaluator as user_credentials.pk, while a member carries the login
        // HANDLE - which is user_credentials.user_name, NOT user_credentials.user_id
        // (a numeric column that matches barely a quarter of them). The two columns
        // also carry different collations, hence the explicit COLLATE. Guarded
        // against a blank handle, which would otherwise match half the table.
        $query->selectSub(
            DB::table('peer_scores')
                ->selectRaw('CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END')
                ->join('user_credentials', 'user_credentials.pk', '=', 'peer_scores.evaluator_id')
                ->whereRaw(PeerGroupSource::EVALUATOR_JOIN)
                ->whereColumn('peer_scores.group_id', 'peer_group_members.group_id')
                ->whereNotNull('peer_group_members.user_id')
                ->where('peer_group_members.user_id', '<>', ''),
            'has_submitted'
        );

        foreach (self::criteria() as $criterion) {
            $query->selectSub(
                DB::table('peer_scores')
                    ->selectRaw('AVG(score)')
                    ->whereColumn('peer_scores.member_id', 'peer_group_members.id')
                    ->whereColumn('peer_scores.group_id', 'peer_group_members.group_id')
                    ->where('peer_scores.column_id', $criterion->id),
                'crit_' . $criterion->id
            );
        }

        // Active / Archived pills, on the COURSE's status - same rule as Course
        // Master and the other two peer grids.
        PeerCourseStatusScope::forRelated($query, $filters['status'] ?? null, 'peer_groups.course_id');

        if (filled($filters['course'] ?? null)) {
            $query->where('peer_groups.course_id', $filters['course']);
        }
        if (filled($filters['event'] ?? null)) {
            $query->where('peer_groups.event_id', $filters['event']);
        }
        // The picker lists Course Group Mapping rows, so filter on the link
        // rather than on peer_groups.id.
        if (filled($filters['group'] ?? null)) {
            $query->where('peer_groups.group_map_pk', $filters['group']);
        }

        // Submitted / Pending, i.e. the has_submitted expression above. Repeated
        // rather than referenced because MySQL can't filter on a select alias.
        $submission = $filters['submission'] ?? null;
        if ($submission === 'submitted' || $submission === 'pending') {
            $hasSubmitted = fn ($sub) => $sub->selectRaw('1')
                ->from('peer_scores')
                ->join('user_credentials', 'user_credentials.pk', '=', 'peer_scores.evaluator_id')
                ->whereRaw(PeerGroupSource::EVALUATOR_JOIN)
                ->whereColumn('peer_scores.group_id', 'peer_group_members.group_id')
                ->whereNotNull('peer_group_members.user_id')
                ->where('peer_group_members.user_id', '<>', '');

            $submission === 'submitted'
                ? $query->whereExists($hasSubmitted)
                : $query->whereNotExists($hasSubmitted);
        }

        $allowed = get_Role_by_course();
        if (! empty($allowed)) {
            $query->whereIn('peer_groups.course_id', $allowed);
        }

        if (empty(request('order'))) {
            $query->orderBy('peer_group_members.id');
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('peerEvaluationReportsTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->selectStyleSingle()
            ->parameters([
                // Responsive off: with a dynamic criteria list this table is wide,
                // and Responsive would collapse the LAST columns (Status, Overall)
                // into a child row. .table-responsive scrolls instead. scrollX off
                // too - it injects a sizing row that renders as an empty band.
                'responsive' => false,
                'scrollX' => false,
                'autoWidth' => false,
                'ordering' => true,
                'sargamServerOrder' => true,
                'searching' => true,
                'lengthChange' => true,
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
                'order' => [],
                'language' => [
                    'search' => '',
                    'searchPlaceholder' => 'Search',
                    'paginate' => ['previous' => "\u{2039}", 'next' => "\u{203A}"],
                    'lengthMenu' => 'Showing _MENU_',
                    'info' => 'of _TOTAL_ items',
                    'infoEmpty' => 'of 0 items',
                    'infoFiltered' => 'of _MAX_ items',
                    'emptyTable' => 'No evaluation reports found',
                    'zeroRecords' => 'No reports match your search',
                ],
            ]);
    }

    public function getColumns(): array
    {
        $columns = [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('ot_name')->title("OT's")->orderable(true)->searchable(true),
            Column::make('ot_code')->title('OT Code')->orderable(true)->searchable(true),
            Column::make('course_name')->title('Course Name')->orderable(true)->searchable(true),
            Column::make('group_name')->title('Group Name')->orderable(true)->searchable(true),
            Column::make('evaluators')->title('Evaluators')->orderable(true)->searchable(false)->addClass('text-center'),
        ];

        foreach (self::criteria() as $criterion) {
            $columns[] = Column::computed('crit_' . $criterion->id)
                ->title($criterion->column_name)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center');
        }

        $columns[] = Column::computed('status')->title('Status')->orderable(true)->searchable(false)->addClass('text-center');
        $columns[] = Column::computed('overall')->title('Overall')->orderable(true)->searchable(false)->addClass('text-center');

        return $columns;
    }

    protected function filename(): string
    {
        return 'PeerEvaluationReports_' . date('YmdHis');
    }
}
