<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
    
use Illuminate\Http\Request;
use App\Models\Notice;
use App\Models\CourseMaster;
use Illuminate\Support\Facades\Crypt;
use Auth;

class NoticeController extends Controller
{
    // Notice List Page
    public function index()
    {
        $notices = Notice::orderBy('pk','DESC')->paginate(10);
        return view('admin.notice.index', compact('notices'));
    }

    // Create Page
    public function create()
    {
        $types = ['Course notice','Office order','Personal','Office notice','Service related'];
        $target = ['Office trainee','Staff/Faculty','Group wise'];

        return view('admin.notice.create', compact('types','target'));
    }

    // Insert
   // Insert
public function store(Request $request)
{
    $request->validate([
        'notice_title'      => 'required|string|max:255',
        'description'       => 'required|string',
        'notice_type'       => 'required|string',
        'display_date'      => 'required|date',
        'expiry_date'       => 'required|date|after_or_equal:display_date',
        'document'          => 'nullable|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        'target_audience'   => 'required|string',
    ], [
        'notice_title.required'     => 'Please enter notice title.',
        'description.required'      => 'Please enter description.',
        'notice_type.required'      => 'Please select notice type.',
        'display_date.required'     => 'Please select display date.',
        'expiry_date.required'      => 'Please select expiry date.',
        'expiry_date.after_or_equal'=> 'Expiry date must be equal or greater than display date.',
        'document.mimes'            => 'Only JPG, PNG, PDF, DOC files allowed.',
        'document.max'              => 'File size must not exceed 2 MB.',
        'target_audience.required'  => 'Please select target audience.',
    ]);

    $data = $request->all();
    $data['created_by'] = Auth::id();

    // File Upload
    if($request->hasFile('document')){
        $data['document'] = $request->file('document')->store('notice_docs');
    }

    Notice::create($data);

    return redirect()->route('admin.notice.index')->with('success','Notice created successfully!');
}


    // Edit Page
    public function edit($encId)
    {
        $id = Crypt::decrypt($encId);
        $notice = Notice::findOrFail($id);
// print_r($notice); exit;
        $types = ['Course notice','Office order','Personal','Office notice','Service related'];
        $target = ['Office trainee','Staff/Faculty','Group wise'];

        return view('admin.notice.edit', compact('notice','types','target','encId'));
    }

    // Update
   public function update(Request $request, $encId)
{
    $request->validate([
        'notice_title'      => 'required|string|max:255',
        'description'       => 'required|string',
        'notice_type'       => 'required|string',
        'display_date'      => 'required|date',
        'expiry_date'       => 'required|date|after_or_equal:display_date',
        'document'          => 'nullable|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        'target_audience'   => 'required|string',
    ]);

    $id = Crypt::decrypt($encId);
    $notice = Notice::findOrFail($id);

    $data = $request->all();

    if($request->hasFile('document')){
        $data['document'] = $request->file('document')->store('notice_docs');
    }

    $notice->update($data);

    return redirect()->route('admin.notice.index')->with('success','Notice updated!');
}


    // Delete
    public function destroy($encId)
    {
        $id = Crypt::decrypt($encId);
        Notice::findOrFail($id)->delete();

        return back()->with('success','Notice deleted!');
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
