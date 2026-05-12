<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NoticeCategoryMaster;
use App\Models\NoticeNotification as Notice;
use App\Models\NoticeSubcategoryMaster;
use App\Models\CourseMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class NoticeNotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notice::with(['course', 'user', 'noticeCategory', 'noticeSubcategory'])->orderBy('pk', 'DESC');

        if ($request->filled('notice_category_master_pk')) {
            $query->where('notice_category_master_pk', $request->notice_category_master_pk);
        } elseif ($request->filled('notice_type')) {
            $query->where('notice_type', $request->notice_type);
        }

        if ($request->course_id) {
            $query->where('course_master_pk', $request->course_id);
        }

        if ($request->status != '') {
            $query->where('active_inactive', $request->status);
        }

        $notices = $query->paginate(10)->appends($request->all());

        $courses = CourseMaster::select('pk', 'course_name')
            ->where('active_inactive', 1)
            ->where('end_date', '>=', now())
            ->get();

        $categories = NoticeCategoryMaster::where('active_inactive', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.NoticeNotification.index', compact('notices', 'courses', 'categories'));
    }

    /**
     * Full notice list for the current user (same scope as dashboard), grouped by category tabs.
     */
    public function feed(Request $request)
    {
        $notices = collect(get_notice_notification_by_role())->unique('pk');
        $q = trim((string) $request->get('q', ''));

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $notices = $notices->filter(function ($n) use ($needle) {
                $hay = mb_strtolower(implode(' ', array_filter([
                    (string) ($n->notice_title ?? ''),
                    (string) ($n->description ?? ''),
                    (string) ($n->notice_type ?? ''),
                    (string) ($n->category_name ?? ''),
                    (string) ($n->subcategory_name ?? ''),
                ])));

                return str_contains($hay, $needle);
            })->values();
        }

        $creatorNames = [];
        $creatorPks = $notices->pluck('created_by')->filter()->unique()->values();
        if ($creatorPks->isNotEmpty() && Schema::hasTable('user_credentials')) {
            $select = ['pk', 'first_name', 'last_name'];
            $hasUserName = Schema::hasColumn('user_credentials', 'user_name');
            if ($hasUserName) {
                $select[] = 'user_name';
            }
            $rows = DB::table('user_credentials')
                ->whereIn('pk', $creatorPks)
                ->get($select);
            foreach ($rows as $row) {
                $full = trim(((string) ($row->first_name ?? '')).' '.((string) ($row->last_name ?? '')));
                if ($full !== '') {
                    $creatorNames[$row->pk] = $full;
                    continue;
                }
                $login = $hasUserName ? trim((string) ($row->user_name ?? '')) : '';
                $creatorNames[$row->pk] = $login !== '' ? $login : '—';
            }
        }

        foreach ($notices as $n) {
            $pk = $n->created_by ?? null;
            $n->creator_display = ($pk && isset($creatorNames[$pk])) ? $creatorNames[$pk] : '—';
        }

        $noticeCategoryTabs = $notices->isEmpty()
            ? collect()
            : $notices->groupBy(function ($n) {
                if (!empty($n->notice_category_master_pk)) {
                    return 'c:'.$n->notice_category_master_pk;
                }

                return 'leg:'.md5((string) ($n->notice_type ?? 'other'));
            })->map(function ($items, $tabKey) {
                $first = $items->first();
                $label = $first->category_name ?? $first->notice_type ?? 'Other';
                $sorted = $items->sortByDesc(function ($row) {
                    return $row->display_date ?? $row->created_at ?? '';
                })->values();

                return [
                    'key' => $tabKey,
                    'label' => $label,
                    'sort' => (int) ($first->category_sort_order ?? 99999),
                    'total' => $sorted->count(),
                    'notices' => $sorted,
                ];
            })->sortBy('sort')->values();

        $activeTabKey = (string) $request->get('tab', '');
        $firstTab = $noticeCategoryTabs->first();
        if ($activeTabKey === '' || $noticeCategoryTabs->firstWhere('key', $activeTabKey) === null) {
            $activeTabKey = $firstTab ? (string) $firstTab['key'] : '';
        }

        return view('admin.NoticeNotification.feed', [
            'noticeCategoryTabs' => $noticeCategoryTabs,
            'activeTabKey' => $activeTabKey,
            'q' => $q,
        ]);
    }

    public function create()
    {
        $categories = NoticeCategoryMaster::where('active_inactive', 1)
            ->with(['subCategories' => function ($q) {
                $q->where('active_inactive', 1)->orderBy('sort_order')->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $target = ['Office trainee', 'Staff/Faculty', 'All'];

        return view('admin.NoticeNotification.create', compact('categories', 'target'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'notice_title' => 'required|string|max:255',
            'description' => 'required|string',
            'notice_category_master_pk' => [
                'required',
                Rule::exists('notice_category_master', 'pk')->where(function ($q) {
                    $q->where('active_inactive', 1);
                }),
            ],
            'notice_subcategory_master_pk' => [
                'nullable',
                'exists:notice_subcategory_master,pk',
                Rule::exists('notice_subcategory_master', 'pk')->where(function ($q) use ($request) {
                    $q->where('notice_category_master_pk', $request->notice_category_master_pk)
                        ->where('active_inactive', 1);
                }),
            ],
            'display_date' => 'required|date',
            'expiry_date' => 'required|date|after_or_equal:display_date',
            'document' => 'nullable|file|mimetypes:image/jpeg,image/png,application/pdf|max:5048',
            'target_audience' => 'required|string',
        ], [
            'notice_title.required' => 'Please enter notice title.',
            'description.required' => 'Please enter description.',
            'notice_category_master_pk.required' => 'Please select notice category.',
            'display_date.required' => 'Please select display date.',
            'expiry_date.required' => 'Please select expiry date.',
            'expiry_date.after_or_equal' => 'Expiry date must be equal or greater than display date.',
            'document.file' => 'Uploaded file is not valid.',
            'document.mimetypes' => 'Unsupported file format. Only JPG, PNG and PDF files are allowed.',
            'target_audience.required' => 'Please select target audience.',
        ]);

        if ($request->filled('course_master_pk')) {
            $request->validate([
                'course_master_pk' => 'required|exists:course_master,pk',
            ], [
                'course_master_pk.required' => 'Please select a valid course.',
                'course_master_pk.exists' => 'Selected course does not exist.',
            ]);
        }

        $category = NoticeCategoryMaster::where('pk', $request->notice_category_master_pk)
            ->where('active_inactive', 1)
            ->firstOrFail();

        $data = [
            'notice_title' => $request->notice_title,
            'description' => $request->description,
            'notice_category_master_pk' => (int) $request->notice_category_master_pk,
            'notice_subcategory_master_pk' => $request->filled('notice_subcategory_master_pk')
                ? (int) $request->notice_subcategory_master_pk
                : null,
            'notice_type' => $category->name,
            'display_date' => $request->display_date,
            'expiry_date' => $request->expiry_date,
            'target_audience' => $request->target_audience,
            'created_by' => Auth::id(),
            'active_inactive' => 1,
            'course_master_pk' => $request->target_audience === 'Office trainee'
                ? $request->input('course_master_pk')
                : null,
        ];

        if ($request->hasFile('document')) {
            $data['document'] = $request->file('document')->store('notice_docs', 'public');
        }

        Notice::create($data);

        return redirect()
            ->route('admin.notice.index')
            ->with('success', 'Notice created successfully!');
    }

    public function edit($encId)
    {
        $id = Crypt::decrypt($encId);
        $notice = Notice::findOrFail($id);

        $resolvedCategoryPk = $notice->notice_category_master_pk;
        if (!$resolvedCategoryPk && $notice->notice_type) {
            $resolvedCategoryPk = NoticeCategoryMaster::where('name', $notice->notice_type)->value('pk');
        }

        $categories = NoticeCategoryMaster::where('active_inactive', 1)
            ->with(['subCategories' => function ($q) {
                $q->where('active_inactive', 1)->orderBy('sort_order')->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $target = ['Office trainee', 'Staff/Faculty', 'All'];

        return view('admin.NoticeNotification.edit', compact('notice', 'categories', 'target', 'encId', 'resolvedCategoryPk'));
    }

    public function update(Request $request, $encId)
    {
        $request->validate([
            'notice_title' => 'required|string|max:255',
            'description' => 'required|string',
            'notice_category_master_pk' => [
                'required',
                Rule::exists('notice_category_master', 'pk')->where(function ($q) {
                    $q->where('active_inactive', 1);
                }),
            ],
            'notice_subcategory_master_pk' => [
                'nullable',
                'exists:notice_subcategory_master,pk',
                Rule::exists('notice_subcategory_master', 'pk')->where(function ($q) use ($request) {
                    $q->where('notice_category_master_pk', $request->notice_category_master_pk)
                        ->where('active_inactive', 1);
                }),
            ],
            'display_date' => 'required|date',
            'expiry_date' => 'required|date|after_or_equal:display_date',
            'document' => 'nullable|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
            'target_audience' => 'required|string',
        ]);

        if ($request->filled('course_master_pk')) {
            $request->validate([
                'course_master_pk' => 'required|exists:course_master,pk',
            ], [
                'course_master_pk.required' => 'Please select a valid course.',
                'course_master_pk.exists' => 'Selected course does not exist.',
            ]);
        }

        $id = Crypt::decrypt($encId);
        $notice = Notice::findOrFail($id);

        $category = NoticeCategoryMaster::where('pk', $request->notice_category_master_pk)
            ->where('active_inactive', 1)
            ->firstOrFail();

        $data = [
            'notice_title' => $request->notice_title,
            'description' => $request->description,
            'notice_category_master_pk' => (int) $request->notice_category_master_pk,
            'notice_subcategory_master_pk' => $request->filled('notice_subcategory_master_pk')
                ? (int) $request->notice_subcategory_master_pk
                : null,
            'notice_type' => $category->name,
            'display_date' => $request->display_date,
            'expiry_date' => $request->expiry_date,
            'target_audience' => $request->target_audience,
            'course_master_pk' => $request->target_audience === 'Office trainee'
                ? $request->input('course_master_pk')
                : null,
        ];

        if ($request->hasFile('document')) {
            $data['document'] = $request->file('document')->store('notice_docs', 'public');
        }

        $notice->update($data);

        return redirect()->route('admin.notice.index')->with('success', 'Notice updated!');
    }

    public function destroy($encId)
    {
        $id = Crypt::decrypt($encId);
        $data = Notice::findOrFail($id);
        if ($data->active_inactive == 0) {
            Notice::findOrFail($id)->delete();

            return back()->with('success', 'Notice deleted!');
        }

        return back()->with('error', 'Active Notice cannot be deleted!');
    }

    public function getCourses()
    {
        $courses = CourseMaster::where('active_inactive', 1)
            ->where('end_date', '>=', date('Y-m-d'))
            ->orderBy('course_name', 'ASC')
            ->get(['pk', 'course_name']);

        return response()->json([
            'status' => true,
            'data' => $courses,
        ]);
    }

    public function getSubcategoriesByCategory(int $categoryId)
    {
        $items = NoticeSubcategoryMaster::query()
            ->where('notice_category_master_pk', $categoryId)
            ->where('active_inactive', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['pk', 'name']);

        return response()->json([
            'status' => true,
            'data' => $items,
        ]);
    }
}
