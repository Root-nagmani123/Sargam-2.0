<?php

namespace App\DataTables\FC;

use App\Models\FC\FcActivityMaster;
use App\Models\FC\FcOtActivity;
use App\Models\FC\FcOtDetail;
use App\Services\FC\FcPostArrivalAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Services\DataTable;

class FcActivitiesHomeDataTable extends DataTable
{
    public function __construct(private FcPostArrivalAccessService $access)
    {
    }

    /**
     * @param  Builder  $query
     * @return \Yajra\DataTables\EloquentDataTable
     */
    public function dataTable($query)
    {
        $tbl = (new FcOtActivity)->getTable();
        $request = $this->request();

        return DataTables::eloquent($query)
            ->filter(function (Builder $q) use ($request, $tbl) {
                $kw = trim((string) data_get($request->input('search'), 'value', ''));
                if ($kw === '') {
                    return true;
                }
                $like = '%'.addcslashes($kw, '%_\\').'%';
                $q->where(function (Builder $w) use ($like, $tbl) {
                    $w->where($tbl.'.course', 'like', $like)
                        ->orWhere($tbl.'.activityval', 'like', $like)
                        ->orWhere($tbl.'.activitydt', 'like', $like)
                        ->orWhere($tbl.'.username', 'like', $like)
                        ->orWhereHas('ot', fn (Builder $qq) => $qq->where('otname', 'like', $like)->orWhere('otcode', 'like', $like))
                        ->orWhereHas('activityMaster', fn (Builder $qq) => $qq->where('menun', 'like', $like));
                });

                return false;
            })
            ->orderColumn('ot_name', function (Builder $q, string $order) use ($tbl) {
                $dir = strtolower($order) === 'asc' ? 'asc' : 'desc';
                $q->orderBy(
                    FcOtDetail::query()
                        ->select('otname')
                        ->whereColumn('username', $tbl.'.username')
                        ->limit(1),
                    $dir
                );
            })
            ->orderColumn('ot_code', function (Builder $q, string $order) use ($tbl) {
                $dir = strtolower($order) === 'asc' ? 'asc' : 'desc';
                $q->orderBy(
                    FcOtDetail::query()
                        ->select('otcode')
                        ->whereColumn('username', $tbl.'.username')
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
            ->addColumn('ot_name', fn (FcOtActivity $r) => (string) ($r->ot->otname ?? ''))
            ->addColumn('ot_code', fn (FcOtActivity $r) => (string) ($r->ot->otcode ?? ''))
            ->addColumn('activity_label', fn (FcOtActivity $r) => (string) ($r->activityMaster->menun ?? $r->activity))
            ->editColumn('course', fn (FcOtActivity $r) => (string) ($r->course ?? ''))
            ->editColumn('activityval', fn (FcOtActivity $r) => (string) ($r->activityval ?? ''))
            ->editColumn('activitydt', fn (FcOtActivity $r) => (string) ($r->activitydt ?? ''))
            ->addColumn('action', function (FcOtActivity $r) {
                $payload = [
                    'updateUrl' => route('fc-reg.admin.activities.update', $r->activityid),
                    'course' => $r->course,
                    'menuid' => $r->activity,
                    'menun' => $r->activityMaster->menun ?? $r->activity,
                    'activityval' => $r->activityval,
                    'otname' => $r->ot->otname ?? '',
                    'otcode' => $r->ot->otcode ?? '',
                    'house' => $r->ot->house ?? '',
                    'housen' => $r->ot->housen ?? '',
                ];
                $json = e(json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
                $destroy = route('fc-reg.admin.activities.destroy', $r->activityid);

                return '<button type="button" class="btn btn-link btn-sm p-0 js-fc-act-edit" data-bs-toggle="modal" data-bs-target="#modalFcActEdit" data-fc-act-edit="'.$json.'">Edit</button>'
                    .'<form method="POST" action="'.e($destroy).'" class="d-inline" onsubmit="return confirm(\'Delete this record?\')">'
                    .'<input type="hidden" name="_token" value="'.e(csrf_token()).'">'
                    .'<input type="hidden" name="_method" value="DELETE">'
                    .'<button type="submit" class="btn btn-link btn-sm text-danger p-0 ms-1">Delete</button></form>';
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
        $user = Auth::user();
        $request = $this->request();

        $query = $this->activitiesGridBaseQuery($user->username, $this->access->departmentIdsForActivityEntry());

        $tbl = (new FcOtActivity)->getTable();
        $course = trim((string) $request->input('filter_course', ''));
        $otcode = trim((string) $request->input('filter_otcode', ''));
        $menuid = trim((string) $request->input('filter_activity', ''));

        $query->when($course !== '', fn (Builder $q) => $q->where($tbl.'.course', $course))
            ->when($otcode !== '', fn (Builder $q) => $q->whereHas('ot', fn (Builder $qq) => $qq->where('otcode', 'like', '%'.$otcode.'%')))
            ->when($menuid !== '', fn (Builder $q) => $q->where($tbl.'.activity', $menuid));

        return $query;
    }

    /**
     * @param  array<int,int>|null  $deptIds
     */
    private function activitiesGridBaseQuery(string $submittedBy, ?array $deptIds): Builder
    {
        $tbl = (new FcOtActivity)->getTable();

        $query = FcOtActivity::query()
            ->with([
                'ot' => static fn ($q) => $q->select('username', 'otname', 'otcode', 'house', 'housen'),
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
}
