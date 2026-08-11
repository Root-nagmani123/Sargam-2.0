<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
    
use Illuminate\Http\Request;
use App\Models\NoticeNotification as Notice;
use App\Models\CourseMaster;
use Illuminate\Support\Facades\Crypt;
use Yajra\DataTables\Facades\DataTables;
use Auth;

class NoticeNotificationController extends Controller
{
    // Notice List Page
   public function index(Request $request)
{
    $types = ['Course notice','Office order','Personal','Office notice','Service related'];

    if ($request->ajax()) {
        $query = Notice::with(['course','user'])->orderBy('pk','DESC');

        // 🔍 Filters
        if ($request->notice_type) {
            $query->where('notice_type', $request->notice_type);
        }

        if ($request->course_id) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->status != "") {
            $query->where('active_inactive', $request->status);
        }

        // 🔍 Free-text search across title, type, course name and creator name
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('notice_title', 'like', $like)
                    ->orWhere('notice_type', 'like', $like)
                    ->orWhereHas('course', function ($c) use ($like) {
                        $c->where('course_name', 'like', $like);
                    })
                    ->orWhereHas('user', function ($u) use ($like) {
                        $u->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$like]);
                    });
            });
        }

        $rowIndex = (int) $request->input('start', 0);

        return DataTables::of($query)
            ->addColumn('sn', function ($n) use (&$rowIndex) {
                $rowIndex++;

                return $rowIndex;
            })
            ->addColumn('notice_title', function ($n) {
                return '<span class="notice-title-text js-custom-title-tooltip" data-full-title="' . e($n->notice_title) . '">'
                    . e($n->notice_title) . '</span>';
            })
            ->addColumn('notice_type', function ($n) {
                return '<span class="badge rounded-1 bg-info-subtle text-info text-capitalize">' . e($n->notice_type) . '</span>';
            })
            ->addColumn('course_name', function ($n) {
                return e($n->course->course_name ?? 'N/A');
            })
            ->addColumn('created_by', function ($n) {
                return e(trim($n->user->first_name . ' ' . $n->user->last_name));
            })
            ->addColumn('created_date', function ($n) {
                return \Carbon\Carbon::parse($n->created_date)->format('d-m-Y');
            })
            ->addColumn('display_date', function ($n) {
                return \Carbon\Carbon::parse($n->display_date)->format('d-m-Y');
            })
            ->addColumn('expiry_date', function ($n) {
                return \Carbon\Carbon::parse($n->expiry_date)->format('d-m-Y');
            })
            ->addColumn('status', function ($n) {
                $class = $n->active_inactive == 1 ? 'success-subtle text-success' : 'danger-subtle text-danger';
                $label = $n->active_inactive == 1 ? 'Active' : 'Inactive';

                return '<span class="badge rounded-1 js-notice-status-badge bg-' . $class . '" data-id="' . $n->pk . '">'
                    . $label . '</span>';
            })
            ->addColumn('action', function ($n) {
                $encId = Crypt::encrypt($n->pk);
                $editUrl = route('admin.notice.edit', $encId);
                $deleteUrl = route('admin.notice.destroy', $encId);
                $checked = $n->active_inactive == 1 ? 'checked' : '';
                $deleteEnabledClass = $n->active_inactive == 0 ? '' : 'd-none';
                $deleteDisabledClass = $n->active_inactive == 1 ? '' : 'd-none';

                return '<div class="d-inline-flex align-items-center gap-1">'
                    . '<a href="' . $editUrl . '" class="btn btn-sm btn-outline-primary btn-transparent border-0 p-0" title="Edit" aria-label="Edit Notice">'
                    . '<span class="material-symbols-rounded fs-5">edit</span></a>'
                    . '<div class="form-check form-switch d-inline-flex align-items-center justify-content-center">'
                    . '<input class="form-check-input status-toggle" type="checkbox" role="switch" data-table="notices_notification" data-column="active_inactive" data-id="' . $n->pk . '" ' . $checked . '>'
                    . '</div>'
                    . '<div class="js-notice-delete-actions" data-id="' . $n->pk . '">'
                    . '<form id="deleteForm' . $encId . '" action="' . $deleteUrl . '" method="POST" class="d-inline js-notice-delete-enabled ' . $deleteEnabledClass . '">'
                    . csrf_field() . method_field('DELETE')
                    . '<button type="button" class="btn btn-sm btn-outline-danger btn-transparent border-0 p-0" title="Delete" aria-label="Delete Notice" onclick="deleteConfirm(\'' . $encId . '\')">'
                    . '<span class="material-symbols-rounded fs-5">delete</span></button>'
                    . '</form>'
                    . '<button class="btn btn-sm btn-outline-secondary btn-transparent border-0 p-0 js-notice-delete-disabled ' . $deleteDisabledClass . '" disabled title="Delete Disabled">'
                    . '<span class="material-symbols-rounded fs-5">block</span></button>'
                    . '</div>'
                    . '</div>';
            })
            ->rawColumns(['notice_title', 'notice_type', 'status', 'action'])
            ->make(true);
    }

    // Courses dropdown
    $courses = CourseMaster::select('pk','course_name')->where('active_inactive', 1)->where('end_date', '>=', now()->toDateString())->get();

    return view('admin.NoticeNotification.index', compact('courses','types'));
}


    // Create Page
    public function create()
    {
        $types = ['Course notice','Office order','Personal','Office notice','Service related'];
        $target = ['Office trainee','Staff/Faculty','All'];

        return view('admin.NoticeNotification.create', compact('types','target'));
    }

    // Insert
   // Insert
