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
        $data_course_id =  get_Role_by_course();
        $query = MemoNoticeTemplate::with('course');

        if(!empty($data_course_id)){
            $query->whereIn('course_master_pk',$data_course_id);
        }

        // Filter by course if selected
        if ($request->filled('course_master_pk')) {
            $query->where('course_master_pk', $request->course_master_pk);
        }

        // Filter by status if selected
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by active/inactive state (matches the Status toggle column).
        if ($request->filled('active_inactive') && in_array((string) $request->active_inactive, ['0', '1'], true)) {
            $query->where('active_inactive', (string) $request->active_inactive);
        }

        // Free-text search on template title / type.
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('memo_notice_type', 'like', "%{$search}%");
            });
        }

        // Column sorting (whitelisted). Program sorts by the related course name via a
        // correlated subquery so no join is needed (avoids active_inactive ambiguity).
        $sort = (string) $request->input('sort', '');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortColumns = [
            'title'  => 'title',
            'type'   => 'memo_notice_type',
            'status' => 'active_inactive',
        ];

        if ($sort === 'program') {
            $query->orderBy(
                CourseMaster::select('course_name')
                    ->whereColumn('course_master.pk', 'memo_notice_templates.course_master_pk'),
                $direction
            );
        } elseif (isset($sortColumns[$sort])) {
            $query->orderBy($sortColumns[$sort], $direction);
        } else {
            $query->orderBy('created_date', 'desc');
        }

        // Per-page size for the design-system footer selector.
        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        // withQueryString() keeps the active filters + sort on the pagination links.
        $templates = $query->paginate($perPage)->withQueryString();

        // Current date for filtering
        $currentDate = now()->toDateString();

        // Only active + ongoing + upcoming courses
        $courses = CourseMaster::where('active_inactive', 1);
            $courses->where(function ($q) use ($currentDate) {

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

            });
            if(!empty($data_course_id)){
                $courses->whereIn('pk',$data_course_id);
            }
            $courses = $courses->orderBy('course_name')
            ->get(['pk', 'course_name']);

        return view('admin.courseAttendanceNoticeMap.memo_notice_index', compact('templates', 'courses'));
    }

    // Show create form
    public function create()
    {
        $currentDate = now()->toDateString();

        $courses = CourseMaster::where('active_inactive', 1);
        $data_course_id =  get_Role_by_course();
        if(!empty($data_course_id)){
            $courses->whereIn('pk',$data_course_id);
        }
        $courses->where(function ($q) use ($currentDate) {

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
            });
        $courses = $courses->orderBy('course_name')
            ->get(['pk', 'course_name', 'start_year', 'end_date']);

        // Disciplines (for the discipline-specific "Discipline Memo" template); filtered by course client-side.
        $disciplines = \App\Models\DisciplineMaster::where('active_inactive', 1)
            ->orderBy('discipline_name')
            ->get(['pk', 'discipline_name', 'course_master_pk']);

        // Memo Types (for the Memo-specific template link, shown only when memo_notice_type = 'Memo').
        $memoTypes = \App\Models\MemoTypeMaster::where('active_inactive', 1)
            ->orderBy('memo_type_name')
            ->get(['pk', 'memo_type_name']);

        return view('admin.courseAttendanceNoticeMap.memo_notice_create', compact('courses', 'disciplines', 'memoTypes'));
    }


   public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'course_master_pk' => 'nullable|integer',
            'discipline_master_pk' => 'nullable|integer|exists:discipline_master,pk|required_if:memo_notice_type,Discipline Memo',
            'memo_type_master_pk' => 'nullable|integer|exists:memo_type_master,pk|required_if:memo_notice_type,Memo',
            'title' => 'required|string|max:255',
            'director' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'content' => 'required|string',
            'memo_notice_type' => 'required|string|in:Memo,Notice,Discipline Memo',
            'signature_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'discipline_master_pk.required_if' => 'Please select a discipline for a Discipline Memo template.',
            'memo_type_master_pk.required_if' => 'Please select a Memo Type for a Memo template.',
        ]);

        // A discipline only applies to Discipline Memo templates; other types stay course-wide.
        $disciplinePk = $validated['memo_notice_type'] === 'Discipline Memo'
            ? ($validated['discipline_master_pk'] ?? null)
            : null;

        // A memo type only applies to Memo templates; other types stay memo-type-agnostic.
        $memoTypePk = $validated['memo_notice_type'] === 'Memo'
            ? ($validated['memo_type_master_pk'] ?? null)
            : null;

        // Notice/Memo may have several templates per course (picked at send time);
        // only Discipline Memo is limited to one active template per course + discipline.
        if ($validated['memo_notice_type'] === 'Discipline Memo') {
            $alreadyExists = MemoNoticeTemplate::where('course_master_pk', $validated['course_master_pk'])
                ->where('memo_notice_type', 'Discipline Memo')
                ->where('active_inactive', 1)
                ->where('discipline_master_pk', $disciplinePk)
                ->exists();

            if ($alreadyExists) {
                return back()->withInput()->with('error',
                    'An active Discipline Memo already exists for this course and discipline.');
            }
        }

        $signaturePath = null;
        if ($request->hasFile('signature_image')) {
            $signaturePath = $request->file('signature_image')->store('memo-notice/signatures', 'public');
        }

        MemoNoticeTemplate::create([
            'course_master_pk' => $validated['course_master_pk'] ?: null,
            'discipline_master_pk' => $disciplinePk,
            'memo_type_master_pk' => $memoTypePk,
            'title' => $validated['title'],
            'director_name' => $validated['director'],
            'director_designation' => $validated['designation'],
            'content' => $validated['content'],
            'memo_notice_type' => $validated['memo_notice_type'],
            'signature_image' => $signaturePath,
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
    // Read-only template details (JSON) for the View modal.
    public function show($id)
    {
        $template = MemoNoticeTemplate::with('course')->findOrFail($id);

        return response()->json([
            'success' => true,
            'template' => [
                'title' => $template->title,
                'course_name' => $template->course->course_name ?? 'General',
                'type' => $template->memo_notice_type,
                'status' => $template->active_inactive == 1 ? 'Active' : 'Inactive',
                'director_name' => $template->director_name,
                'director_designation' => $template->director_designation,
                'content' => $template->content,
                'signature_url' => $template->signature_image ? asset('storage/' . $template->signature_image) : null,
            ],
        ]);
    }

    public function edit($id)
    {
        $template = MemoNoticeTemplate::findOrFail($id);

        $currentDate = now()->format('Y-m-d');

        // Fetch only active + ongoing + upcoming courses
        $courses = CourseMaster::where('active_inactive', 1);
        $data_course_id =  get_Role_by_course();
        if(!empty($data_course_id)){
            $courses->whereIn('pk',$data_course_id);
        }
        $courses->where(function ($q) use ($currentDate) {

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
            });
        $courses = $courses->orderBy('course_name')
            ->get(['pk', 'course_name']);

        $disciplines = \App\Models\DisciplineMaster::where('active_inactive', 1)
            ->orderBy('discipline_name')
            ->get(['pk', 'discipline_name', 'course_master_pk']);

        $memoTypes = \App\Models\MemoTypeMaster::where('active_inactive', 1)
            ->orderBy('memo_type_name')
            ->get(['pk', 'memo_type_name']);

        return view('admin.courseAttendanceNoticeMap.memo_notice_edit', compact('template', 'courses', 'disciplines', 'memoTypes'));
    }


    // Update template
    public function update(Request $request, $id)
{
    try {
        $template = MemoNoticeTemplate::findOrFail($id);

        $validated = $request->validate([
            'course_master_pk' => 'nullable|integer',
            'discipline_master_pk' => 'nullable|integer|exists:discipline_master,pk|required_if:memo_notice_type,Discipline Memo',
            'memo_type_master_pk' => 'nullable|integer|exists:memo_type_master,pk|required_if:memo_notice_type,Memo',
            'title' => 'required|string|max:255',
            'director' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'content' => 'required|string',
            'memo_notice_type' => 'required|string|in:Memo,Notice,Discipline Memo',
            'signature_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'discipline_master_pk.required_if' => 'Please select a discipline for a Discipline Memo template.',
            'memo_type_master_pk.required_if' => 'Please select a Memo Type for a Memo template.',
        ]);

        $disciplinePk = $validated['memo_notice_type'] === 'Discipline Memo'
            ? ($validated['discipline_master_pk'] ?? null)
            : null;

        $memoTypePk = $validated['memo_notice_type'] === 'Memo'
            ? ($validated['memo_type_master_pk'] ?? null)
            : null;

        // Only Discipline Memo is limited to one active template per course + discipline;
        // Notice/Memo may have several per course.
        if ($validated['memo_notice_type'] === 'Discipline Memo') {
            $alreadyExists = MemoNoticeTemplate::where('course_master_pk', $validated['course_master_pk'])
                ->where('memo_notice_type', 'Discipline Memo')
                ->where('active_inactive', 1)
                ->where('pk', '!=', $template->pk)
                ->where('discipline_master_pk', $disciplinePk)
                ->exists();

            if ($alreadyExists) {
                return back()->withInput()->with('error',
                    'An active Discipline Memo already exists for this course and discipline.');
            }
        }

        $updateData = [
            'course_master_pk' => $validated['course_master_pk'] ?: null,
            'discipline_master_pk' => $disciplinePk,
            'memo_type_master_pk' => $memoTypePk,
            'title' => $validated['title'],
            'director_name' => $validated['director'],
            'director_designation' => $validated['designation'],
            'content' => $validated['content'],
            'memo_notice_type' => $validated['memo_notice_type'],
            'updated_by' => Auth::id(),
        ];

        if ($request->hasFile('signature_image')) {
            if ($template->signature_image) {
                Storage::disk('public')->delete($template->signature_image);
            }
            $updateData['signature_image'] = $request->file('signature_image')->store('memo-notice/signatures', 'public');
        }

        if ($request->input('remove_signature') == '1' && $template->signature_image) {
            Storage::disk('public')->delete($template->signature_image);
            $updateData['signature_image'] = null;
        }

        $template->update($updateData);

        return redirect()->route('admin.memo-notice.index')
            ->with('success', 'Memo/Notice template updated successfully.');

    } catch (\Exception $e) {
        \Log::error('Update error: ' . $e->getMessage());
        return back()->withInput()->with('error', 'Failed to update template.' . $e->getMessage());
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
