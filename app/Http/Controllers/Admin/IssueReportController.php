<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\IssueReportDataTable;
use App\DataTables\MyComplaintDataTable;
use App\Http\Controllers\Controller;
use App\Models\IssueReport;
use App\Models\SidebarMenu\MenuGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class IssueReportController extends Controller
{
    /** Human labels for issue_reports.status. */
    public const STATUS_LABELS = [
        IssueReport::STATUS_OPEN        => 'Open',
        IssueReport::STATUS_IN_PROGRESS => 'In Progress',
        IssueReport::STATUS_RESOLVED    => 'Resolved',
        IssueReport::STATUS_CLOSED      => 'Closed',
    ];

    /**
     * The value store() writes to issue_reports.reported_by.
     *
     * This is user_credentials.user_id (an employee_master.pk), NOT Auth::id() —
     * User::$primaryKey is 'pk', a different column. Both readers join on user_id
     * (IssueReportDataTable::query, self::show), so any scope that filters on
     * reported_by must resolve the identity through here or it silently matches
     * nothing. Kept deliberately identical to the original inline expression so
     * existing rows stay reachable; empties are rejected on the read side only.
     */
    public static function reporterIdentity()
    {
        $user = Auth::user();

        return $user ? ($user->user_id ?? Auth::id()) : null;
    }

    /** The issue console exposes every reporter's contact details, so it is Super Admin only. */
    private function authorizeConsole(): void
    {
        abort_unless(isSidebarPrivilegedUser(), 403, 'You do not have permission to view reported issues.');
    }

    /**
     * Admin listing of reported issues (DataTable-driven).
     */
    public function index(IssueReportDataTable $dataTable)
    {
        $this->authorizeConsole();

        return $dataTable->render('admin.issue_reports.index', [
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    /**
     * JSON detail for a single reported issue (feeds the admin details modal).
     */
    public function show($id)
    {
        $this->authorizeConsole();

        $report = IssueReport::findOrFail($id);

        $reporter = DB::table('user_credentials')
            ->where('user_id', $report->reported_by)
            ->first(['first_name', 'last_name', 'user_name', 'email_id', 'mobile_no']);

        $reporterName = $reporter
            ? trim(($reporter->first_name ?? '') . ' ' . ($reporter->last_name ?? ''))
            : '';
        if ($reporterName === '') {
            $reporterName = $reporter->user_name ?? ('User #' . $report->reported_by);
        }

        return response()->json([
            'success' => true,
            'issue'   => [
                'id'            => $report->id,
                'reference'     => '#' . $report->id,
                'reporter'      => $reporterName,
                'reporter_email'=> $reporter->email_id ?? null,
                'reporter_phone'=> $reporter->mobile_no ?? null,
                'module_name'   => $report->module_name,
                'sub_module'    => $report->sub_module,
                'description'   => $report->description,
                'page_url'      => $report->page_url,
                'attachment_url'=> $report->attachment ? Storage::disk('public')->url($report->attachment) : null,
                'status'        => (int) $report->status,
                'status_label'  => self::STATUS_LABELS[(int) $report->status] ?? 'Open',
                'reported_on'   => $report->created_at ? Carbon::parse($report->created_at)->format('d-m-Y h:i A') : null,
            ],
            'status_options' => self::STATUS_LABELS,
        ]);
    }

    /**
     * Update the workflow status of a reported issue.
     */
    public function updateStatus(Request $request, $id)
    {
        $this->authorizeConsole();

        $validated = $request->validate([
            'status' => 'required|integer|in:' . implode(',', array_keys(self::STATUS_LABELS)),
        ]);

        $report = IssueReport::findOrFail($id);
        $report->status = (int) $validated['status'];
        $report->save();

        return response()->json([
            'success'      => true,
            'message'      => 'Issue #' . $report->id . ' marked as ' . (self::STATUS_LABELS[$report->status] ?? 'Open') . '.',
            'status'       => $report->status,
            'status_label' => self::STATUS_LABELS[$report->status] ?? 'Open',
        ]);
    }

    /**
     * The reporter's own complaints. Open to every authenticated user — the
     * scoping lives in MyComplaintDataTable::query(), not in a role gate.
     */
    public function myComplaints(MyComplaintDataTable $dataTable)
    {
        return $dataTable->render('admin.my_complaints.index', [
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    /**
     * JSON detail for one of the CURRENT user's own complaints.
     *
     * Deliberately separate from show(): that one is a bare findOrFail, so reusing
     * it here would let any user read any complaint by guessing an id. Scoping the
     * lookup itself (rather than fetching then comparing) makes someone else's id
     * a 404 and leaks nothing. Contact fields are omitted — it is the caller's own
     * record, so they add nothing but exposure surface.
     */
    public function myComplaintShow($id)
    {
        $identity = self::reporterIdentity();

        // Fail closed: an unresolved identity must match no rows rather than every
        // row whose reported_by is likewise empty.
        abort_unless(filled($identity), 404);

        $report = IssueReport::where('id', $id)
            ->where('reported_by', $identity)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'issue'   => [
                'id'            => $report->id,
                'reference'     => '#' . $report->id,
                'module_name'   => $report->module_name,
                'sub_module'    => $report->sub_module,
                'description'   => $report->description,
                'page_url'      => $report->page_url,
                'attachment_url'=> $report->attachment ? Storage::disk('public')->url($report->attachment) : null,
                'status'        => (int) $report->status,
                'status_label'  => self::STATUS_LABELS[(int) $report->status] ?? 'Open',
                'reported_on'   => $report->created_at ? Carbon::parse($report->created_at)->format('d-m-Y h:i A') : null,
            ],
        ]);
    }

    /**
     * Modules offered in the "Report a problem" dropdown.
     * Groups are named non-uniquely across sidebar categories (e.g. "Time Table"
     * exists under both Setup and Academics), so collapse by name for the reporter.
     */
    public static function moduleOptions()
    {
        return MenuGroup::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->unique(fn ($g) => strtolower(trim($g->name)))
            ->map(fn ($g) => ['id' => $g->id, 'name' => trim($g->name)])
            ->values();
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'menu_group_id' => 'required|integer|exists:menu_groups,id',
                'sub_module'    => 'nullable|string|max:255',
                'description'   => 'required|string|max:5000',
                'attachment'    => 'nullable|file|mimes:jpg,jpeg,png,pdf,csv,xlsx|max:5120',
                'page_url'      => 'nullable|string|max:500',
            ], [
                'menu_group_id.required' => 'Please select the module you are facing issues with.',
                'menu_group_id.exists'   => 'The selected module is no longer available.',
                'description.required'   => 'Please describe the issue.',
                'attachment.mimes'       => 'Attachment must be a .jpg, .png, .pdf, .csv or .xlsx file.',
                'attachment.max'         => 'Attachment must not exceed 5MB.',
            ]);

            $group = MenuGroup::where('is_active', 1)->find($validated['menu_group_id']);
            if (!$group) {
                throw ValidationException::withMessages([
                    'menu_group_id' => ['The selected module is no longer available.'],
                ]);
            }

            $path = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                if (!$file->isValid()) {
                    throw ValidationException::withMessages([
                        'attachment' => ['The upload failed. Please try again or use a different file.'],
                    ]);
                }
                $path = $file->store('issue_reports', 'public');
            }

            $report = IssueReport::create([
                'reported_by'   => self::reporterIdentity(),
                'menu_group_id' => $group->id,
                'module_name'   => trim($group->name),
                'sub_module'    => $validated['sub_module'] ?? null,
                'description'   => $validated['description'],
                'attachment'    => $path,
                'page_url'      => $validated['page_url'] ?? $request->headers->get('referer'),
                'status'        => IssueReport::STATUS_OPEN,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your issue has been reported. Reference #' . $report->id . '.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Please correct the highlighted fields.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Issue report submit failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while reporting the issue. Please try again.',
            ], 500);
        }
    }
}
