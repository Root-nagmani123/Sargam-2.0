<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\EmployeeMaster;
use App\Models\StudentMasterCourseMap;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DirectoryController extends Controller
{
    /**
     * Page-level search box value. Returns '' for DataTables ajax calls, where
     * `search` arrives as an array and is applied by the datatable engine instead.
     */
    private function filterSearchTerm(Request $request): string
    {
        $search = $request->input('search', '');

        return is_array($search) ? '' : trim((string) $search);
    }

    public function lbsnaa(Request $request)
    {
        // DataTables sends `search` as an array (search[value]); that is handled by the
        // datatable engine, so only the page's own text filter is read here.
        $search = $this->filterSearchTerm($request);
        $sort = (string) $request->input('sort', 'name_asc');
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 10;
        }
        $export = (string) $request->input('export', '');
        $sortMap = [
            'name_asc' => ['employee_master.first_name', 'asc'],
            'name_desc' => ['employee_master.first_name', 'desc'],
            'designation_asc' => ['d.designation_name', 'asc'],
            'designation_desc' => ['d.designation_name', 'desc'],
        ];
        [$sortColumn, $sortDirection] = $sortMap[$sort] ?? $sortMap['name_asc'];

        $employeesQuery = EmployeeMaster::query()
            ->leftJoin('designation_master as d', 'employee_master.designation_master_pk', '=', 'd.pk')
            ->leftJoin('department_master as dept', 'employee_master.department_master_pk', '=', 'dept.pk')
            ->where('employee_master.status', 1)
            ->select([
                'employee_master.pk',
                'employee_master.first_name',
                'employee_master.middle_name',
                'employee_master.last_name',
                'employee_master.current_address',
                'employee_master.office_extension_no',
                'employee_master.mobile',
                'employee_master.residence_no',
                'employee_master.email',
                'employee_master.officalemail',
                'employee_master.profile_picture',
                'd.designation_name',
                'dept.department_name',
            ]);

        if ($search !== '') {
            $employeesQuery->where(function ($query) use ($search) {
                $query->where('employee_master.first_name', 'like', "%{$search}%")
                    ->orWhere('employee_master.middle_name', 'like', "%{$search}%")
                    ->orWhere('employee_master.last_name', 'like', "%{$search}%")
                    ->orWhere('employee_master.email', 'like', "%{$search}%")
                    ->orWhere('employee_master.officalemail', 'like', "%{$search}%")
                    ->orWhere('employee_master.mobile', 'like', "%{$search}%")
                    ->orWhere('d.designation_name', 'like', "%{$search}%")
                    ->orWhere('dept.department_name', 'like', "%{$search}%");
            });
        }

        $employeesQuery = $employeesQuery
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('employee_master.last_name');

        if (in_array($export, ['csv', 'excel'], true)) {
            return $this->streamEmployeesExport($employeesQuery->cursor(), $export);
        }

        if ($request->ajax()) {
            return $this->lbsnaaDatatable($employeesQuery);
        }

        return view('admin.directory.lbsnaa');
    }

    /**
     * Server-side feed for the LBSNAA directory grid (search/sort/paging happen in SQL).
     */
    protected function lbsnaaDatatable($query)
    {
        return \Yajra\DataTables\Facades\DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('name', function ($row) {
                $name = trim(($row->first_name ?? '').' '.($row->middle_name ?? '').' '.($row->last_name ?? ''));

                return e($name ?: '-');
            })
            ->addColumn('email_address', fn ($row) => e(($row->officalemail ?: $row->email) ?: '-'))
            ->editColumn('designation_name', fn ($row) => e($row->designation_name ?: '-'))
            ->editColumn('department_name', fn ($row) => e($row->department_name ?: '-'))
            ->editColumn('current_address', fn ($row) => e($row->current_address ?: '-'))
            ->editColumn('office_extension_no', fn ($row) => e($row->office_extension_no ?: '-'))
            ->editColumn('mobile', fn ($row) => e($row->mobile ?: '-'))
            ->editColumn('residence_no', fn ($row) => e($row->residence_no ?: '-'))
            ->addColumn('photo', function ($row) {
                $src = ! empty($row->profile_picture)
                    ? asset('storage/'.$row->profile_picture)
                    : asset('images/dummypic.jpeg');

                return '<img src="'.e($src).'" alt="photo" class="directory-photo" loading="lazy" decoding="async">';
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('employee_master.first_name', 'like', "%{$keyword}%")
                        ->orWhere('employee_master.middle_name', 'like', "%{$keyword}%")
                        ->orWhere('employee_master.last_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('email_address', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('employee_master.email', 'like', "%{$keyword}%")
                        ->orWhere('employee_master.officalemail', 'like', "%{$keyword}%");
                });
            })
            ->orderColumn('name', 'employee_master.first_name $1')
            ->rawColumns(['photo'])
            ->make(true);
    }

    public function ot(Request $request)
    {
        $activeCourses = CourseMaster::query()
            ->where('active_inactive', 1)
            ->where('end_date', '<', now()->toDateString())
            ->orderByDesc('end_date')
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'couse_short_name']);

        $selectedCourseId = (int) $request->input('course_id', 0);
        // DataTables sends `search` as an array (search[value]); that is handled by the
        // datatable engine, so only the page's own text filter is read here.
        $search = $this->filterSearchTerm($request);
        $sort = (string) $request->input('sort', 'name_asc');
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 10;
        }
        $export = (string) $request->input('export', '');
        $sortMap = [
            'name_asc' => ['sm.display_name', 'asc'],
            'name_desc' => ['sm.display_name', 'desc'],
            'ot_code_asc' => ['sm.generated_OT_code', 'asc'],
            'ot_code_desc' => ['sm.generated_OT_code', 'desc'],
        ];
        [$sortColumn, $sortDirection] = $sortMap[$sort] ?? $sortMap['name_asc'];

        if ($selectedCourseId <= 0 && $activeCourses->isNotEmpty()) {
            $selectedCourseId = (int) $activeCourses->first()->pk;
        }

        $students = new LengthAwarePaginator([], 0, $perPage);
        if ($selectedCourseId > 0) {
            $studentsQuery = StudentMasterCourseMap::query()
                ->join('student_master as sm', 'student_master_course__map.student_master_pk', '=', 'sm.pk')
                ->join('course_master as cm', 'student_master_course__map.course_master_pk', '=', 'cm.pk')
                ->leftJoin('cadre_master as cad', 'sm.cadre_master_pk', '=', 'cad.pk')
                ->where('student_master_course__map.active_inactive', 1)
                ->where('sm.status', 1)
                ->where('cm.active_inactive', 1)
                ->where('cm.pk', $selectedCourseId)
                ->select([
                    'sm.pk',
                    'sm.display_name',
                    'sm.generated_OT_code',
                    'sm.email',
                    'sm.photo_path',
                    'cm.course_name',
                    'cad.cadre_name',
                ]);

            if ($search !== '') {
                $studentsQuery->where(function ($query) use ($search) {
                    $query->where('sm.display_name', 'like', "%{$search}%")
                        ->orWhere('sm.generated_OT_code', 'like', "%{$search}%")
                        ->orWhere('sm.email', 'like', "%{$search}%")
                        ->orWhere('cad.cadre_name', 'like', "%{$search}%");
                });
            }

            $studentsQuery = $studentsQuery->orderBy($sortColumn, $sortDirection);

            if (in_array($export, ['csv', 'excel'], true)) {
                return $this->streamOtExport($studentsQuery->cursor(), $export);
            }

            if ($request->ajax()) {
                return $this->otDatatable($studentsQuery);
            }
        } elseif ($request->ajax()) {
            return \Yajra\DataTables\Facades\DataTables::of(collect([]))->make(true);
        }

        return view('admin.directory.ot', compact('activeCourses', 'selectedCourseId', 'search', 'sort', 'perPage'));
    }

    /**
     * Server-side feed for the OT directory grid (search/sort/paging happen in SQL).
     */
    protected function otDatatable($query)
    {
        return \Yajra\DataTables\Facades\DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('display_name', fn ($row) => e($row->display_name ?: '-'))
            ->editColumn('generated_OT_code', fn ($row) => e($row->generated_OT_code ?: '-'))
            ->editColumn('email', fn ($row) => e($row->email ?: '-'))
            ->editColumn('course_name', fn ($row) => e($row->course_name ?: '-'))
            ->editColumn('cadre_name', fn ($row) => e($row->cadre_name ?: '-'))
            ->addColumn('room_no', fn () => '-')
            ->addColumn('room_extension_no', fn () => '-')
            ->addColumn('photo', function ($row) {
                $src = ! empty($row->photo_path)
                    ? asset('storage/'.$row->photo_path)
                    : asset('images/dummypic.jpeg');

                return '<img src="'.e($src).'" alt="photo" class="directory-photo" loading="lazy" decoding="async">';
            })
            ->rawColumns(['photo'])
            ->make(true);
    }

    private function streamEmployeesExport(iterable $rows, string $export): StreamedResponse
    {
        $isExcel = $export === 'excel';
        $filename = $isExcel ? 'lbsnaa-directory.xls' : 'lbsnaa-directory.csv';
        $delimiter = $isExcel ? "\t" : ',';

        return response()->streamDownload(function () use ($rows, $delimiter) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['S.No.', 'Name', 'Designation', 'Section', 'Address', 'Office Extension', 'Mobile', 'Residence', 'Email'], $delimiter);
            $index = 1;
            foreach ($rows as $row) {
                $name = trim(($row->first_name ?? '') . ' ' . ($row->middle_name ?? '') . ' ' . ($row->last_name ?? ''));
                $email = $row->officalemail ?: $row->email;
                fputcsv($output, [
                    $index++,
                    $name ?: '-',
                    $row->designation_name ?: '-',
                    $row->department_name ?: '-',
                    $row->current_address ?: '-',
                    $row->office_extension_no ?: '-',
                    $row->mobile ?: '-',
                    $row->residence_no ?: '-',
                    $email ?: '-',
                ], $delimiter);
            }
            fclose($output);
        }, $filename);
    }

    private function streamOtExport(iterable $rows, string $export): StreamedResponse
    {
        $isExcel = $export === 'excel';
        $filename = $isExcel ? 'ot-directory.xls' : 'ot-directory.csv';
        $delimiter = $isExcel ? "\t" : ',';

        return response()->streamDownload(function () use ($rows, $delimiter) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['S.No.', 'Name', 'OT Code', 'Room No.', 'Room Extension No.', 'Email', 'Course', 'Cadre'], $delimiter);
            $index = 1;
            foreach ($rows as $row) {
                fputcsv($output, [
                    $index++,
                    $row->display_name ?: '-',
                    $row->generated_OT_code ?: '-',
                    '-',
                    '-',
                    $row->email ?: '-',
                    $row->course_name ?: '-',
                    $row->cadre_name ?: '-',
                ], $delimiter);
            }
            fclose($output);
        }, $filename);
    }
}

