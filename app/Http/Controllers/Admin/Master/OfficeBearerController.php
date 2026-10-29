<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\Master\OfficeBearerDataTable;
use App\Exports\OfficeBearerExport;
use App\Http\Controllers\Controller;
use App\Models\ClubSocietyMaster;
use App\Services\ClubSociety\OfficeBearerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Communications -> Club/ Society -> Officer Bearers.
 *
 * A read-only listing of who holds each post: the winners of every election
 * whose result has been published. The "who won" rule lives in
 * OfficeBearerService so this screen and the election result screen agree.
 */
class OfficeBearerController extends Controller
{
    public function index(OfficeBearerDataTable $dataTable)
    {
        return $dataTable->render('admin.master.office_bearer.index', [
            'courses'        => $this->courseOptions('active'),
            'archiveCourses' => $this->courseOptions('archive'),
            'clubSocieties'  => ClubSocietyMaster::where('active_inactive', 1)
                ->orderBy('club_society_name')->get(['pk', 'club_society_name']),
        ]);
    }

    /**
     * The one definition of the grid, shared by the DataTable, the Excel export
     * and the print sheet so the three cannot drift apart.
     */
    public static function baseQuery(string $status = 'active', $coursePk = null, $clubPk = null)
    {
        return OfficeBearerService::electedQuery([
            'status'                 => $status,
            'course_master_pk'       => $coursePk,
            'club_society_master_pk' => $clubPk,
        ])
            ->orderBy('ob.course_name')
            ->orderBy('ob.club_society_name')
            ->orderBy('ob.role_name')
            ->orderBy('ob.rank_in_post');
    }

    public static function rows(string $status = 'active', $coursePk = null, $clubPk = null)
    {
        return self::baseQuery($status, $coursePk, $clubPk)->get();
    }

    public function export(Request $request)
    {
        try {
            return Excel::download(
                new OfficeBearerExport(
                    $request->input('status_filter', 'active'),
                    $request->input('course_master_pk'),
                    $request->input('club_society_master_pk')
                ),
                'officer_bearers.xlsx'
            );
        } catch (\Throwable $e) {
            return redirect()->route('master.office.bearer.index')
                ->with('error', 'Error exporting data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        $status   = $request->input('status_filter', 'active');
        $coursePk = $request->input('course_master_pk');
        $clubPk   = $request->input('club_society_master_pk');

        $filterBits = ['<strong>Status:</strong> ' . ($status === 'archive' ? 'Archived' : 'Active')];
        if (filled($coursePk)) {
            $name = DB::table('course_master')->where('pk', $coursePk)->value('course_name');
            if ($name) {
                $filterBits[] = '<strong>Course:</strong> ' . e($name);
            }
        }
        if (filled($clubPk)) {
            $name = DB::table('club_society_master')->where('pk', $clubPk)->value('club_society_name');
            if ($name) {
                $filterBits[] = '<strong>Club/ Society:</strong> ' . e($name);
            }
        }

        return view('admin.master.office_bearer.export_print', [
            'rows'       => self::rows($status, $coursePk, $clubPk),
            'exportDate' => now()->format('d M Y, h:i A'),
            'filterLine' => implode(' &nbsp;|&nbsp; ', $filterBits),
        ]);
    }

    private function courseOptions(string $status)
    {
        return DB::table('course_master')
            ->where('active_inactive', $status === 'archive' ? 0 : 1)
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'couse_short_name']);
    }
}
