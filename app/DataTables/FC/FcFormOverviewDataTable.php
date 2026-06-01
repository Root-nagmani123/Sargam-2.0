<?php

namespace App\DataTables\FC;

use App\Models\FC\FcForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class FcFormOverviewDataTable extends DataTable
{
    protected FcForm $form;
    public \Illuminate\Support\Collection $steps;
    protected string $trackerTable;
    protected string $userKey;
    public int $totalSteps;

    public function __construct(FcForm $form)
    {
        $this->form         = $form;
        $this->trackerTable = $form->trackerStorageTable();
        $this->userKey      = $form->user_identifier ?: 'user_id';

        // Only steps with a whitelisted tracker_column are treated as progress columns.
        $this->steps = $form->activeSteps()
            ->whereNotNull('tracker_column')
            ->orderBy('step_number')
            ->get()
            ->filter(fn ($s) => preg_match('/^[a-zA-Z0-9_]+$/', $s->tracker_column))
            ->values();

        $this->totalSteps = $this->steps->count();
    }

    // ── Base query ────────────────────────────────────────────────────
    public function query()
    {
        $t = $this->trackerTable;
        $u = $this->userKey;

        $stepsDoneExpr = $this->totalSteps > 0
            ? $this->steps
                ->map(fn ($s) => "CASE WHEN `{$t}`.`{$s->tracker_column}`=1 THEN 1 ELSE 0 END")
                ->implode(' + ')
            : '0';

        $query = DB::table($t);

        fc_report_apply_tracker_user_resolution($query, $t, $t);
        fc_report_join_student_master_firsts($query, $t, $t);

        $query->leftJoin('service_master as svc', 's1.service_id', '=', 'svc.pk')
            ->leftJoin('state_masters as st', 's1.allotted_state_id', '=', 'st.id');

        if ($u === 'user_id') {
            $query->addSelect([
                DB::raw(fc_report_route_user_id_sql($t, $t).' as route_user_id'),
                DB::raw(fc_report_login_username_sql($t, $t).' as login_username'),
            ]);
        } else {
            $query->addSelect([
                DB::raw('uc.pk as route_user_id'),
                DB::raw("`{$t}`.`{$u}` as login_username"),
            ]);
        }

        $query->select([
                "{$t}.{$u}",
                "{$t}.status",
                's1.full_name',
                's1.mobile_no',
                DB::raw('COALESCE(svc.service_short_name, svc.service_name) as service_code'),
                's1.cadre',
                'st.state_name as allotted_state',
                DB::raw("({$stepsDoneExpr}) as steps_done"),
            ]);

        // Add one boolean column per trackable step
        foreach ($this->steps as $step) {
            $query->addSelect("{$t}.{$step->tracker_column}");
        }

        return $query;
    }

    // ── DataTable transformation + raw HTML rendering ─────────────────
    public function dataTable($query)
    {
        $userKey    = $this->userKey;
        $totalSteps = $this->totalSteps;
        $t          = $this->trackerTable;

        $dt = datatables()
            ->query($query)
            ->addIndexColumn();

        // Login name (OT / user_name), not roster pk stored in user_id
        $dt->editColumn($userKey, fn ($row) =>
            '<code style="font-size:11px">' . e($row->login_username ?? '—') . '</code>'
        );

        // Full name fallback
        $dt->editColumn('full_name', fn ($row) =>
            e($row->full_name ?? '—')
        );

        // Service badge
        $dt->editColumn('service_code', fn ($row) =>
            $row->service_code
                ? '<span class="badge bg-primary-subtle text-primary" style="font-size:10px;">' . e($row->service_code) . '</span>'
                : '—'
        );

        // Tick / cross per step
        foreach ($this->steps as $step) {
            $col = $step->tracker_column;
            $dt->editColumn($col, fn ($row) =>
                ($row->{$col} ?? false)
                    ? '<i class="bi bi-check-circle-fill text-success" style="font-size:13px;"></i>'
                    : '<i class="bi bi-circle text-secondary" style="font-size:13px;opacity:.4;"></i>'
            );
        }

        // Progress bar (based on pre-computed steps_done)
        $dt->addColumn('progress_bar', function ($row) use ($totalSteps) {
            if ($totalSteps < 1) {
                return '—';
            }
            $done = (int) $row->steps_done;
            $pct  = round(($done / $totalSteps) * 100);
            return '<div class="progress mx-auto" style="height:6px;width:60px;">'
                . '<div class="progress-bar bg-success" style="width:' . $pct . '%"></div>'
                . '</div>'
                . '<span style="font-size:10px;color:#666;">' . $done . '/' . $totalSteps . '</span>';
        });

        // Status badge
        $dt->editColumn('status', function ($row) use ($totalSteps) {
            if ($row->status === 'SUBMITTED') {
                return '<span class="badge bg-success" style="font-size:10px;">Submitted</span>';
            }
            if ($totalSteps > 0 && (int) ($row->steps_done ?? 0) >= $totalSteps) {
                return '<span class="badge bg-success" style="font-size:10px;">Complete</span>';
            }

            return '<span class="badge bg-warning text-dark" style="font-size:10px;">Incomplete</span>';
        });

        // Action button
        $dt->addColumn('action', function ($row) use ($userKey) {
            $routeId = $row->route_user_id ?? $row->{$userKey} ?? null;
            if (! $routeId) {
                return '—';
            }
            $url = route('admin.reports.student', $routeId);
            return '<a href="' . e($url) . '" class="btn btn-outline-primary py-0 px-2" style="font-size:11px;" title="View Profile">'
                . '<i class="bi bi-eye"></i></a>';
        });

        // Declare all HTML columns
        $rawCols = [$userKey, 'full_name', 'service_code', 'progress_bar', 'status', 'action'];
        foreach ($this->steps as $step) {
            $rawCols[] = $step->tracker_column;
        }
        $dt->rawColumns($rawCols);

        // Sortable order hints for joined columns
        $dt->orderColumn('full_name', 's1.full_name $1');
        $dt->orderColumn('service_code', 'svc.service_short_name $1');
        $dt->orderColumn('allotted_state', 'st.state_name $1');

        // Custom server-side filtering (replaces DataTables' built-in search)
        $dt->filter(function ($q) use ($t, $userKey) {
            $req = request();

            if ($req->filled('f_status')) {
                $q->where("{$t}.status", $req->f_status);
            }
            if ($req->filled('f_service_id')) {
                $q->where('s1.service_id', $req->f_service_id);
            }
            if ($req->filled('f_search')) {
                $term = '%' . $req->f_search . '%';
                $q->where(function ($sub) use ($term, $t, $userKey) {
                    $sub->where('s1.full_name', 'like', $term)
                        ->orWhere('s1.mobile_no', 'like', $term)
                        ->orWhere('uc.user_name', 'like', $term);
                    if ($userKey === 'user_id') {
                        $sub->orWhere("{$t}.user_id", 'like', $term)
                            ->orWhere('frm.user_id', 'like', $term)
                            ->orWhere('uc_frm.user_name', 'like', $term);
                    } else {
                        $sub->orWhere("{$t}.{$userKey}", 'like', $term);
                    }
                });
            }
        }, false);

        return $dt;
    }

    // ── HTML builder (column definitions + AJAX config) ───────────────
    public function html(): HtmlBuilder
    {
        $ajaxData = "function(d) {
            d.f_status     = document.getElementById('f_status')?.value     || '';
            d.f_service_id = document.getElementById('f_service_id')?.value || '';
            d.f_search     = document.getElementById('f_search')?.value     || '';
        }";

        return $this->builder()
            ->setTableId('fcFormOverviewTable')
            ->addTableClass('table table-hover table-sm mb-0')
            ->columns($this->getColumns())
            ->ajax([
                'url'  => route('admin.reports.form', $this->form),
                'type' => 'GET',
                'data' => $ajaxData,
            ])
            ->parameters([
                'responsive'   => false,
                'autoWidth'    => false,
                'scrollX'      => true,
                'ordering'     => true,
                'searching'    => false,   // We use our own filter form
                'lengthChange' => true,
                'pageLength'   => 25,
                'order'        => [[2, 'asc']], // full_name default
                'lengthMenu'   => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'processing'   => true,
                'serverSide'   => true,
                'dom'          => '<"row mb-2"<"col-sm-6"l><"col-sm-6 text-end"i>>rt<"row mt-2"<"col-sm-12"p>>',
            ]);
    }

    // ── Column definitions ────────────────────────────────────────────
    public function getColumns(): array
    {
        $cols = [
            Column::computed('DT_RowIndex')
                ->title('#')
                ->addClass('text-center')
                ->orderable(false)
                ->searchable(false)
                ->width('40px'),

            Column::make($this->userKey)
                ->title('Username'),

            Column::make('full_name')
                ->title('Full Name'),

            Column::make('service_code')
                ->title('Service')
                ->searchable(false),

            Column::make('cadre')
                ->title('Cadre')
                ->searchable(false),

            Column::make('allotted_state')
                ->title('State')
                ->searchable(false),

            Column::make('mobile_no')
                ->title('Mobile'),
        ];

        // One column per trackable step
        foreach ($this->steps as $step) {
            $cols[] = Column::make($step->tracker_column)
                ->title(Str::limit($step->step_name, 12))
                ->addClass('text-center')
                ->searchable(false);
        }

        $cols[] = Column::computed('progress_bar')
            ->title('Progress')
            ->addClass('text-center')
            ->orderable(false)
            ->searchable(false)
            ->width('80px');

        $cols[] = Column::make('status')
            ->title('Status')
            ->addClass('text-center')
            ->searchable(false);

        $cols[] = Column::computed('action')
            ->title('')
            ->orderable(false)
            ->searchable(false)
            ->width('48px');

        return $cols;
    }
}
