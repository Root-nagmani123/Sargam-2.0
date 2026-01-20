<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MemoConclusionMaster;
use App\DataTables\MemoConclusionMasterDataTable;
use Illuminate\Support\Facades\Log;

class MemoConclusionMasterController extends Controller
{
    public function index(MemoConclusionMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.memo_conclusion_master.index');
    }

    public function create()
    {
        return view('admin.master.memo_conclusion_master.create_edit');
    }

    public function edit($id)
    {
        $conclusion = MemoConclusionMaster::findOrFail(decrypt($id));
        return view('admin.master.memo_conclusion_master.create_edit', compact('conclusion'));
    }

    public function store(Request $request)
    {
        try {
            // 1️⃣ Validate input
            $validated = $request->validate([
                'discussion_name' => 'required|string|max:100',
                'pt_discusion'    => 'nullable|string',
                'active_inactive' => 'required',
            ]);

            // 2️⃣ Ensure active_inactive is stored as integer 0 or 1
            $validated['active_inactive'] = $validated['active_inactive'] ? 1 : 0;

            // 3️⃣ Update or Create
            if ($request->filled('id')) {
                // 🔹 Update using query
                MemoConclusionMaster::where('pk', $request->id)
                    ->update([
                        'discussion_name' => $validated['discussion_name'],
                        'pt_discusion'    => $validated['pt_discusion'] ?? null,
                        'active_inactive' => $validated['active_inactive'],
                    ]);

                $message = 'Memo Conclusion updated successfully.';
            } else {
                // 🔹 Create new record
                MemoConclusionMaster::create([
                    'discussion_name' => $validated['discussion_name'],
                    'pt_discusion'    => $validated['pt_discusion'] ?? null,
                    'active_inactive' => $validated['active_inactive'],
                ]);

                $message = 'Memo Conclusion saved successfully.';
            }

            // 4️⃣ Return JSON response for SweetAlert
            return response()->json([
                'status'  => true,
                'message' => $message,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors → display in SweetAlert
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log other errors
            Log::error($e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }



    public function destroy(Request $request, $id)
    {
        try {
            // 🔹 Find record
            $memo = MemoConclusionMaster::findOrFail($id);

            // 🔒 Prevent deleting active record
            if ($memo->active_inactive == 1) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Active memo conclusion cannot be deleted.'
                ], 403);
            }

            // 🗑️ Delete
            $memo->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Memo conclusion deleted successfully.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Record not found.'
            ], 404);
        } catch (\Exception $e) {

            Log::error('Delete Memo Conclusion Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }
}
