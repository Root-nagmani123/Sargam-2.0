<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\EmployeeMaster;
use App\Models\StudentMasterCourseMap;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class DirectoryController extends Controller
{
    public function lbsnaa(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
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

        $employees = $employeesQuery->get();

        return view('admin.directory.lbsnaa', compact('employees'));
    }

    public function ot(Request $request)
    {
        // Active = ongoing/upcoming courses, Archived = courses that have ended.
        $status = (string) $request->input('status', 'active');
        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }
        $today = now()->toDateString();

        $coursesQuery = CourseMaster::query()->where('active_inactive', 1);
        if ($status === 'archived') {
            $coursesQuery->where('end_date', '<', $today);
        } else {
            $coursesQuery->where('end_date', '>=', $today);
        }
        $activeCourses = $coursesQuery
            ->orderByDesc('end_date')
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'couse_short_name']);

        $selectedCourseId = (int) $request->input('course_id', 0);
        $search = trim((string) $request->input('search', ''));

        if ($selectedCourseId <= 0 && $activeCourses->isNotEmpty()) {
            $selectedCourseId = (int) $activeCourses->first()->pk;
        }

        $students = collect();
        if ($selectedCourseId > 0) {
            $studentsQuery = StudentMasterCourseMap::query()
                ->join('student_master as sm', 'student_master_course__map.student_master_pk', '=', 'sm.pk')
                ->join('course_master as cm', 'student_master_course__map.course_master_pk', '=', 'cm.pk')
                ->leftJoin('cadre_master as cad', 'sm.cadre_master_pk', '=', 'cad.pk')
                ->leftJoin('ot_hostel_room_details as ohrd', function ($join) {
                    $join->on('ohrd.user_name', '=', 'sm.user_id')
                         ->on('ohrd.course_master_pk', '=', 'cm.pk')
                         ->where('ohrd.active_inactive', 1);
                })
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
                    'ohrd.hostel_room_name',
                ]);

            if ($search !== '') {
                $studentsQuery->where(function ($query) use ($search) {
                    $query->where('sm.display_name', 'like', "%{$search}%")
                        ->orWhere('sm.generated_OT_code', 'like', "%{$search}%")
                        ->orWhere('sm.email', 'like', "%{$search}%")
                        ->orWhere('cad.cadre_name', 'like', "%{$search}%");
                });
            }

            $students = $studentsQuery->orderBy('sm.display_name')->get();
        }

        return view('admin.directory.ot', compact('students', 'activeCourses', 'selectedCourseId', 'search', 'status'));
    }

    public function otData(Request $request)
    {
        $status = (string) $request->input('status', 'active');
        $courseId = (int) $request->input('course_id', 0);

        $today = now()->toDateString();

        // If no course ID provided, get the first active course
        if ($courseId <= 0) {
            $coursesQuery = CourseMaster::query()->where('active_inactive', 1);
            if ($status === 'archived') {
                $coursesQuery->where('end_date', '<', $today);
            } else {
                $coursesQuery->where('end_date', '>=', $today);
            }
            $firstCourse = $coursesQuery
                ->orderByDesc('end_date')
                ->orderBy('course_name')
                ->first(['pk']);
            
            if ($firstCourse) {
                $courseId = (int) $firstCourse->pk;
            }
        }

        $query = StudentMasterCourseMap::query()
            ->join('student_master as sm', 'student_master_course__map.student_master_pk', '=', 'sm.pk')
            ->join('course_master as cm', 'student_master_course__map.course_master_pk', '=', 'cm.pk')
            ->leftJoin('cadre_master as cad', 'sm.cadre_master_pk', '=', 'cad.pk')
            ->where('student_master_course__map.active_inactive', 1)
            ->where('sm.status', 1)
            ->where('cm.active_inactive', 1);

        // Filter by course
        if ($courseId > 0) {
            $query->where('cm.pk', $courseId);
        }

        // Filter by status
        if ($status === 'archived') {
            $query->where('cm.end_date', '<', $today);
        } else {
            $query->where('cm.end_date', '>=', $today);
        }

        $query->select([
            'sm.pk',
            'sm.display_name',
            'sm.generated_OT_code',
            'sm.email',
            'sm.photo_path',
            'cm.course_name',
            'cad.cadre_name',
        ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('display_name', function ($row) {
                return $row->display_name ?: '-';
            })
            ->editColumn('generated_OT_code', function ($row) {
                return $row->generated_OT_code ?: '-';
            })
            ->editColumn('email', function ($row) {
                return $row->email ?: '-';
            })
            ->editColumn('course_name', function ($row) {
                return $row->course_name ?: '-';
            })
            ->editColumn('cadre_name', function ($row) {
                return $row->cadre_name ?: '-';
            })
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

