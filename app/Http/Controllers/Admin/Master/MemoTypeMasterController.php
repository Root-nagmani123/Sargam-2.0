<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MemoTypeMaster;
use Illuminate\Support\Facades\Storage;
use App\DataTables\MemoTypeMasterDataTable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;



class MemoTypeMasterController extends Controller
{
    public function index(MemoTypeMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.memo_type.index');
    }

    public function create()
    {
        return view('admin.master.memo_type.create_edit');
    }


    public function store(Request $request)
    {
        // The listing's modal posts the raw pk; the standalone edit page posts it encrypted.
        $id = null;
        if ($request->filled('pk')) {
            if (! is_scalar($request->pk)) {
                return response()->json(['status' => false, 'message' => 'Invalid record.'], 422);
            }
            try {
                $id = is_numeric($request->pk) ? (int) $request->pk : (int) decrypt($request->pk);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                return response()->json(['status' => false, 'message' => 'Record not found.'], 404);
            }
        }

        // ✅ Validation (AJAX compatible)
        $validator = Validator::make($request->all(), [
            'memo_type_name'   => [
                'required',
                'string',
                'max:100',
                // Column collation is case-insensitive and TrimStrings runs first,
                // so "Warning" and " warning " count as the same name.
                Rule::unique('memo_type_master', 'memo_type_name')->ignore($id, 'pk'),
            ],
            'memo_doc_upload'  => 'nullable|mimes:pdf,doc,docx|max:2048',
            'active_inactive'  => 'required|in:1,2',
        ], [
            'memo_type_name.unique' => 'This memo type name already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // ✅ Add / Edit logic
            if ($id) {
                $memoType = MemoTypeMaster::findOrFail($id);
            } else {
                $memoType = new MemoTypeMaster();
            }

            $memoType->memo_type_name  = $request->memo_type_name;
            $memoType->active_inactive = $request->active_inactive;

            // ✅ File Upload
            if ($request->hasFile('memo_doc_upload')) {

                // Delete old file if exists
                if (
                    !empty($memoType->memo_doc_upload) &&
                    Storage::disk('public')->exists($memoType->memo_doc_upload)
                ) {
                    Storage::disk('public')->delete($memoType->memo_doc_upload);
                }

                $file = $request->file('memo_doc_upload');
                $extension = safe_upload_extension($file);

                $filename = 'memo_' . time() . '.' . $extension;

                $path = $file->storeAs('memo_documents', $filename, 'public');

                $memoType->memo_doc_upload = $path;
            }

            $memoType->save();

            return response()->json([
                'status'  => true,
                'message' => 'Memo Type saved successfully.'
            ]);
        } catch (\Exception $e) {

            Log::error('Memo Type Store Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }


    public function edit($id)
    {
        $memoType = MemoTypeMaster::findOrFail(decrypt($id));
        return view('admin.master.memo_type.create_edit', compact('memoType'));
    }

    public function DELETE(Request $request, $id)
    {
        try {
            // 🔹 Find record
            $memoType = MemoTypeMaster::findOrFail(decrypt($id));

            // 🔒 Block delete if active
            if ($memoType->active_inactive == 1) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Active memo type cannot be deleted.'
                ], 403);
            }

            // 🗑️ Delete record
            $memoType->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Memo type deleted successfully.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Record not found.'
            ], 404);
        } catch (\Exception $e) {

            Log::error('Delete Memo Type Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }
}
