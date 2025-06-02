<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MemoTypeMaster;
use Illuminate\Support\Facades\Storage;


class MemoTypeMasterController extends Controller
{
    public function index()
    {
        $memoTypes = MemoTypeMaster::all();
        return view('admin.master.memo_type.index', compact('memoTypes'));
    }

    public function create()
    {
        return view('admin.master.memo_type.create_edit');
    }

    public function store(Request $request)
    {
                $request->validate([
                    'memo_type_name' => 'required|string|max:100',
                    'memo_doc_upload' => 'nullable|mimes:pdf,doc,docx|max:2048',
                    'active_inactive' => 'required|in:1,2',
                ]);


                    try {
                    if ($request->pk) {
                        $memoType = MemoTypeMaster::findOrFail(decrypt($request->pk));
                    } else {
                        $memoType = new MemoTypeMaster();
                    }

                    $memoType->memo_type_name = $request->memo_type_name;
                    $memoType->active_inactive = $request->active_inactive;

                    // File Upload to Storage
             if ($request->hasFile('memo_doc_upload')) {
                    $file = $request->file('memo_doc_upload');

                    // Delete old file if editing
                    if (isset($memoType->memo_doc_upload) && Storage::disk('public')->exists($memoType->memo_doc_upload)) {
                        Storage::disk('public')->delete($memoType->memo_doc_upload);
                    }

                    // Get original file name with extension
                    $originalFileName = $file->getClientOriginalName();

                    // Optional: create a unique file name to avoid overwrite
                    $filename = uniqid() . '_' . $originalFileName;

                    // Store the file in 'public/memo_documents' with custom name
                    $path = $file->storeAs('memo_documents', $filename, 'public');

                    // Save the relative path to DB
                    $memoType->memo_doc_upload = $path;
                }


                    $memoType->save();

                    return redirect()->route('master.memo.type.master.index')->with('success', 'Memo Type saved successfully.');
                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                    return redirect()->back()->with('error', 'Something went wrong.');
                }

    }

    public function edit($id)
    {
        $memoType = MemoTypeMaster::findOrFail(decrypt($id));
        return view('admin.master.memo_type.create_edit', compact('memoType'));
    }

    public function delete($id)
    {
        try {
            $memoType = MemoTypeMaster::findOrFail(decrypt($id));
            $memoType->delete();
            return redirect()->route('master.memo.type.master.index')->with('success', 'Memo Type deleted successfully.');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong.');
        }
    }
}