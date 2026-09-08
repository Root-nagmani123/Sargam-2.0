<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\Master\ClubSocietyMasterDataTable;
use App\Exports\ClubSocietyMasterExport;
use App\Http\Controllers\Controller;
use App\Models\ClubSocietyMaster;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Communications → Club/ Society → Define Club/ Society.
 *
 * The grid, the Add/Edit modal and the delete action all live on the index page;
 * store() therefore serves both create and update and answers JSON for the
 * modal's AJAX submit.
 */
class ClubSocietyMasterController extends Controller
{
    public function index(ClubSocietyMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.club_society.index');
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
            'club_society_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('club_society_master', 'club_society_name')->ignore($pk, 'pk'),
            ],
        ], [
            'club_society_name.required' => 'Club/ Society name is required.',
            'club_society_name.unique'   => 'This club/ society already exists.',
        ]);

        if ($pk) {
            $clubSociety = ClubSocietyMaster::findOrFail($pk);
            $message     = 'Club/ Society updated successfully.';
        } else {
            $clubSociety = new ClubSocietyMaster();
            $message     = 'Club/ Society created successfully.';
        }

        $clubSociety->club_society_name = trim($validated['club_society_name']);

        if ($request->filled('active_inactive')) {
            $clubSociety->active_inactive = (int) $request->input('active_inactive');
        } elseif (! $clubSociety->exists) {
            $clubSociety->active_inactive = 1;
        }

        $clubSociety->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.club.society.index')->with('success', $message);
    }

    public function destroy(Request $request, $id)
    {
        try {
            $clubSociety = ClubSocietyMaster::findOrFail(decrypt($id));
        } catch (\Throwable $e) {
            return $this->failure($request, 'Club/ Society not found.', 404);
        }

        $clubSociety->delete();

        $message = 'Club/ Society deleted successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.club.society.index')->with('success', $message);
    }

    public function export()
    {
        try {
            return Excel::download(new ClubSocietyMasterExport, 'club_society_master.xlsx');
        } catch (\Throwable $e) {
            return redirect()
                ->route('master.club.society.index')
                ->with('error', 'Error exporting data: ' . $e->getMessage());
        }
    }

    /**
     * Branded print sheet. Renders every row (not just the visible DataTables
     * page) with the same columns as the Excel download so the two agree.
     */
    public function print()
    {
        return view('admin.master.club_society.export_print', [
            'rows'       => ClubSocietyMaster::orderBy('pk')->get(),
            'exportDate' => now()->format('d M Y, h:i A'),
        ]);
    }

    /** Shape an error the same way for AJAX callers and full page posts. */
    private function failure(Request $request, string $message, int $status = 422)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $message], $status);
        }

        return redirect()->route('master.club.society.index')->with('error', $message);
    }
}
