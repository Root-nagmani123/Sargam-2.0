<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\Master\ClubSocietyRoleMasterDataTable;
use App\Exports\ClubSocietyRoleMasterExport;
use App\Http\Controllers\Controller;
use App\Models\ClubSocietyRoleMaster;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Communications -> Club/ Society -> Define Club/ Society Role.
 *
 * The grid, the Add/Edit modal and the delete action all live on the index page;
 * store() therefore serves both create and update and answers JSON for the
 * modal's AJAX submit.
 */
class ClubSocietyRoleMasterController extends Controller
{
    public function index(ClubSocietyRoleMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.club_society_role.index');
    }

    public function store(Request $request)
    {
        // `pk` arrives encrypted from the edit button; decrypt before it is used
        // either as the unique-rule exception or as the row to update. Posting a
        // raw pk must not silently fall through to a create.
        $pk = null;
        if ($request->filled('pk')) {
            try {
                $pk = decrypt($request->input('pk'));
            } catch (\Throwable $e) {
                return $this->failure($request, 'Invalid record reference. Please reload the page and try again.');
            }
        }

        $validated = $request->validate([
            'club_society_role_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('club_society_role_master', 'club_society_role_name')->ignore($pk, 'pk'),
            ],
        ], [
            'club_society_role_name.required' => 'Club/ Society role name is required.',
            'club_society_role_name.unique'   => 'This club/ society role already exists.',
        ]);

        if ($pk) {
            $role    = ClubSocietyRoleMaster::findOrFail($pk);
            $message = 'Club/ Society role updated successfully.';
        } else {
            $role    = new ClubSocietyRoleMaster();
            $message = 'Club/ Society role created successfully.';
        }

        $role->club_society_role_name = trim($validated['club_society_role_name']);

        if ($request->filled('active_inactive')) {
            $role->active_inactive = (int) $request->input('active_inactive');
        } elseif (! $role->exists) {
            $role->active_inactive = 1;
        }

        $role->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.club.society.role.index')->with('success', $message);
    }

    public function destroy(Request $request, $id)
    {
        try {
            $role = ClubSocietyRoleMaster::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return $this->failure($request, 'Club/ Society role not found.', 404);
        }

        $role->delete();

        $message = 'Club/ Society role deleted successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.club.society.role.index')->with('success', $message);
    }

    public function export()
    {
        try {
            return Excel::download(new ClubSocietyRoleMasterExport, 'club_society_role_master.xlsx');
        } catch (\Throwable $e) {
            return redirect()
                ->route('master.club.society.role.index')
                ->with('error', 'Error exporting data: ' . $e->getMessage());
        }
    }

    /**
     * Branded print sheet. Renders every row (not just the visible DataTables
     * page) with the same columns as the Excel download so the two agree.
     */
    public function print()
    {
        return view('admin.master.club_society_role.export_print', [
            'rows'       => ClubSocietyRoleMaster::orderBy('pk')->get(),
            'exportDate' => now()->format('d M Y, h:i A'),
        ]);
    }

    /** Shape an error the same way for AJAX callers and full page posts. */
    private function failure(Request $request, string $message, int $status = 422)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $message], $status);
        }

        return redirect()->route('master.club.society.role.index')->with('error', $message);
    }
}