public function store(Request $request)
{
    $request->validate([
        'notice_title'    => 'required|string|max:255',
        'description'     => 'required|string',
        'notice_type'     => 'required|string',
        'display_date'    => 'required|date',
        'expiry_date'     => 'required|date|after_or_equal:display_date',

        // ✅ ONLY JPG, PNG, PDF
        'document'        => 'nullable|file|mimetypes:image/jpeg,image/png,application/pdf|max:5048',

        'target_audience' => 'required|string',
    ], [
        'notice_title.required'      => 'Please enter notice title.',
        'description.required'       => 'Please enter description.',
        'notice_type.required'       => 'Please select notice type.',
        'display_date.required'      => 'Please select display date.',
        'expiry_date.required'       => 'Please select expiry date.',
        'expiry_date.after_or_equal' => 'Expiry date must be equal or greater than display date.',

        'document.file'       => 'Uploaded file is not valid.',
        'document.mimetypes'  => 'Unsupported file format. Only JPG, PNG and PDF files are allowed.',
        'document.max'        => 'File size must not exceed 2 MB.',

        'target_audience.required'   => 'Please select target audience.',
    ]);

   if ($request->filled('course_master_pk')) {
    $request->validate([
        'course_master_pk' => 'required|exists:course_master,pk',
    ], [
        'course_master_pk.required' => 'Please select a valid course.',
        'course_master_pk.exists'   => 'Selected course does not exist.',
    ]);
}


    $data = $request->all();
    $data['created_by'] = Auth::id();

    if ($request->hasFile('document')) {
        $data['document'] = $request->file('document')
                                    ->store('notice_docs', 'public');
    }

    Notice::create($data);

    return redirect()
        ->route('admin.notice.index')
        ->with('success', 'Notice created successfully!');
}


    // Edit Page
    public function edit($encId)
    {
        $id = Crypt::decrypt($encId);
        $notice = Notice::findOrFail($id);
// print_r($notice); exit;
        $types = ['Course notice','Office order','Personal','Office notice','Service related'];
        $target = ['Office trainee','Staff/Faculty','All'];

        return view('admin.NoticeNotification.edit', compact('notice','types','target','encId'));
    }

    // Update
   public function update(Request $request, $encId)
{
    // print_r($request->all()); exit;
    $request->validate([
        'notice_title'      => 'required|string|max:255',
        'description'       => 'required|string',
        'notice_type'       => 'required|string',
        'display_date'      => 'required|date',
        'expiry_date'       => 'required|date|after_or_equal:display_date',
        'document'          => 'nullable|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        'target_audience'   => 'required|string',
    ]);
     if ($request->filled('course_master_pk')) {
    $request->validate([
        'course_master_pk' => 'required|exists:course_master,pk',
    ], [
        'course_master_pk.required' => 'Please select a valid course.',
        'course_master_pk.exists'   => 'Selected course does not exist.',
    ]);
}

    $id = Crypt::decrypt($encId);
    $notice = Notice::findOrFail($id);

    $data = $request->all();

    if($request->hasFile('document')){
        // $data['document'] = $request->file('document')->store('public/notice_docs');
         $file = $request->file('document');
        $path = $file->store('notice_docs', 'public');
        $data['document'] = $path;
    }

    $notice->update($data);

    return redirect()->route('admin.notice.index')->with('success','Notice updated!');
}


    // Delete
    public function destroy($encId)
    {
        $id = Crypt::decrypt($encId);
        $data = Notice::findOrFail($id);
        if($data->active_inactive ==0){
        Notice::findOrFail($id)->delete();
        return back()->with('success','Notice deleted!');
        }else{
        return back()->with('error','Active Notice cannot be deleted!');
        }    
    }
public function getCourses()
{
    // Course model ko aapke DB name ke according adjust karein
    $courses = CourseMaster::where('active_inactive', 1)
                        ->where('end_date', '>=', date('Y-m-d'))
                     ->orderBy('course_name', 'ASC')
                     ->get(['pk','course_name']);

    return response()->json([
        'status' => true,
        'data' => $courses
    ]);
}

   

}
