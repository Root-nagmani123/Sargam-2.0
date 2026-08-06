<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
    
use Illuminate\Http\Request;
use App\Models\NoticeNotification as Notice;
use App\Models\CourseMaster;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Auth;

class NoticeNotificationController extends Controller
{
    // Notice List Page
   public function index(Request $request)
{
    $types = ['Course notice','Office order','Personal','Office notice','Service related'];
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

    // Pagination with filters
    $notices = $query->paginate(10)->appends($request->all());

    // Courses dropdown
    $courses = CourseMaster::select('pk','course_name')->where('active_inactive', 1)->where('end_date', '>=', now()->toDateString())->get();

    return view('admin.NoticeNotification.index', compact('notices','courses','types'));
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

    $notice = DB::transaction(fn () => Notice::create($data));

    $this->sendNoticeNotifications(
        $data['target_audience'] ?? 'All',
        $data['course_master_pk'] ?? null,
        $data['notice_title'] ?? 'New Notice',
        (int) $notice->pk
    );

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

    DB::transaction(fn () => $notice->update($data));

    // Only re-alert recipients when something they'd actually care about moved. Editing the
    // body or swapping the attachment must not re-notify everyone — the feed always shows
    // the current version. (Previously every save re-blasted a "New Notice" to all recipients.)
    if ($notice->wasChanged(self::NOTIFIABLE_NOTICE_FIELDS)) {
        $this->sendNoticeNotifications(
            $notice->target_audience ?? 'All',
            $notice->course_master_pk !== null ? (int) $notice->course_master_pk : null,
            $notice->notice_title ?? 'Updated Notice',
            (int) $notice->pk,
            true
        );
    }

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
    $courses = CourseMaster::where('active_inactive', 1)
                        ->where('end_date', '>=', date('Y-m-d'))
                     ->orderBy('course_name', 'ASC')
                     ->get(['pk','course_name']);

    return response()->json([
        'status' => true,
        'data' => $courses
    ]);
}

    /**
     * Resolve recipients and send in-app notifications for a new/updated notice.
     *
     * Never allowed to break the notice save: the notice is already committed by the time
     * this runs, so a fan-out failure is logged and swallowed rather than 500-ing a
     * successful save. $isUpdate only changes the wording — callers decide whether to
     * notify at all.
     */
    private function sendNoticeNotifications(
        string $targetAudience,
        ?int $coursePk,
        string $noticeTitle,
        int $noticePk,
        bool $isUpdate = false
    ): void {
        try {
            $senderUserId = Auth::user()?->user_id;
            $recipientIds = $this->resolveNoticeRecipients($targetAudience, $coursePk);

            if (empty($recipientIds)) {
                return;
            }

            // Exclude the creator from receiving their own notification. Both sides are cast:
            // resolve* returns ints, but user_id can arrive as a numeric string.
            if ($senderUserId !== null) {
                $senderUserId = (int) $senderUserId;
                $recipientIds = array_filter($recipientIds, fn ($id) => (int) $id !== $senderUserId);
            }

            $recipientIds = array_values(array_unique($recipientIds));

            if (empty($recipientIds)) {
                return;
            }

            notification()->createMultiple(
                $recipientIds,
                'notice',
                'Notice',
                $noticePk,
                $isUpdate ? 'Notice Updated' : 'New Notice',
                $noticeTitle,
                $senderUserId
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Fields whose change is worth re-notifying recipients about. Editing anything else
     * (typo in the body, a document swap) must not re-alert everyone — see the notice
     * feed, which always shows the current version anyway.
     */
    private const NOTIFIABLE_NOTICE_FIELDS = [
        'notice_title',
        'target_audience',
        'course_master_pk',
        'display_date',
        'expiry_date',
    ];

    private function resolveNoticeRecipients(string $targetAudience, ?int $coursePk): array
    {
        $audience = strtolower(trim($targetAudience));

        if (str_contains($audience, 'office trainee') && $coursePk) {
            // Students enrolled in the specific course
            return DB::table('student_master_course__map as smcm')
                ->join('user_credentials as uc', function ($join) {
                    $join->on('uc.user_id', '=', 'smcm.student_master_pk')
                         ->where('uc.user_category', 'S');
                })
                ->where('smcm.course_master_pk', $coursePk)
                ->pluck('uc.user_id')
                ->map(fn($id) => (int) $id)
                ->toArray();
        }

        if (str_contains($audience, 'staff') || str_contains($audience, 'faculty')) {
            // All active employee and faculty portal users
            return DB::table('user_credentials')
                ->whereIn('user_category', ['E', 'F'])
                ->where('active_inactive', 1)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->map(fn($id) => (int) $id)
                ->toArray();
        }

        // 'All' — everyone except students.
        //
        // NOTE: this must NOT be a bare where('user_category', '!=', 'S'). SQL three-valued
        // logic makes NULL != 'S' evaluate to NULL rather than TRUE, so that form silently
        // drops every NULL-category user — the large majority of this table — even though
        // 'All' notices are visible to them in the feed. Keep the explicit NULL branch.
        return DB::table('user_credentials')
            ->where(function ($q) {
                $q->whereNull('user_category')
                  ->orWhere('user_category', '!=', 'S');
            })
            ->where('active_inactive', 1)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    }

}
