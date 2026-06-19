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
            if (! hasRole('Super Admin') && ! hasRole('Training IST')) {
                abort(403, 'You are not authorized to access PT Exemption Master.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        return view('admin.exemption_master.index');
    }

    public function create(Request $request)
    {
        $courses = $this->getCourses();
        $courseMasterPk = $request->query('course_master_pk');
        $effectiveFrom = $request->query('effective_from');

        $maleRecord = null;
        $femaleRecord = null;

        if ($courseMasterPk && $effectiveFrom) {
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
            'femaleRecord'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_master_pk' => 'required|exists:course_master,pk',
            'effective_from' => 'required|date',
            'male_exemption_days' => 'required|numeric|min:0|max:999.9',
            'female_exemption_days' => 'required|numeric|min:0|max:999.9',
        ]);

        $this->assertCourseAllowed((int) $validated['course_master_pk']);

        $user = Auth::user();
        $now = now();

        DB::transaction(function () use ($validated, $user, $now) {
            $this->saveExemptionRow(
                (int) $validated['course_master_pk'],
                $validated['effective_from'],
                'Male',
                (float) $validated['male_exemption_days'],
                $user->pk ?? null,
                $now
            );

            $this->saveExemptionRow(
                (int) $validated['course_master_pk'],
                $validated['effective_from'],
                'Female',
                (float) $validated['female_exemption_days'],
                $user->pk ?? null,
                $now
            );
        });

        return redirect()
            ->route('admin.pt-exemption-master.create', [
                'course_master_pk' => $validated['course_master_pk'],
                'effective_from' => $validated['effective_from'],
            ])
            ->with('success', 'PT exemption count saved successfully.');
    }

    protected function saveExemptionRow(
        int $courseMasterPk,
        string $effectiveFrom,
        string $gender,
        float $exemptionDays,
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
        $record = ExemptionMaster::findOrFail($id);

        $this->assertCourseAllowed((int) $record->course_master_pk);

        if ((int) $record->active_inactive === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Only inactive records can be deleted.',
            ], 422);
        }

        $record->delete();

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
                $record->update([
                    'active_inactive' => (int) $request->active_inactive,
                    'modified_date' => now(),
                ]);
            }
        }

        $query = ExemptionMaster::with('course')->orderByDesc('pk');

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
            ->addColumn('exemption_days_display', function ($row) {
                return number_format((float) $row->exemption_days, 1) . ' Days';
            })
            ->addColumn('status', function ($row) {
                $checked = (int) $row->active_inactive === 1 ? 'checked' : '';

                return '
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input exemption-status-toggle"
                               type="checkbox"
                               data-id="' . $row->pk . '"
                               ' . $checked . '>
                    </div>';
            })
            ->addColumn('action', function ($row) {
                $url = route('admin.pt-exemption-master.create', [
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
                        <a href="javascript:void(0)" class="text-danger exemption-delete-btn" data-id="' . $row->pk . '" title="Delete">
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

    /**
     * @return array<int>|null null = all courses (Super Admin)
     */
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
