<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Models\COE\LaptopResponse;
use App\Models\COE\ScriberMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * COE → Master Data → Scriber's Master.
 *
 * The list the OT Laptop Status dialog's Primary / Secondary Scriber dropdowns read.
 *
 * The grid is client-side (CoeGrid), like OT Laptop Status: index() renders every row
 * for the scope and the browser filters, sorts and pages them. Add / Edit / Delete are
 * AJAX calls answered in JSON; the page reloads only after the server says it worked.
 *
 * Two scopes, each its own URL, split on the scriber's status:
 *   ?scope=active    Active scribers (default)
 *   ?scope=archived  Inactive scribers
 */
class ScriberMasterController extends Controller
{
    public const SCOPES = ['active', 'archived'];

    public function index(Request $request)
    {
        $scope = $this->resolveScope($request);

        $rows = ScriberMaster::query()
            ->where('active_inactive', $scope === 'archived' ? 0 : 1)
            ->orderBy('scriber_name')
            ->get()
            ->map(fn (ScriberMaster $s) => [
                'id' => (int) $s->pk,
                'name' => $s->scriber_name,
                'phone' => $s->phone_number,
                'email' => $s->email,
                'role' => (int) $s->scriber_role,
                'role_label' => ScriberMaster::roleLabel($s->scriber_role),
                'remarks' => $s->remarks,
                'active' => (bool) $s->active_inactive,
            ]);

        return view('admin.coe.scriber_master.index', [
            'scope' => $scope,
            'rows' => $rows,
            'roles' => ScriberMaster::roles(),
        ]);
    }

    /**
     * Add Scriber / Edit — one endpoint; a `pk` in the body makes it an edit.
     *
     * Validation failures come back as Laravel's 422 JSON, keyed by the field names the
     * dialog posts, so the browser can put each message under its own control.
     */
    public function store(Request $request): JsonResponse
    {
        $id = $request->filled('pk') ? (int) $request->input('pk') : null;
        $existing = $id ? ScriberMaster::find($id) : null;

        if ($id && $existing === null) {
            return response()->json(['message' => 'That scriber no longer exists.'], 404);
        }

        $validated = $request->validate([
            'scriber_name' => ['required', 'string', 'max:150'],
            'phone_number' => ['required', 'regex:/^[0-9]{10}$/'],
            'email' => ['required', 'email', 'max:150', Rule::unique('coe_scriber_master', 'email')->ignore($id, 'pk')],
            'scriber_role' => ['required', Rule::in(array_map('strval', array_keys(ScriberMaster::roles())))],
            'remarks' => ['nullable', 'string', 'max:500'],
            'active_inactive' => ['required', Rule::in(['0', '1'])],
        ], [
            'scriber_name.required' => 'Please enter the scriber name.',
            'phone_number.required' => 'Please enter the phone number.',
            'phone_number.regex' => 'The phone number must be 10 digits.',
            'email.required' => 'Please enter the email ID.',
            'email.email' => 'Please enter a valid email ID.',
            'email.unique' => 'A scriber with this email ID already exists.',
            'scriber_role.required' => 'Please select the scriber role.',
        ]);

        // A role change would leave the scriber in a slot of the other kind on every OT
        // they are assigned to, which the dropdowns could then never show again.
        if ($existing && (int) $existing->scriber_role !== (int) $validated['scriber_role']) {
            $assigned = $this->assignmentCount($existing->pk);
            if ($assigned > 0) {
                $message = 'This scriber is assigned to ' . $assigned . ' OT(s); unassign them before changing the role.';

                return response()->json(['message' => $message, 'errors' => ['scriber_role' => [$message]]], 422);
            }
        }

        $scriber = $existing ?? new ScriberMaster();
        $now = now();
        $remarks = trim((string) ($validated['remarks'] ?? ''));

        $scriber->fill([
            'scriber_name' => trim($validated['scriber_name']),
            'phone_number' => $validated['phone_number'],
            'email' => strtolower(trim($validated['email'])),
            'scriber_role' => (int) $validated['scriber_role'],
            'remarks' => $remarks === '' ? null : $remarks,
            'active_inactive' => (int) $validated['active_inactive'],
        ]);
        if (! $scriber->exists) {
            $scriber->created_by = Auth::id();
            $scriber->created_date = $now;
        }
        $scriber->modified_by = Auth::id();
        $scriber->modified_date = $now;
        $scriber->save();

        return response()->json(['message' => $existing ? 'Scriber updated.' : 'Scriber added.']);
    }

    /**
     * Delete one scriber — refused while any OT is assigned to them. The foreign keys
     * would refuse it anyway; this says why, and points to Inactive instead.
     */
    public function destroy(int $scriber): JsonResponse
    {
        $record = ScriberMaster::find($scriber);
        if ($record === null) {
            return response()->json(['message' => 'That scriber no longer exists.'], 404);
        }

        $assigned = $this->assignmentCount($record->pk);
        if ($assigned > 0) {
            return response()->json([
                'message' => 'This scriber is assigned to ' . $assigned . ' OT(s) and cannot be deleted. Set the status to Inactive instead.',
            ], 422);
        }

        $record->delete();

        return response()->json(['message' => 'Scriber deleted.']);
    }

    private function assignmentCount(int $scriberPk): int
    {
        return LaptopResponse::where('primary_scriber_pk', $scriberPk)
            ->orWhere('secondary_scriber_pk', $scriberPk)
            ->count();
    }

    private function resolveScope(Request $request): string
    {
        $scope = $request->query('scope');

        return in_array($scope, self::SCOPES, true) ? $scope : 'active';
    }
}
