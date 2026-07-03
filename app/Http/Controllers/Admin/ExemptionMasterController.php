<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\ExemptionMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ExemptionMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // if (! hasRole('Super Admin') && ! hasRole('Training IST')) {
            //     abort(403, 'You are not authorized to access PT Exemption Master.');
            // }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        return view('admin.exemption_master.index', [
            'coursesActive' => $this->getConfiguredCourses('active'),
            'coursesArchive' => $this->getConfiguredCourses('archive'),
        ]);
    }

    public function create(Request $request)
    {
        $courseMasterPk = $request->query('course_master_pk');
        $effectiveFrom = $request->query('effective_from');
        $isEditing = $courseMasterPk && $effectiveFrom;

        // Only courses with an *active* exemption should be blocked from being
        // configured again. Inactive rows must not exclude a course from the
        // dropdown (delete already frees it up; inactive should behave the same).
        $configuredCourseIds = ExemptionMaster::query()
            ->where('active_inactive', 1)
            ->when($isEditing, fn ($q) => $q->where('course_master_pk', '!=', $courseMasterPk))
            ->distinct()
            ->pluck('course_master_pk')
            ->all();

        $courses = $this->getCourses()->reject(
            fn ($course) => in_array($course->pk, $configuredCourseIds, true)
        );

        if ($isEditing && $courses->doesntContain('pk', (int) $courseMasterPk)) {
            $editingCourse = CourseMaster::find($courseMasterPk);
            if ($editingCourse) {
                $courses->prepend($editingCourse);
            }
        }

        $maleRecord = null;
        $femaleRecord = null;

        if ($isEditing) {
            $records = ExemptionMaster::where('course_master_pk', $courseMasterPk)
                ->whereDate('effective_from', $effectiveFrom)
                ->get()
                ->keyBy('gender');

            $maleRecord = $records->get('Male');
            $femaleRecord = $records->get('Female');
        }

        return view('admin.exemption_master.create', compact(
            'courses',
            'courseMasterPk',
            'effectiveFrom',
            'maleRecord',
            'femaleRecord',
            'isEditing'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_master_pk' => 'required|exists:course_master,pk',
            'effective_from' => 'required|date',
            'apply_cutoff_time' => 'required|date_format:H:i',
            'male_exemption_days' => 'required|numeric|min:0|max:999.9',
            'female_exemption_days' => 'required|numeric|min:0|max:999.9',
        ]);

        $this->assertCourseAllowed((int) $validated['course_master_pk']);

        if ($this->courseHasConflictingExemption(
            (int) $validated['course_master_pk'],
            $validated['effective_from']
        )) {
            return back()->withInput()->withErrors([
                'course_master_pk' => 'PT exemption is already configured for this course. Please edit the existing record.',
            ]);
        }

        $user = Auth::user();
        $now = now();

        DB::transaction(function () use ($validated, $user, $now) {
            $this->saveExemptionRow(
                (int) $validated['course_master_pk'],
                $validated['effective_from'],
                'Male',
                (float) $validated['male_exemption_days'],
                $validated['apply_cutoff_time'],
                $user->pk ?? null,
                $now
            );

            $this->saveExemptionRow(
                (int) $validated['course_master_pk'],
                $validated['effective_from'],
                'Female',
                (float) $validated['female_exemption_days'],
                $validated['apply_cutoff_time'],
                $user->pk ?? null,
                $now
            );
        });

        return redirect()
            ->route('admin.pt-exemption-master.index')
            ->with('success', 'PT exemption count saved successfully.');
    }

    protected function courseHasConflictingExemption(int $courseMasterPk, string $effectiveFrom): bool
    {
        // Inactive configurations do not block re-configuring a course, so only
        // active rows are considered when detecting a conflict.
        $existingForCourse = ExemptionMaster::where('course_master_pk', $courseMasterPk)
            ->where('active_inactive', 1)
            ->exists();

        if (! $existingForCourse) {
            return false;
        }

        return ! ExemptionMaster::where('course_master_pk', $courseMasterPk)
            ->where('active_inactive', 1)
            ->whereDate('effective_from', $effectiveFrom)
            ->exists();
    }

    protected function saveExemptionRow(
        int $courseMasterPk,
        string $effectiveFrom,
        string $gender,
        float $exemptionDays,
        string $applyCutoffTime,
        ?int $createdBy,
        $now
    ): void {
        $record = ExemptionMaster::where('course_master_pk', $courseMasterPk)
            ->whereDate('effective_from', $effectiveFrom)
            ->where('gender', $gender)
            ->first();

        if ($record) {
            $record->update([
                'exemption_days' => $exemptionDays,
                'apply_cutoff_time' => $applyCutoffTime,
                'active_inactive' => 1,
                'modified_date' => $now,
            ]);

            return;
        }

        ExemptionMaster::create([
            'course_master_pk' => $courseMasterPk,
            'effective_from' => $effectiveFrom,
            'gender' => $gender,
            'exemption_days' => $exemptionDays,
            'apply_cutoff_time' => $applyCutoffTime,
            'active_inactive' => 1,
            'created_by' => $createdBy,
            'created_date' => $now,
            'modified_date' => $now,
        ]);
    }

    public function status(Request $request, $id)
    {
        $record = ExemptionMaster::findOrFail($id);

        $this->assertCourseAllowed((int) $record->course_master_pk);

        $request->validate([
            'active_inactive' => 'required|in:0,1',
        ]);

        // A PT exemption is configured as a Male + Female pair for the same course
        // and effective-from date. Toggling one gender must toggle its sibling too.
        ExemptionMaster::where('course_master_pk', $record->course_master_pk)
            ->whereDate('effective_from', $record->effective_from)
            ->update([
                'active_inactive' => (int) $request->active_inactive,
                'modified_date' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $record = ExemptionMaster::findOrFail($id);

        $this->assertCourseAllowed((int) $record->course_master_pk);

        if ((int) $record->active_inactive === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Only inactive records can be deleted.',
            ], 422);
        }

        // Delete the full Male + Female pair for this course and effective-from date,
        // so both genders are removed together.
        ExemptionMaster::where('course_master_pk', $record->course_master_pk)
            ->whereDate('effective_from', $record->effective_from)
            ->where('active_inactive', 0)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Record deleted successfully.',
        ]);
    }

    protected function datatable(Request $request)
    {
        if ($request->filled('pk') && $request->filled('active_inactive') && (int) $request->active_inactive !== 2) {
            $record = ExemptionMaster::find($request->pk);
            if ($record) {
                $this->assertCourseAllowed((int) $record->course_master_pk);
                // Keep the Male + Female pair (same course + effective-from) in sync.
                ExemptionMaster::where('course_master_pk', $record->course_master_pk)
                    ->whereDate('effective_from', $record->effective_from)
                    ->update([
                        'active_inactive' => (int) $request->active_inactive,
                        'modified_date' => now(),
                    ]);
            }
        }

        $query = $this->baseListQuery($request);

        return DataTables::of($query)
            ->addIndexColumn()
            ->filter(function ($query) use ($request) {
                if (! empty($request->search['value'])) {
                    $search = $request->search['value'];
                    $query->where(function ($q) use ($search) {
                        $q->where('gender', 'like', "%{$search}%")
                            ->orWhereHas('course', function ($qc) use ($search) {
                                $qc->where('course_name', 'like', "%{$search}%");
                            });
                    });
                }
            })
            ->addColumn('course_name', function ($row) {
                return $row->course->course_name ?? 'N/A';
            })
            ->addColumn('effective_from_display', function ($row) {
                return $row->effective_from ? $row->effective_from->format('d-m-Y') : 'N/A';
            })
            ->addColumn('apply_cutoff_time_display', function ($row) {
                if (blank($row->apply_cutoff_time)) {
                    return 'N/A';
                }

                return \Carbon\Carbon::parse($row->apply_cutoff_time)->format('h:i A');
            })
            ->addColumn('exemption_days_display', function ($row) {
                return number_format((float) $row->exemption_days, 1) . ' Days';
            })
            ->addColumn('status', function ($row) {
                if ((int) $row->active_inactive === 1) {
                    return '<span class="badge rounded-1 programme-status-badge programme-status-badge--active">Active</span>';
                }

                return '<span class="badge rounded-1 programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $url = route('admin.pt-exemption-master.create', [
                    'course_master_pk' => $row->course_master_pk,
                    'effective_from' => $row->effective_from?->format('Y-m-d'),
                ]);

                $checked = (int) $row->active_inactive === 1 ? 'checked' : '';

                $editBtn = '<a href="' . $url . '" class="programme-action-btn" aria-label="Edit" title="Edit">'
                    . '<i class="bi bi-pencil" aria-hidden="true"></i></a>';

                $toggle = '<div class="form-check form-switch programme-action-switch mb-0">'
                    . '<input class="form-check-input plain-status-toggle exemption-status-toggle" type="checkbox" role="switch" '
                    . 'data-id="' . $row->pk . '" ' . $checked . '></div>';

                $deleteBtn = '';
                if ((int) $row->active_inactive === 0) {
                    $deleteBtn = '<a href="javascript:void(0)" class="programme-action-btn programme-action-btn--danger exemption-delete-btn" '
                        . 'data-id="' . $row->pk . '" aria-label="Delete" title="Delete">'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i></a>';
                }

                return '<div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">'
                    . $editBtn . $toggle . $deleteBtn . '</div>';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    protected function baseListQuery(Request $request)
    {
        $query = ExemptionMaster::with('course')->orderByDesc('pk');

        $courseIds = $this->getAllowedCourseIds();
        if ($courseIds !== null) {
            $query->whereIn('course_master_pk', $courseIds);
        }

        $statusFilter = (string) $request->input('status_filter', 'active');
        $today = now()->toDateString();
        if ($statusFilter === 'archive') {
            // Archived = the course has already ended (expired).
            $query->whereHas('course', fn ($q) => $q->whereDate('end_date', '<', $today));
        } elseif ($statusFilter === 'active') {
            // Active = the course is still current / upcoming.
            $query->whereHas('course', fn ($q) => $q->whereDate('end_date', '>=', $today));
        }

        if ($request->filled('course_filter')) {
            $query->where('course_master_pk', (int) $request->input('course_filter'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('effective_from', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('effective_from', '<=', $request->input('to_date'));
        }

        return $query;
    }

    /**
     * Courses that have at least one PT exemption configured, scoped to the given tab status
     * by course lifecycle. $status: 'active' = course still current/upcoming,
     * 'archive' = course already ended, null = any.
     */
    protected function getConfiguredCourses(?string $status = null)
    {
        $courseIds = $this->getAllowedCourseIds();

        $configuredIds = ExemptionMaster::query()
            ->when($courseIds !== null, fn ($q) => $q->whereIn('course_master_pk', $courseIds))
            ->distinct()
            ->pluck('course_master_pk')
            ->all();

        if (empty($configuredIds)) {
            return collect();
        }

        $today = now()->toDateString();

        return CourseMaster::whereIn('pk', $configuredIds)
            ->when($status === 'active', fn ($q) => $q->whereDate('end_date', '>=', $today))
            ->when($status === 'archive', fn ($q) => $q->whereDate('end_date', '<', $today))
            ->orderBy('course_name')
            ->pluck('course_name', 'pk');
    }

    public function export(Request $request)
    {
        $query = $this->baseListQuery($request);

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('gender', 'like', "%{$search}%")
                    ->orWhereHas('course', function ($qc) use ($search) {
                        $qc->where('course_name', 'like', "%{$search}%");
                    });
            });
        }

        $rows = $query->get();

        $columns = ['S. No.', 'Course', 'Effective From', 'PT Timing', 'Gender', 'PT Exemption Count (Days)', 'Status'];
        $filename = 'PT_Exemption_Master_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            $serial = 1;
            foreach ($rows as $row) {
                fputcsv($out, [
                    $serial++,
                    $row->course->course_name ?? 'N/A',
                    $row->effective_from ? $row->effective_from->format('d-m-Y') : 'N/A',
                    blank($row->apply_cutoff_time) ? 'N/A' : \Carbon\Carbon::parse($row->apply_cutoff_time)->format('h:i A'),
                    $row->gender,
                    number_format((float) $row->exemption_days, 1) . ' Days',
                    (int) $row->active_inactive === 1 ? 'Active' : 'Inactive',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function getCourses()
    {
        $courseIds = $this->getAllowedCourseIds();

        $query = CourseMaster::query()
            ->where('active_inactive', 1)
            ->where('end_date', '>', now())
            ->orderBy('course_name');

        if ($courseIds !== null) {
            $query->whereIn('pk', $courseIds);
        }

        return $query->get();
    }

    /**
     * @return array<int>|null null = all courses (training authority / Super Admin)
     */
    protected function getAllowedCourseIds(): ?array
    {
        if (isTrainingOrEstateAuthority()) {
            return null;
        }

        $courseIds = get_Role_by_course();

        if (empty($courseIds) || $courseIds === [-1]) {
            return [-1];
        }

        return $courseIds;
    }

    protected function assertCourseAllowed(int $courseMasterPk): void
    {
        $courseIds = $this->getAllowedCourseIds();

        if ($courseIds !== null && ! in_array($courseMasterPk, $courseIds, true)) {
            abort(403, 'You are not authorized for this course.');
        }
    }
}
