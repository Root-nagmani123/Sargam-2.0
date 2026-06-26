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
            if (! hasRole('Super Admin') && ! hasRole('Training IST')) {
                abort(403, 'You are not authorized to access Stationed Leave Master.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        return view('admin.stationed_leave_master.index');
    }

    public function create(Request $request)
    {
        $courses = $this->getCourses();
        $faculties = $this->getFacultyOptions();
        $config = null;
        $approvers = collect();

        $courseMasterPk = $request->query('course_master_pk');
        $effectiveFrom = $request->query('effective_from');

        if ($courseMasterPk && $effectiveFrom) {
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
            'effectiveFrom'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_master_pk' => 'required|exists:course_master,pk',
            'effective_from' => 'required|date',
            'apply_cutoff_time' => 'required|date_format:H:i',
            'is_faculty_approval_required' => 'required|accepted',
            'faculty_rows' => 'required|array|min:1',
            'faculty_rows.*.faculty_master_pk' => 'required|exists:faculty_master,pk',
            'faculty_rows.*.is_approval_authority' => 'nullable|in:0,1',
        ], [
            'is_faculty_approval_required.required' => 'Please check "Require approval from faculty" to continue.',
            'is_faculty_approval_required.accepted' => 'Please check "Require approval from faculty" to continue.',
            'faculty_rows.required' => 'Please add at least one faculty when approval is required.',
            'faculty_rows.min' => 'Please add at least one faculty when approval is required.',
        ]);

        $this->assertCourseAllowed((int) $validated['course_master_pk']);

        $approvalRequired = 1;
        $facultyRows = collect($validated['faculty_rows'])
            ->unique('faculty_master_pk')
            ->values();

        $hasAuthority = $facultyRows->contains(function ($row) {
            return (int) ($row['is_approval_authority'] ?? 0) === 1;
        });

        if (! $hasAuthority) {
            return back()->withInput()->withErrors([
                'faculty_rows' => 'Please mark at least one faculty as approval authority.',
            ]);
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

    protected function datatable(Request $request)
    {
        $query = StationedLeaveMaster::with(['course', 'approvers'])
            ->withCount('approvers')
            ->orderByDesc('pk');

        $courseIds = $this->getAllowedCourseIds();
        if ($courseIds !== null) {
            $query->whereIn('course_master_pk', $courseIds);
        }

        return DataTables::of($query)
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
                $checked = (int) $row->active_inactive === 1 ? 'checked' : '';

                return '
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input stationed-leave-status-toggle"
                               type="checkbox"
                               data-id="' . $row->pk . '"
                               ' . $checked . '>
                    </div>';
            })
            ->addColumn('action', function ($row) {
                $url = route('admin.stationed-leave-master.create', [
                    'course_master_pk' => $row->course_master_pk,
                    'effective_from' => $row->effective_from?->format('Y-m-d'),
                ]);

                $editBtn = '
                    <a href="' . $url . '" class="text-primary" title="Edit">
                        <i class="material-icons material-symbols-rounded" style="font-size:20px;">edit</i>
                    </a>';

                $deleteBtn = '';
                if ((int) $row->active_inactive === 0) {
                    $deleteBtn = '
                        <a href="javascript:void(0)" class="text-danger stationed-leave-delete-btn" data-id="' . $row->pk . '" title="Delete">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;">delete</i>
                        </a>';
                }

                return '<div class="d-inline-flex align-items-center gap-2">' . $editBtn . $deleteBtn . '</div>';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
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

    protected function getAllowedCourseIds(): ?array
    {
        if (hasRole('Super Admin')) {
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
