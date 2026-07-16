<?php

namespace App\DataTables\FC;

use App\Models\FC\FcForm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Server-side (AJAX) admin report of the "Health Risk Factors" step (student_master_seconds).
 * Scoped to a course/form; reuses FcFormOverviewDataTable's base query for the user/name/service
 * resolution and joins the health columns onto it.
 */
class FcHealthRiskReportDataTable extends DataTable
{
    protected ?FcForm $form;
    protected string $userKey;

    /** health column => short display title (full label shown via the header `title` attribute in the view). */
    public const HEALTH_COLUMNS = [
        'health_asthma'             => 'Asthma',
        'health_lung_disease'       => 'Chronic Lung Disease',
        'health_kidney_disease'     => 'Kidney (Dialysis)',
        'health_diabetes'           => 'Diabetes',
        'health_blood_disorder'     => 'Blood Disorder',
        'health_immunocompromised'  => 'Immunocompromised',
        'health_liver_disease'      => 'Liver Disease',
        'health_cardiac_condition'  => 'Serious Cardiac',
        'health_pregnant_lactating' => 'Pregnant / Lactating',
        'health_additional_info'    => 'Additional Info',
    ];

    public function __construct(?FcForm $form = null)
    {
        $this->form    = $form;
        $this->userKey = $form ? ($form->user_identifier ?: 'user_id') : 'user_id';
    }

    public function query()
    {
        if (! $this->form) {
            return DB::table('student_masters')->whereRaw('1 = 0')
                ->selectRaw('NULL as user_id, NULL as login_username, NULL as route_user_id, NULL as full_name, NULL as service_code');
        }

        $query = (new FcFormOverviewDataTable($this->form))->query();
        // Health answers live on student_master_seconds, keyed by the same user_id as s1.
        $query->leftJoin('student_master_seconds as s2', 's2.user_id', '=', 's1.user_id')
              ->addSelect(array_map(fn ($c) => "s2.$c", array_keys(self::HEALTH_COLUMNS)));

        return $query;
    }

    public function dataTable($query)
    {
        $userKey = $this->userKey;
        $form    = $this->form;

        $dt = datatables()->query($query)->addIndexColumn();

        $dt->editColumn('login_username', fn ($row) =>
            '<code style="font-size:11px">' . e($row->login_username ?? '—') . '</code>'
        );
        $dt->editColumn('full_name', fn ($row) => e($row->full_name ?? '—'));
        $dt->editColumn('service_code', fn ($row) =>
            $row->service_code
                ? '<span class="badge bg-primary-subtle text-primary" style="font-size:10px;">' . e($row->service_code) . '</span>'
                : '—'
        );

        foreach (array_keys(self::HEALTH_COLUMNS) as $col) {
            $dt->editColumn($col, function ($row) use ($col) {
                $val = trim((string) ($row->{$col} ?? ''));
                return $val === '' ? '<span class="text-muted">—</span>' : e($val);
            });
        }

        $dt->filter(function ($q) use ($form) {
            if (! $form) {
                return;
            }
            $req  = request();
            $term = trim((string) ($req->input('search_term') ?: $req->input('search.value') ?: ''));
            if ($term === '') {
                return;
            }
            $like    = '%' . $term . '%';
            $t       = $form->trackerStorageTable();
            $userKey = $form->user_identifier ?: 'user_id';
            $hasFrm  = Schema::hasTable('fc_registration_master');
            $q->where(function ($sub) use ($like, $t, $userKey, $hasFrm) {
                $sub->where('s1.full_name', 'like', $like)
                    ->orWhere('s1.first_name', 'like', $like)
                    ->orWhere('s1.last_name', 'like', $like)
                    ->orWhere('s1.mobile_no', 'like', $like)
                    ->orWhere('uc.user_name', 'like', $like)
                    ->orWhere("{$t}.{$userKey}", 'like', $like);
                if ($hasFrm) {
                    $sub->orWhere('frm.user_id', 'like', $like);
                }
            });
        }, false);

        $dt->orderColumn('full_name', 's1.full_name $1');

        $dt->rawColumns(array_merge(
            ['login_username', 'service_code'],
            array_keys(self::HEALTH_COLUMNS)
        ));

        return $dt;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('healthRiskReportTable')
            ->addTableClass('table table-bordered table-striped table-hover text-nowrap align-middle mb-0')
            ->columns($this->getColumns())
            ->parameters(['processing' => true, 'serverSide' => true, 'searching' => false]);
    }

    public function getColumns(): array
    {
        $cols = [
            Column::computed('DT_RowIndex')->title('S.No.')->addClass('text-center')->orderable(false)->searchable(false)->width('50px'),
            Column::make('login_username')->title('Username')->orderable(false),
            Column::make('full_name')->title('Full Name'),
            Column::make('service_code')->title('Service')->searchable(false),
        ];
        foreach (self::HEALTH_COLUMNS as $col => $title) {
            $cols[] = Column::make($col)->title($title)->orderable(false)->searchable(false);
        }

        return $cols;
    }

    protected function filename(): string
    {
        return 'HealthRiskReport_' . date('YmdHis');
    }
}
