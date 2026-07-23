<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\IssueReportDataTable;
use App\DataTables\UserIssueReportDataTable;
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
     * Admin listing of reported issues (DataTable-driven).
     * Non-admin users are redirected to their own issues page.
     */
    public function index(IssueReportDataTable $dataTable)
    {
        if (! isSidebarPrivilegedUser()) {
            return redirect()->route('my.issue-reports.index');
        }

        return $dataTable->render('admin.issue_reports.index', [
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    /**
     * JSON detail for a single reported issue (feeds the admin details modal).
     */
    public function show($id)
    {
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
                'attachment_url'=> $report->attachment ? url('storage/' . $report->attachment) : null,
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

    /**
     * User-facing listing — shows only the current user's own reported issues.
     */
    public function myIssues(UserIssueReportDataTable $dataTable)
    {
        return $dataTable->render('admin.issue_reports.my_issues');
    }

    /**
     * Distinct department/submodule values for the user-facing filter dropdowns
     * (scoped to the current user's own issues).
     */
    public function myFilterOptions()
    {
        $userId = Auth::user()->user_id ?? Auth::id();

        $departments = DB::table('issue_reports')
            ->where('reported_by', $userId)
            ->whereNotNull('module_name')
            ->where('module_name', '!=', '')
            ->distinct()
            ->orderBy('module_name')
            ->pluck('module_name');

        $submodules = DB::table('issue_reports')
            ->where('reported_by', $userId)
            ->whereNotNull('sub_module')
            ->where('sub_module', '!=', '')
            ->distinct()
            ->orderBy('sub_module')
            ->pluck('sub_module');

        return response()->json(compact('departments', 'submodules'));
    }

    /**
     * CSV export for the user's own issues (same filter params as the DataTable).
     */
    public function myExport(\Illuminate\Http\Request $request)
    {
        $userId          = Auth::user()->user_id ?? Auth::id();
        $statusFilter    = $request->query('status_filter', 'all');
        $deptFilter      = $request->query('dept_filter', '');
        $submoduleFilter = $request->query('submodule_filter', '');
        $dateFrom        = $request->query('date_from', '');
        $dateTo          = $request->query('date_to', '');

        $query = IssueReport::query()
            ->where('reported_by', $userId)
            ->select('issue_reports.*');

        if ($statusFilter === 'active') {
            $query->whereIn('status', [IssueReport::STATUS_OPEN, IssueReport::STATUS_IN_PROGRESS]);
        } elseif ($statusFilter === 'fixed') {
            $query->whereIn('status', [IssueReport::STATUS_RESOLVED, IssueReport::STATUS_CLOSED]);
        }

        if ($deptFilter      !== '') $query->where('module_name', $deptFilter);
        if ($submoduleFilter !== '') $query->where('sub_module', $submoduleFilter);
        if ($dateFrom        !== '') $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo          !== '') $query->whereDate('created_at', '<=', $dateTo);

        $reports  = $query->orderBy('id', 'desc')->get();
        $filename = 'my_issue_reports_' . date('Ymd_His') . '.csv';

        return response()->stream(function () use ($reports) {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($h, ['S.No.', 'Date', 'Department Name', 'Sub-Module', 'Issue Description', 'Status']);
            foreach ($reports as $i => $r) {
                $label = in_array((int) $r->status, [IssueReport::STATUS_OPEN, IssueReport::STATUS_IN_PROGRESS])
                    ? 'Active' : 'Fixed Issue';
                fputcsv($h, [
                    $i + 1,
                    $r->created_at ? Carbon::parse($r->created_at)->format('d-m-Y') : '',
                    $r->module_name ?? '',
                    $r->sub_module  ?? '',
                    $r->description ?? '',
                    $label,
                ]);
            }
            fclose($h);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Delete a reported issue (and its attachment if present).
     */
    public function destroy($id)
    {
        $report = IssueReport::findOrFail($id);

        if ($report->attachment) {
            Storage::disk('public')->delete($report->attachment);
        }

        $report->delete();

        return response()->json(['success' => true, 'message' => 'Issue #' . $id . ' deleted.']);
    }

    /**
     * Distinct department/submodule values for the filter dropdowns.
     */
    public function filterOptions()
    {
        $departments = DB::table('issue_reports')
            ->whereNotNull('module_name')
            ->where('module_name', '!=', '')
            ->distinct()
            ->orderBy('module_name')
            ->pluck('module_name');

        $submodules = DB::table('issue_reports')
            ->whereNotNull('sub_module')
            ->where('sub_module', '!=', '')
            ->distinct()
            ->orderBy('sub_module')
            ->pluck('sub_module');

        return response()->json(compact('departments', 'submodules'));
    }

    /**
     * CSV export of issue reports (respects the same filters as the DataTable).
     */
    public function export(\Illuminate\Http\Request $request)
    {
        $statusFilter    = $request->query('status_filter', 'all');
        $deptFilter      = $request->query('dept_filter', '');
        $submoduleFilter = $request->query('submodule_filter', '');
        $dateFrom        = $request->query('date_from', '');
        $dateTo          = $request->query('date_to', '');

        $query = IssueReport::query()
            ->leftJoin('user_credentials as uc', 'uc.user_id', '=', 'issue_reports.reported_by')
            ->select([
                'issue_reports.*',
                'uc.first_name as reporter_first',
                'uc.last_name  as reporter_last',
                'uc.user_name  as reporter_username',
            ]);

        if ($statusFilter === 'active') {
            $query->whereIn('issue_reports.status', [IssueReport::STATUS_OPEN, IssueReport::STATUS_IN_PROGRESS]);
        } elseif ($statusFilter === 'fixed') {
            $query->whereIn('issue_reports.status', [IssueReport::STATUS_RESOLVED, IssueReport::STATUS_CLOSED]);
        }

        if ($deptFilter      !== '') $query->where('issue_reports.module_name', $deptFilter);
        if ($submoduleFilter !== '') $query->where('issue_reports.sub_module', $submoduleFilter);
        if ($dateFrom        !== '') $query->whereDate('issue_reports.created_at', '>=', $dateFrom);
        if ($dateTo          !== '') $query->whereDate('issue_reports.created_at', '<=', $dateTo);

        $reports  = $query->orderBy('issue_reports.id', 'desc')->get();
        $filename = 'issue_reports_' . date('Ymd_His') . '.csv';

        return response()->stream(function () use ($reports) {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($h, ['S.No.', 'Date', 'Department Name', 'Sub-Module', 'Issue Raised By', 'Issue Description', 'Status']);
            foreach ($reports as $i => $r) {
                $name  = trim(($r->reporter_first ?? '') . ' ' . ($r->reporter_last ?? ''));
                if ($name === '') $name = $r->reporter_username ?? ('User #' . $r->reported_by);
                $label = in_array((int) $r->status, [IssueReport::STATUS_OPEN, IssueReport::STATUS_IN_PROGRESS])
                    ? 'Active' : 'Fixed Issue';
                fputcsv($h, [
                    $i + 1,
                    $r->created_at ? Carbon::parse($r->created_at)->format('d-m-Y') : '',
                    $r->module_name ?? '',
                    $r->sub_module  ?? '',
                    $name,
                    $r->description ?? '',
                    $label,
                ]);
            }
            fclose($h);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
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
                'reported_by'   => Auth::user()->user_id ?? Auth::id(),
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
