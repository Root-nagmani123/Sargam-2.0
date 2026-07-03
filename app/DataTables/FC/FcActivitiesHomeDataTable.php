<?php

namespace App\DataTables\FC;

use App\Models\FC\FcActivityMaster;
use App\Models\FC\FcForm;
use App\Models\FC\FcOtActivity;
use App\Models\StudentMaster;
use App\Services\FC\FcActivityStudentResolver;
use App\Services\FC\FcPostArrivalAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Services\DataTable;

class FcActivitiesHomeDataTable extends DataTable
{
    public function __construct(
        private FcPostArrivalAccessService $access,
        private FcActivityStudentResolver $trainees
    ) {
    }

    /**
     * @param  Builder  $query
     * @return \Yajra\DataTables\EloquentDataTable
     */
    public function dataTable($query)
    {
        $tbl = (new FcOtActivity)->getTable();
        $actUserCol = fc_user_col('fc_otactivity_details');
        $request = $this->request();

        return DataTables::eloquent($query)
            ->filter(function (Builder $q) use ($request, $tbl, $actUserCol) {
                $kw = trim((string) data_get($request->input('search'), 'value', ''));
                if ($kw === '') {
                    return true;
                }
                $like = '%'.addcslashes($kw, '%_\\').'%';
                $q->where(function (Builder $w) use ($like, $tbl, $actUserCol) {
                    $w->where($tbl.'.course', 'like', $like)
                        ->orWhere($tbl.'.activityval', 'like', $like)
                        ->orWhere($tbl.'.activitydt', 'like', $like)
                        ->orWhere($tbl.'.'.$actUserCol, 'like', $like)
                        ->orWhereHas('studentViaCredentials', function (Builder $qq) use ($like) {
                            $qq->where('generated_OT_code', 'like', $like)
                                ->orWhere('display_name', 'like', $like)
                                ->orWhere('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like);
                        })
                        ->orWhereHas('studentMasterDirect', function (Builder $qq) use ($like) {
                            $qq->where('generated_OT_code', 'like', $like)
                                ->orWhere('display_name', 'like', $like)
                                ->orWhere('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like);
                        })
                        ->orWhereHas('activityMaster', fn (Builder $qq) => $qq->where('menun', 'like', $like));
                });

                return false;
            })
            ->orderColumn('ot_name', function (Builder $q, string $order) use ($tbl, $actUserCol) {
                $dir = strtolower($order) === 'asc' ? 'asc' : 'desc';
                $q->orderBy(
                    StudentMaster::query()
                        ->selectRaw('COALESCE(NULLIF(TRIM(display_name), ""), TRIM(CONCAT(COALESCE(first_name,""), " ", COALESCE(middle_name,""), " ", COALESCE(last_name,""))))')
                        ->join('user_credentials as uc', 'uc.user_id', '=', 'student_master.pk')
                        ->whereColumn('uc.pk', $tbl.'.'.$actUserCol)
                        ->limit(1),
                    $dir
                );
            })
            ->orderColumn('ot_code', function (Builder $q, string $order) use ($tbl, $actUserCol) {
                $dir = strtolower($order) === 'asc' ? 'asc' : 'desc';
                $q->orderBy(
                    StudentMaster::query()
                        ->select('generated_OT_code')
                        ->join('user_credentials as uc', 'uc.user_id', '=', 'student_master.pk')
                        ->whereColumn('uc.pk', $tbl.'.'.$actUserCol)
                        ->limit(1),
                    $dir
                );
            })
            ->orderColumn('activity_label', function (Builder $q, string $order) use ($tbl) {
                $dir = strtolower($order) === 'asc' ? 'asc' : 'desc';
                $q->orderBy(
                    FcActivityMaster::query()
                        ->select('menun')
                        ->whereColumn('menuid', $tbl.'.activity')
                        ->limit(1),
                    $dir
                );
            })
            ->orderColumn('course', $tbl.'.course $1')
            ->orderColumn('activityval', $tbl.'.activityval $1')
            ->orderColumn('activitydt', $tbl.'.activitydt $1')
            ->addColumn('ot_name', fn (FcOtActivity $r) => ($sm = $this->studentForRow($r))
                ? $this->trainees->displayName($sm)
                : '')
            ->addColumn('ot_code', fn (FcOtActivity $r) => (string) ($this->studentForRow($r)?->generated_OT_code ?? ''))
            ->addColumn('activity_label', fn (FcOtActivity $r) => (string) ($r->activityMaster->menun ?? $r->activity))
            ->editColumn('course', fn (FcOtActivity $r) => (string) ($r->course ?? ''))
            ->editColumn('activityval', fn (FcOtActivity $r) => (string) ($r->activityval ?? ''))
            ->editColumn('activitydt', fn (FcOtActivity $r) => (string) ($r->activitydt ?? ''))
            ->addColumn('action', function (FcOtActivity $r) {
                $sm = $this->studentForRow($r);
                $payload = [
                    'updateUrl' => route('fc-reg.admin.activities.update', $r->activityid),
                    'course' => $r->course,
                    'menuid' => $r->activity,
                    'menun' => $r->activityMaster->menun ?? $r->activity,
                    'department_id' => (int) ($r->activityMaster->department_id ?? 0),
                    'activityval' => $r->activityval,
                    'otname' => $sm ? $this->trainees->displayName($sm) : '',
                    'otcode' => (string) ($sm->generated_OT_code ?? ''),
                    'house' => '',
                    'housen' => (string) ($sm->rank ?? ''),
                ];
                $json = e(json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
                $destroy = route('fc-reg.admin.activities.destroy', $r->activityid);

                return '<span class="d-inline-flex align-items-center gap-1 flex-shrink-0">'
                    .'<button type="button" class="btn btn-link btn-sm p-0 js-fc-act-edit" data-bs-toggle="modal" data-bs-target="#modalFcActEdit" data-fc-act-edit="'.$json.'" aria-label="Edit activity">'
                    .'<i class="bi bi-pencil" aria-hidden="true"></i></button>'
                    .'<form method="POST" action="'.e($destroy).'" class="d-inline m-0" onsubmit="return confirm(\'Delete this record?\')">'
                    .'<input type="hidden" name="_token" value="'.e(csrf_token()).'">'
                    .'<input type="hidden" name="_method" value="DELETE">'
                    .'<button type="submit" class="btn btn-link btn-sm text-danger p-0" aria-label="Delete activity">'
                    .'<i class="bi bi-trash" aria-hidden="true"></i></button></form></span>';
            })
            ->rawColumns(['action'])
            ->only([
                'ot_name',
                'ot_code',
                'course',
                'activity_label',
                'activityval',
                'activitydt',
                'action',
            ]);
    }

    public function query(): Builder
    {
        $request = $this->request();
        $submittedBy = trim((string) (Auth::user()?->user_name ?? ''));

        if ($submittedBy === '') {
            return FcOtActivity::query()->whereRaw('0 = 1');
        }

        $query = $this->activitiesGridBaseQuery($submittedBy, $this->access->departmentIdsForActivityEntry());

        $tbl = (new FcOtActivity)->getTable();
        $formId = trim((string) $request->input('filter_form_id', ''));
        $otcode = trim((string) $request->input('filter_otcode', ''));
        $menuid = trim((string) $request->input('filter_activity', ''));

        $query->when($formId !== '', function (Builder $q) use ($formId, $tbl) {
            $coursePk = FcForm::query()->whereKey((int) $formId)->value('course_master_pk');
            if (! $coursePk) {
                return;
            }
            $actCol = fc_user_col('fc_otactivity_details');
            $q->whereExists(function ($sub) use ($coursePk, $tbl, $actCol) {
                $sub->selectRaw('1')
                    ->from('user_credentials as uc')
                    ->join('student_master as sm', 'sm.pk', '=', 'uc.user_id')
                    ->whereColumn('uc.pk', "{$tbl}.{$actCol}")
                    ->where(function ($w) use ($coursePk) {
                        $w->where('sm.course_master_pk', $coursePk)
                            ->orWhereExists(function ($m) use ($coursePk) {
                                $m->select(DB::raw(1))
                                    ->from('student_master_course__map as smcm')
                                    ->whereColumn('smcm.student_master_pk', 'sm.pk')
                                    ->where('smcm.course_master_pk', $coursePk)
                                    ->where('smcm.active_inactive', 1);
                            });
                    });
            });
        })
            ->when($otcode !== '', function (Builder $q) use ($otcode) {
                $like = '%'.$otcode.'%';
                $q->where(function (Builder $w) use ($like) {
                    $w->whereHas('studentViaCredentials', fn (Builder $qq) => $qq->where('generated_OT_code', 'like', $like))
                        ->orWhereHas('studentMasterDirect', fn (Builder $qq) => $qq->where('generated_OT_code', 'like', $like));
                });
            })
            ->when($menuid !== '', fn (Builder $q) => $q->where($tbl.'.activity', $menuid));

        return $query;
    }

    /**
     * @param  array<int,int>|null  $deptIds
     */
    private function activitiesGridBaseQuery(string $submittedBy, ?array $deptIds): Builder
    {
        $tbl = (new FcOtActivity)->getTable();

        $studentCols = [
            'student_master.pk',
            'student_master.generated_OT_code',
            'student_master.display_name',
            'student_master.first_name',
            'student_master.middle_name',
            'student_master.last_name',
            'student_master.rank',
        ];

        $query = FcOtActivity::query()
            ->with([
                'studentViaCredentials' => static fn ($q) => $q->select($studentCols),
                'studentMasterDirect' => static fn ($q) => $q->select($studentCols),
                'activityMaster' => static fn ($q) => $q->select('menuid', 'menun', 'department_id'),
            ])
            ->where($tbl.'.submitedby', $submittedBy)
            ->where($tbl.'.status', 1);

        if ($deptIds !== null) {
            if ($deptIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereHas('activityMaster', static fn ($q2) => $q2->whereIn('department_id', $deptIds));
            }
        }

        return $query->orderByDesc($tbl.'.id');
    }

    private function studentForRow(FcOtActivity $row): ?StudentMaster
    {
        return $row->studentViaCredentials ?? $row->studentMasterDirect;
    }
}
