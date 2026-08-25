<?php

namespace App\DataTables;

use App\Models\PeerReflectionField;
use App\Support\PeerCourseStatusScope;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Server-side feed for Peer Evaluation -> Manage Reflection Fields.
 *
 * Course and Event names are joined columns so they sort and search in SQL
 * rather than paging the table into PHP. `course_id` is a `course_master.pk`
 * (see 2026_08_24_000002_point_peer_evaluation_at_course_master).
 *
 * course_id / event_id / group_id are all nullable - a field with none set is a
 * GLOBAL field that appears on every evaluation form, so the joins are LEFT and
 * the columns fall back to a dash.
 */
class PeerReflectionFieldDataTable extends DataTable
{
    private const DISPLAY_DATE = 'd/m/Y';

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('course_name', fn ($row) => e($row->course_name ?: '-'))
            ->addColumn('event_name', fn ($row) => e($row->event_name ?: '-'))
            ->addColumn('field_label', fn ($row) => e($row->field_label ?: '-'))
            ->addColumn('created_date', fn ($row) => optional($row->created_at)->format(self::DISPLAY_DATE) ?: '-')
            ->addColumn('status', function ($row) {
                // Display only. The control lives in the Action column (doc §3b).
                $active = (bool) $row->is_active;

                return '<span class="status-pill badge rounded-1 ' . ($active ? 'bg-success-subtle' : 'bg-danger-subtle') . '">'
                    . ($active ? 'Active' : 'Inactive')
                    . '</span>';
            })
            ->addColumn('action', function ($row) {
                $active = (bool) $row->is_active;

                // Everything the Edit modal needs travels on the button.
                $edit = '<button type="button" class="pe-act pe-act--edit prf-edit-btn"'
                    . ' data-id="' . (int) $row->id . '"'
                    . ' data-course-id="' . ($row->course_id ? (int) $row->course_id : '') . '"'
                    . ' data-event-id="' . ($row->event_id ? (int) $row->event_id : '') . '"'
                    // The picker is keyed by Course Group Mapping, so prefill with the
                    // link, not with peer_groups.id.
                    . ' data-group-id="' . ($row->group_map_pk ? (int) $row->group_map_pk : '') . '"'
                    . ' data-field-label="' . e((string) $row->field_label) . '">'
                    . '<span class="pe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
                    . '<span class="pe-act__label">Edit</span>'
                    . '</button>';

                $previewUrl = route('admin.peer.reflection-fields.preview', array_filter([
                    'course_id' => $row->course_id,
                    'event_id' => $row->event_id,
                    'group_id' => $row->group_id,
                ]));

                $preview = '<a href="' . e($previewUrl) . '" class="pe-act pe-act--preview" title="Preview the form this field appears on">'
                    . '<span class="pe-act__icon"><i class="bi bi-clipboard-check" aria-hidden="true"></i></span>'
                    . '<span class="pe-act__label">Preview<br>Form</span>'
                    . '</a>';

                // The global .status-toggle handler (admin_assets/js/custom.js) owns
                // this: SweetAlert confirm -> POST admin/toggle-status. No page JS.
                // NO .form-check/.form-switch wrapper - that combination yanks the
                // input -2.375rem left (custom.css:107-112) and would knock it off
                // centre above its caption. The caption names the ACTION, not the
                // state, because the state is already shown one column over.
                $toggle = '<label class="pe-act pe-act--toggle">'
                    . '<span class="pe-act__icon">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    . ' data-table="peer_reflection_fields" data-column="is_active"'
                    . ' data-id_column="id" data-id="' . (int) $row->id . '"'
                    . ($active ? ' checked' : '') . '>'
                    . '</span>'
                    . '<span class="pe-act__label">' . ($active ? 'Deactivate' : 'Activate') . '</span>'
                    . '</label>';

                $delete = '<button type="button" class="pe-act pe-act--del prf-delete-btn"'
                    . ' data-id="' . (int) $row->id . '"'
                    . ' data-field-label="' . e((string) $row->field_label) . '">'
                    . '<span class="pe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
                    . '<span class="pe-act__label">Delete</span>'
                    . '</button>';

                return '<div class="pe-act-group pe-act-group--wide" role="group" aria-label="Row actions">'
                    . $edit . $preview . $toggle . $delete . '</div>';
            })
            ->filterColumn('course_name', function ($query, $keyword) {
                $query->where('course_master.course_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('event_name', function ($query, $keyword) {
                $query->where('peer_events.event_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('field_label', function ($query, $keyword) {
                $query->where('peer_reflection_fields.field_label', 'like', "%{$keyword}%");
            })
            ->filterColumn('created_date', function ($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(peer_reflection_fields.created_at, '%d/%m/%Y') like ?", ["%{$keyword}%"]);
            })
            ->orderColumn('course_name', 'course_master.course_name $1')
            ->orderColumn('event_name', 'peer_events.event_name $1')
            ->orderColumn('field_label', 'peer_reflection_fields.field_label $1')
            ->orderColumn('created_date', 'peer_reflection_fields.created_at $1')
            ->orderColumn('status', 'peer_reflection_fields.is_active $1')
            ->filter(function ($query) {
                $searchValue = request()->input('search.value');

                if (! empty($searchValue)) {
                    $query->where(function ($sub) use ($searchValue) {
                        $sub->where('peer_reflection_fields.field_label', 'like', "%{$searchValue}%")
                            ->orWhere('course_master.course_name', 'like', "%{$searchValue}%")
                            ->orWhere('peer_events.event_name', 'like', "%{$searchValue}%")
                            ->orWhereRaw("DATE_FORMAT(peer_reflection_fields.created_at, '%d/%m/%Y') like ?", ["%{$searchValue}%"]);
                    });
                }
            }, true)
            ->rawColumns(['status', 'action'])
            ->setRowId('id');
    }

    public function query(PeerReflectionField $model): QueryBuilder
    {
        return self::baseQuery(
            $model,
            request('course_filter'),
            request('event_filter'),
            request('status_filter')
        );
    }

    /**
     * The Active / Archived pills, scoped on the COURSE a field belongs to.
     *
     * includeUnscoped: a field with no course is GLOBAL - it is on every
     * evaluation form regardless of which courses are running - so it shows under
     * BOTH pills rather than vanishing from both. That is the one place these
     * pills are not a strict partition, and it is deliberate: the alternative is a
     * row that exists but can be reached from no tab.
     */
    public static function applyStatusScope($query, $status)
    {
        return PeerCourseStatusScope::forRelated(
            $query,
            $status,
            'peer_reflection_fields.course_id',
            true
        );
    }

    /**
     * The one query the grid and every export share, so a download can never show
     * a different set of rows than the screen.
     */
    public static function baseQuery(
        PeerReflectionField $model,
        $courseFilter = null,
        $eventFilter = null,
        $status = null
    ): QueryBuilder {
        $query = $model->newQuery()
            ->leftJoin('course_master', 'course_master.pk', '=', 'peer_reflection_fields.course_id')
            ->leftJoin('peer_events', 'peer_events.id', '=', 'peer_reflection_fields.event_id')
            ->leftJoin('peer_groups', 'peer_groups.id', '=', 'peer_reflection_fields.group_id')
            ->select([
                'peer_reflection_fields.id',
                'peer_reflection_fields.field_label',
                'peer_reflection_fields.course_id',
                'peer_reflection_fields.event_id',
                'peer_reflection_fields.group_id',
                'peer_groups.group_map_pk as group_map_pk',
                'peer_reflection_fields.is_active',
                'peer_reflection_fields.created_at',
                'course_master.course_name as course_name',
                'peer_events.event_name as event_name',
            ]);

        self::applyStatusScope($query, $status);

        if (filled($courseFilter)) {
            $query->where('peer_reflection_fields.course_id', $courseFilter);
        }

        if (filled($eventFilter)) {
            $query->where('peer_reflection_fields.event_id', $eventFilter);
        }

        // Newest first by default, but only while the user hasn't clicked a header.
        if (empty(request('order'))) {
            $query->orderBy('peer_reflection_fields.id', 'desc');
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        // No ->buttons(): the page ships its own server-rendered Download/Print,
        // and Button::make('reset'|'reload') throws "unknown button type", after
        // which jQuery skips every later init.dt handler - which is what strips
        // the search box and pager off a grid that otherwise looks fine.
        return $this->builder()
            ->setTableId('peerReflectionFieldsTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->selectStyleSingle()
            ->parameters([
                // Responsive OFF, deliberately. With 7 columns and a four-action
                // row group this table is wider than the card, and the Responsive
                // plugin "solves" that by collapsing the LAST column into a child
                // row - which hides Edit / Preview / the status switch / Delete
                // behind a expander. The panel's .table-responsive wrapper already
                // handles overflow by scrolling, which keeps every action reachable.
                'responsive' => false,
                // scrollX stays FALSE: it makes DataTables split the table into
                // scrollHead/scrollBody and inject a sizing row, which renders as
                // an empty grey band under the header. The panel's
                // .table-responsive wrapper already gives overflow-x: auto.
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
                    'emptyTable' => 'No reflection fields found',
                    'zeroRecords' => 'No reflection fields match your search',
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')->title('S. No.')->searchable(false)->orderable(false)->addClass('text-center'),
            Column::make('course_name')->title('Course Name')->orderable(true)->searchable(true),
            Column::make('event_name')->title('Event Name')->orderable(true)->searchable(true),
            Column::make('field_label')->title('Field Label')->orderable(true)->searchable(true)->addClass('prf-col-wrap'),
            Column::make('created_date')->title('Reflection Field Created Date')->orderable(true)->searchable(true)->addClass('text-center'),
            Column::computed('status')->title('Status')->orderable(true)->searchable(false)->addClass('text-center'),
            Column::computed('action')->title('Action')->orderable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'PeerReflectionFields_' . date('YmdHis');
    }
}
