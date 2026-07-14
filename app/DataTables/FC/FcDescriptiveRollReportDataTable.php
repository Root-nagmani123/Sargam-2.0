<?php

namespace App\DataTables\FC;

use App\Models\FC\FcForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Server-side (AJAX) list of students for the Descriptive Roll (first-2-steps) report.
 * Scoped to a single course/form; reuses FcFormOverviewDataTable's base query so the
 * joins / user resolution stay identical to the other FC reports.
 */
class FcDescriptiveRollReportDataTable extends DataTable
{
    protected ?FcForm $form;
    protected string $userKey;

    public function __construct(?FcForm $form = null)
    {
        $this->form    = $form;
        $this->userKey = $form ? ($form->user_identifier ?: 'user_id') : 'user_id';
    }

    // ── Base query (scoped to the selected course/form) ───────────────
    public function query()
    {
        // No course selected → return an empty, correctly-shaped result set.
        if (! $this->form) {
            return DB::table('student_masters')
                ->whereRaw('1 = 0')
                ->selectRaw(
                    "NULL as user_id, NULL as login_username, NULL as route_user_id, "
                    . "NULL as full_name, NULL as service_code, NULL as allotted_state, NULL as mobile_no"
                );
        }

        return (new FcFormOverviewDataTable($this->form))->query();
    }

    // ── Transform + AJAX filtering ────────────────────────────────────
    public function dataTable($query)
    {
        $userKey = $this->userKey;
        $form    = $this->form;

        $dt = datatables()
            ->query($query)
            ->addIndexColumn();

        $dt->editColumn('login_username', fn ($row) =>
            '<code style="font-size:11px">' . e($row->login_username ?? '—') . '</code>'
        );
        $dt->editColumn('full_name', fn ($row) => e($row->full_name ?? '—'));
        $dt->editColumn('service_code', fn ($row) =>
            $row->service_code
                ? '<span class="badge bg-primary-subtle text-primary" style="font-size:10px;">' . e($row->service_code) . '</span>'
                : '—'
        );
        $dt->editColumn('allotted_state', fn ($row) => e($row->allotted_state ?? '—'));
        $dt->editColumn('mobile_no', fn ($row) => e($row->mobile_no ?? '—'));

        // Per-student "Descriptive Roll (Steps 1-2)" PDF download button.
        $dt->addColumn('action', function ($row) use ($userKey) {
            $id = $row->{$userKey} ?? null;
            if ($id === null || $id === '') {
                return '<span class="text-muted">—</span>';
            }
            $url = route('admin.reports.descriptive-roll.student.pdf', $id);
            return '<a href="' . e($url) . '" target="_blank" '
                . 'class="btn btn-outline-danger py-0 px-2" style="font-size:11px;" '
                . 'title="Download this student\'s Descriptive Roll (Steps 1-2) PDF">'
                . '<i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>';
        });

        // Custom "User Name" filter (course is applied by scoping the base query).
        $dt->filter(function ($q) use ($form) {
            if (! $form) {
                return;
            }
            $req  = request();
            // Accept both the native DataTables search box (search[value]) and the
            // legacy custom "search_term" param.
            $term = trim((string) ($req->input('search_term') ?: $req->input('search.value') ?: ''));
            if ($term === '') {
                return;
            }

            $like     = '%' . $term . '%';
            $t        = $form->trackerStorageTable();
            $userKey  = $form->user_identifier ?: 'user_id';
            $hasFrm   = Schema::hasTable('fc_registration_master');

            $q->where(function ($sub) use ($like, $t, $userKey, $hasFrm) {
                $sub->where('s1.full_name', 'like', $like)
                    ->orWhere('s1.first_name', 'like', $like)
                    ->orWhere('s1.last_name', 'like', $like)
                    ->orWhereRaw("CONCAT(COALESCE(s1.first_name,''),' ',COALESCE(s1.last_name,'')) LIKE ?", [$like])
                    ->orWhere('s1.mobile_no', 'like', $like)
                    ->orWhere('uc.user_name', 'like', $like)
                    ->orWhere("{$t}.{$userKey}", 'like', $like);
                if ($hasFrm) {
                    $sub->orWhere('frm.user_id', 'like', $like);
                }
            });
        }, false); // autoFilter off: our closure is the only search (columns are aliases,
                   // Yajra's default global search would emit invalid SQL on them).

        // Order hints for aliased columns (avoid ORDER BY on raw expressions).
        $dt->orderColumn('full_name', 's1.full_name $1');
        $dt->orderColumn('service_code', 'svc.service_short_name $1');
        $dt->orderColumn('allotted_state', 'st.state_name $1');
        $dt->orderColumn('mobile_no', 's1.mobile_no $1');

        $dt->rawColumns(['login_username', 'service_code', 'action']);

        return $dt;
    }

    // ── HTML builder (only used to render the <table> element) ─────────
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('descriptiveRollTable')
            ->addTableClass('table table-bordered table-striped table-hover text-nowrap align-middle mb-0')
            ->columns($this->getColumns())
            ->parameters([
                'processing' => true,
                'serverSide' => true,
                'searching'  => false,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex')
                ->title('S.No.')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false)
                ->width('50px'),

            Column::make('login_username')->title('Username')->orderable(false),
            Column::make('full_name')->title('Full Name'),
            Column::make('service_code')->title('Service')->searchable(false),
            Column::make('allotted_state')->title('State')->searchable(false),
            Column::make('mobile_no')->title('Mobile'),

            Column::computed('action')
                ->title('Descriptive Roll (Steps 1–2)')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false)
                ->width('160px'),
        ];
    }

    protected function filename(): string
    {
        return 'DescriptiveRollReport_' . date('YmdHis');
    }
}
