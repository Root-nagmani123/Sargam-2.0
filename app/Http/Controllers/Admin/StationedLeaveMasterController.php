<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\FacultyMaster;
use App\Models\StationedLeaveFacultyApprover;
use App\Models\StationedLeaveMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StationedLeaveMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // if (! hasRole('Super Admin') && ! hasRole('Training IST')) {
            //     abort(403, 'You are not authorized to access Stationed Leave Master.');
            // }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        $statusCounts = $this->getStatusCounts();

        return view('admin.stationed_leave_master.index', [
            'activeCourses' => $this->getConfiguredCoursesByStatus('active'),
            'archiveCourses' => $this->getConfiguredCoursesByStatus('archive'),
            'activeCount' => $statusCounts['active'],
            'archiveCount' => $statusCounts['archive'],
        ]);
    }

    public function create(Request $request)
    {
        $faculties = $this->getFacultyOptions();
        $config = null;
        $approvers = collect();

        $courseMasterPk = $request->query('course_master_pk');
        $effectiveFrom = $request->query('effective_from');
        $isEditing = $courseMasterPk && $effectiveFrom;

        // Only courses with an *active* stationed-leave configuration should be
        // blocked from being configured again. Inactive rows must not exclude a
        // course from the dropdown (delete already frees it up; inactive behaves
        // the same). This mirrors the PT Exemption Master behaviour.
        $configuredCourseIds = StationedLeaveMaster::query()
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

        if ($isEditing) {
            $config = StationedLeaveMaster::with(['approvers.faculty'])
                ->where('course_master_pk', $courseMasterPk)
                ->whereDate('effective_from', $effectiveFrom)
                ->first();

            if ($config) {
                $this->assertCourseAllowed((int) $config->course_master_pk);
                $approvers = $config->approvers;
            }
        }

        return view('admin.stationed_leave_master.create', compact(
            'courses',
            'faculties',
            'config',
            'approvers',
            'courseMasterPk',
            'effectiveFrom',
            'isEditing'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_master_pk' => 'required|exists:course_master,pk',
            'effective_from' => 'required|date',
            'apply_cutoff_time' => 'required|date_format:H:i',
            'is_faculty_approval_required' => 'nullable|in:1',
            'faculty_rows' => 'nullable|array',
            'faculty_rows.*.faculty_master_pk' => 'required|exists:faculty_master,pk',
            'faculty_rows.*.is_approval_authority' => 'nullable|in:0,1',
        ], [
            'faculty_rows.required' => 'Please add at least one faculty when approval is required.',
            'faculty_rows.min' => 'Please add at least one faculty when approval is required.',
        ]);

        $this->assertCourseAllowed((int) $validated['course_master_pk']);

        if ($this->courseHasConflictingConfig(
            (int) $validated['course_master_pk'],
            $validated['effective_from']
        )) {
            return back()->withInput()->withErrors([
                'course_master_pk' => 'Stationed leave is already configured for this course. Please edit the existing record.',
            ]);
        }

        $approvalRequired = $request->has('is_faculty_approval_required') ? 1 : 0;
        $facultyRows = collect($validated['faculty_rows'] ?? [])
            ->unique('faculty_master_pk')
            ->values();

        if ($approvalRequired === 1) {
            if ($facultyRows->isEmpty()) {
                return back()->withInput()->withErrors([
                    'faculty_rows' => 'Please add at least one faculty when approval is required.',
                ]);
            }

            $hasAuthority = $facultyRows->contains(function ($row) {
                return (int) ($row['is_approval_authority'] ?? 0) === 1;
            });

            if (! $hasAuthority) {
                return back()->withInput()->withErrors([
                    'faculty_rows' => 'Please mark at least one faculty as approval authority.',
                ]);
            }
        } else {
            $facultyRows = collect();
        }

        $user = Auth::user();
        $now = now();

        DB::transaction(function () use ($validated, $approvalRequired, $facultyRows, $user, $now) {
            $config = StationedLeaveMaster::where('course_master_pk', $validated['course_master_pk'])
                ->whereDate('effective_from', $validated['effective_from'])
                ->first();

            if ($config) {
                $config->update([
                    'is_faculty_approval_required' => $approvalRequired,
                    'apply_cutoff_time' => $validated['apply_cutoff_time'],
                    'active_inactive' => 1,
                    'modified_date' => $now,
                ]);
            } else {
                $config = StationedLeaveMaster::create([
                    'course_master_pk' => $validated['course_master_pk'],
                    'effective_from' => $validated['effective_from'],
                    'apply_cutoff_time' => $validated['apply_cutoff_time'],
                    'is_faculty_approval_required' => $approvalRequired,
                    'active_inactive' => 1,
                    'created_by' => $user->pk ?? null,
                    'created_date' => $now,
                    'modified_date' => $now,
                ]);
            }

            StationedLeaveFacultyApprover::where('stationed_leave_master_pk', $config->pk)->delete();

            foreach ($facultyRows as $row) {
                StationedLeaveFacultyApprover::create([
                    'stationed_leave_master_pk' => $config->pk,
                    'faculty_master_pk' => $row['faculty_master_pk'],
                    'is_approval_authority' => (int) ($row['is_approval_authority'] ?? 0),
                    'created_date' => $now,
                    'modified_date' => $now,
                ]);
            }
        });

        return redirect()
            ->route('admin.stationed-leave-master.index')
            ->with('success', 'Stationed leave configuration saved successfully.');
    }

    /**
     * A course may only have one active stationed-leave configuration. An active
     * config for a *different* effective-from date blocks creating a new one, so
     * the user must edit the existing record instead. Inactive configs do not
     * block re-configuration. Mirrors the PT Exemption Master rule.
     */
    protected function courseHasConflictingConfig(int $courseMasterPk, string $effectiveFrom): bool
    {
        $existingForCourse = StationedLeaveMaster::where('course_master_pk', $courseMasterPk)
            ->where('active_inactive', 1)
            ->exists();

        if (! $existingForCourse) {
            return false;
        }

        return ! StationedLeaveMaster::where('course_master_pk', $courseMasterPk)
            ->where('active_inactive', 1)
            ->whereDate('effective_from', $effectiveFrom)
            ->exists();
    }

    public function status(Request $request, $id)
    {
        $record = StationedLeaveMaster::findOrFail($id);
        $this->assertCourseAllowed((int) $record->course_master_pk);

        $request->validate([
            'active_inactive' => 'required|in:0,1',
        ]);

        $record->update([
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
        $record = StationedLeaveMaster::findOrFail($id);
        $this->assertCourseAllowed((int) $record->course_master_pk);

        if ((int) $record->active_inactive === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Only inactive records can be deleted.',
            ], 422);
        }

        DB::transaction(function () use ($record) {
            StationedLeaveFacultyApprover::where('stationed_leave_master_pk', $record->pk)->delete();
            $record->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Record deleted successfully.',
        ]);
    }

    public function faculties(Request $request)
    {
        $search = trim((string) $request->get('q', ''));

        $query = FacultyMaster::query()
            ->where('active_inactive', 1)
            ->where('faculty_type', 1) // Internal faculty only
            ->orderBy('full_name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email_id', 'like', "%{$search}%")
                    ->orWhere('current_designation', 'like', "%{$search}%");
            });
        }

        $faculties = $query->limit(50)->get()->map(function ($faculty) {
            $name = trim($faculty->full_name ?: implode(' ', array_filter([
                $faculty->first_name,
                $faculty->middle_name,
                $faculty->last_name,
            ])));

            return [
                'pk' => $faculty->pk,
                'name' => $name ?: 'N/A',
                'designation' => $faculty->current_designation ?? 'N/A',
                'email' => $faculty->email_id ?? 'N/A',
            ];
        });

        return response()->json(['data' => $faculties]);
    }

    protected function baseListQuery(Request $request)
    {
        $statusFilter = strtolower((string) $request->input('status_filter', 'active'));

        $query = StationedLeaveMaster::with(['course', 'approvers'])
            ->withCount('approvers')
            ->orderByDesc('pk');

        // Tabs are driven by the linked course's status: Active shows configs of
        // running courses, Archived shows configs of ended/inactive courses.
        $this->applyCourseStatusFilter($query, $statusFilter);

        $courseIds = $this->getAllowedCourseIds();
        if ($courseIds !== null) {
            $query->whereIn('course_master_pk', $courseIds);
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

    protected function datatable(Request $request)
    {
        return DataTables::of($this->baseListQuery($request))
            ->addIndexColumn()
            ->filter(function ($query) use ($request) {
                if (! empty($request->search['value'])) {
                    $search = $request->search['value'];
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('course', function ($qc) use ($search) {
                            $qc->where('course_name', 'like', "%{$search}%");
                        });
                    });
                }
            })
            ->addColumn('course_name', fn ($row) => $row->course->course_name ?? 'N/A')
            ->addColumn('effective_from_display', fn ($row) => $row->effective_from?->format('d-m-Y') ?? 'N/A')
            ->addColumn('apply_cutoff_time_display', function ($row) {
                if (blank($row->apply_cutoff_time)) {
                    return 'N/A';
                }

                return \Carbon\Carbon::parse($row->apply_cutoff_time)->format('h:i A');
            })
            ->addColumn('approval_required_display', fn ($row) => (int) $row->is_faculty_approval_required === 1 ? 'Yes' : 'No')
            ->addColumn('faculty_count_display', fn ($row) => (int) ($row->approvers_count ?? 0))
            ->addColumn('status', function ($row) {
                if ((int) $row->active_inactive === 1) {
                    return '<span class="badge rounded-pill programme-status-badge programme-status-badge--active">Active</span>';
                }

                return '<span class="badge rounded-pill programme-status-badge programme-status-badge--inactive">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $url = route('admin.stationed-leave-master.create', [
                    'course_master_pk' => $row->course_master_pk,
                    'effective_from' => $row->effective_from?->format('Y-m-d'),
                ]);

                $checked = (int) $row->active_inactive === 1 ? 'checked' : '';

                $editBtn = '<a href="' . $url . '" class="programme-action-btn" aria-label="Edit" title="Edit">'
                    . '<i class="bi bi-pencil" aria-hidden="true"></i></a>';

                $toggle = '<div class="form-check form-switch programme-action-switch mb-0">'
                    . '<input class="form-check-input plain-status-toggle stationed-leave-status-toggle" type="checkbox" role="switch" '
                    . 'data-id="' . $row->pk . '" ' . $checked . '></div>';

                $deleteBtn = '';
                if ((int) $row->active_inactive === 0) {
                    $deleteBtn = '<a href="javascript:void(0)" class="programme-action-btn programme-action-btn--danger stationed-leave-delete-btn" '
                        . 'data-id="' . $row->pk . '" aria-label="Delete" title="Delete">'
                        . '<i class="bi bi-trash3" aria-hidden="true"></i></a>';
                }

                return '<div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">'
                    . $editBtn . $toggle . $deleteBtn . '</div>';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function export(Request $request)
    {
        $rows = $this->baseListQuery($request)->get();

        $columns = ['S. No.', 'Course', 'Effective From', 'PT Timing', 'Approval Required', 'Faculty Count', 'Status'];
        $filename = 'Stationed_Leave_Master_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            $serial = 1;
            foreach ($rows as $row) {
                fputcsv($out, [
                    $serial++,
                    $row->course->course_name ?? 'N/A',
                    $row->effective_from?->format('d-m-Y') ?? 'N/A',
                    blank($row->apply_cutoff_time) ? 'N/A' : \Carbon\Carbon::parse($row->apply_cutoff_time)->format('h:i A'),
                    (int) $row->is_faculty_approval_required === 1 ? 'Yes' : 'No',
                    (int) ($row->approvers_count ?? 0),
                    (int) $row->active_inactive === 1 ? 'Active' : 'Inactive',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Constrain a StationedLeaveMaster query to configs whose linked course is
     * active (running) or archived (inactive / past its end date).
     */
    protected function applyCourseStatusFilter($query, string $statusFilter)
    {
        $today = now()->toDateString();

        if ($statusFilter === 'archive') {
            $query->whereHas('course', function ($q) use ($today) {
                $q->where('active_inactive', 0)
                    ->orWhereDate('end_date', '<', $today);
            });
        } else {
            $query->whereHas('course', function ($q) use ($today) {
                $q->where('active_inactive', 1)
                    ->where(function ($q2) use ($today) {
                        $q2->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $today);
                    });
            });
        }

        return $query;
    }

    /**
     * Courses that have at least one stationed-leave configuration, filtered by
     * the course's active/archive status.
     */
    protected function getConfiguredCoursesByStatus(string $statusFilter)
    {
        $courseIds = $this->getAllowedCourseIds();

        $configuredIds = StationedLeaveMaster::query()
            ->when($courseIds !== null, fn ($q) => $q->whereIn('course_master_pk', $courseIds))
            ->distinct()
            ->pluck('course_master_pk')
            ->all();

        if (empty($configuredIds)) {
            return collect();
        }

        $today = now()->toDateString();

        return CourseMaster::whereIn('pk', $configuredIds)
            ->when($statusFilter === 'archive', function ($q) use ($today) {
                $q->where(function ($q2) use ($today) {
                    $q2->where('active_inactive', 0)
                        ->orWhereDate('end_date', '<', $today);
                });
            }, function ($q) use ($today) {
                $q->where('active_inactive', 1)
                    ->where(function ($q2) use ($today) {
                        $q2->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $today);
                    });
            })
            ->orderBy('course_name')
            ->pluck('course_name', 'pk');
    }

    protected function getStatusCounts(): array
    {
        $courseIds = $this->getAllowedCourseIds();

        $base = fn () => StationedLeaveMaster::query()
            ->when($courseIds !== null, fn ($q) => $q->whereIn('course_master_pk', $courseIds));

        return [
            'active' => $this->applyCourseStatusFilter($base(), 'active')->count(),
            'archive' => $this->applyCourseStatusFilter($base(), 'archive')->count(),
        ];
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

    protected function getFacultyOptions()
    {
        return FacultyMaster::query()
            ->where('active_inactive', 1)
            ->where('faculty_type', 1) // Internal faculty only
            ->orderBy('full_name')
            ->get()
            ->map(function ($faculty) {
                $name = trim($faculty->full_name ?: implode(' ', array_filter([
                    $faculty->first_name,
                    $faculty->middle_name,
                    $faculty->last_name,
                ])));

                return [
                    'pk' => $faculty->pk,
                    'name' => $name ?: 'N/A',
                    'designation' => $faculty->current_designation ?? 'N/A',
                    'email' => $faculty->email_id ?? 'N/A',
                ];
            });
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
