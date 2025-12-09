<?php
// app/Http/Controllers/Admin/MemoNoticeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemoNoticeTemplate;
use App\Models\CourseMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MemoNoticeController extends Controller
{
    // Display all templates
    public function index(Request $request)
    {
        $query = MemoNoticeTemplate::with('course')
            ->orderBy('created_date', 'desc');

        // Filter by course if selected
        if ($request->filled('course_master_pk')) {
            $query->where('course_master_pk', $request->course_master_pk);
        }

        // Filter by status if selected
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $templates = $query->paginate(20);

        // Current date for filtering
        $currentDate = now()->toDateString();

        // Only active + ongoing + upcoming courses
        $courses = CourseMaster::where('active_inactive', 1)
            ->where(function ($q) use ($currentDate) {

                // Ongoing courses: start_year <= today AND (end_date is null OR end_date >= today)
                $q->where(function ($ongoing) use ($currentDate) {
                    $ongoing->where('start_year', '<=', $currentDate)
                        ->where(function ($end) use ($currentDate) {
                            $end->whereNull('end_date')
                                ->orWhere('end_date', '>=', $currentDate);
                        });
                })

                    // OR upcoming courses: start_year > today
                    ->orWhere('start_year', '>', $currentDate);
            })
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);

        return view('admin.courseAttendanceNoticeMap.memo_notice_index', compact('templates', 'courses'));
    }

    // Show create form
    public function create()
    {
        $currentDate = now()->toDateString();

        $courses = CourseMaster::where('active_inactive', 1)
            ->where(function ($q) use ($currentDate) {

                // Ongoing courses
                $q->where(function ($ongoing) use ($currentDate) {
                    $ongoing->where('start_year', '<=', $currentDate)
                        ->where(function ($end) use ($currentDate) {
                            $end->whereNull('end_date')
                                ->orWhere('end_date', '>=', $currentDate);
                        });
                })

                    // OR upcoming courses
                    ->orWhere(function ($upcoming) use ($currentDate) {
                        $upcoming->where('start_year', '>', $currentDate);
                    });
            })
            ->orderBy('course_name')
            ->get(['pk', 'course_name', 'start_year', 'end_date']);

        return view('admin.courseAttendanceNoticeMap.memo_notice_create', compact('courses'));
    }


   public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'course_master_pk' => 'nullable|integer',
            'title' => 'required|string|max:255',
            'director' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'content' => 'required|string',
            'memo_notice_type' => 'required|string|in:Memo,Notice',
        ]);

        // Prevent Duplicate Active Memo/Notice
        $alreadyExists = MemoNoticeTemplate::where('course_master_pk', $validated['course_master_pk'])
            ->where('memo_notice_type', $validated['memo_notice_type'])
            ->where('active_inactive', 1)   // Only active templates count
            ->exists();

        if ($alreadyExists) {
            return back()->withInput()->with('error',
                'An active ' . $validated['memo_notice_type'] . ' already exists for this course.');
        }

        MemoNoticeTemplate::create([
            'course_master_pk' => $validated['course_master_pk'] ?: null,
            'title' => $validated['title'],
            'director_name' => $validated['director'],
            'director_designation' => $validated['designation'],
            'content' => $validated['content'],
            'memo_notice_type' => $validated['memo_notice_type'],
            'active_inactive' => 1,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.memo-notice.index')
            ->with('success', 'Template created successfully.');
    }
    catch (\Exception $e) {
        \Log::error('Memo Notice Creation Error: '.$e->getMessage());
        return back()->withInput()->with('error', 'Failed to create template.');
    }
}


    // Show edit form
    public function edit($id)
    {
        $template = MemoNoticeTemplate::findOrFail($id);

        $currentDate = now()->format('Y-m-d');

        // Fetch only active + ongoing + upcoming courses
        $courses = CourseMaster::where('active_inactive', 1)
            ->where(function ($q) use ($currentDate) {

                // **Ongoing courses**: start_year <= today AND (end_date is null OR end_date >= today)
                $q->where(function ($ongoing) use ($currentDate) {
                    $ongoing->where('start_year', '<=', $currentDate)
                        ->where(function ($end) use ($currentDate) {
                            $end->whereNull('end_date')
                                ->orWhere('end_date', '>=', $currentDate);
                        });
                })

                    // **OR upcoming courses**: start_year > today
                    ->orWhere('start_year', '>', $currentDate);
            })
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);

        return view('admin.courseAttendanceNoticeMap.memo_notice_edit', compact('template', 'courses'));
    }


    // Update template
    public function update(Request $request, $id)
    {

        try {
            $template = MemoNoticeTemplate::findOrFail($id);

            // Use the correct field names from your form
            $validated = $request->validate([
                'course_master_pk' => 'nullable|integer',
                'title' => 'required|string|max:255',
                'director' => 'required|string|max:255', // Changed from director_name to director
                'designation' => 'required|string|max:255', // Changed from director_designation to designation
                'content' => 'required|string',
                'memo_notice_type' => 'required|string|in:Memo,Notice',
            ]);

            // Map form fields to database columns
            $updateData = [
                'course_master_pk' => $validated['course_master_pk'] ?: null,
                'title' => $validated['title'],
                'director_name' => $validated['director'], // Map 'director' to 'director_name'
                'director_designation' => $validated['designation'], // Map 'designation' to 'director_designation'
                'content' => $validated['content'],
                'memo_notice_type' => $validated['memo_notice_type'],
                'updated_by' => Auth::id()
            ];
            $template->update($updateData);

            return redirect()->route('admin.memo-notice.index')
                ->with('success', 'Memo/Notice template updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Update error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error updating template: ' . $e->getMessage());
        }
    }

    // Delete template
    public function destroy($id)
    {
        $template = MemoNoticeTemplate::findOrFail($id);
        if ($template->active_inactive == 1) {
            return back()->with('error', 'Active templates cannot be deleted. Please deactivate first.');
        }
        $template->delete();

        return redirect()->route('admin.memo-notice.index')
            ->with('success', 'Memo/Notice template deleted successfully.');
    }

    // Upload PDF file
    public function uploadPdf(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240' // 10MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('memo-notice/pdfs', 'public');

        return response()->json([
            'url' => Storage::url($path),
            'filename' => $file->getClientOriginalName()
        ]);
    }

    // Preview template
    public function preview($id)
    {
        $template = MemoNoticeTemplate::with('course')->findOrFail($id);

        return view('admin.courseAttendanceNoticeMap.memo_notice_preview', compact('template'));
    }

    // Change status
   public function changeStatus($id, $status)
{
    try {
        $template = MemoNoticeTemplate::findOrFail($id);

        $courseId = $template->course_master_pk;
        $type = $template->memo_notice_type; // Memo or Notice

        // Only if activating
        if ($status == 1) {
            MemoNoticeTemplate::where('course_master_pk', $courseId)
                ->where('memo_notice_type', $type) // 🔥 Only same type deactivate
                ->where('pk', '!=', $id)
                ->update([
                    'active_inactive' => 0,
                    'updated_by' => Auth::id()
                ]);
        }

        // Update current template
        $template->update([
            'active_inactive' => $status,
            'updated_by' => Auth::id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status updated successfully.'
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong!'
        ], 500);
    }
}


}
