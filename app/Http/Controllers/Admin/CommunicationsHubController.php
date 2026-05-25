<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeMaster;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommunicationsHubController extends Controller
{
    private const SECTIONS = ['notifications', 'notices', 'birthdays', 'wishes'];

    public function index(Request $request)
    {
        $section = (string) $request->get('section', 'notifications');
        if (! in_array($section, self::SECTIONS, true)) {
            $section = 'notifications';
        }

        $user = Auth::user();
        $userId = (int) ($user->user_id ?? 0);
        $q = trim((string) $request->get('q', ''));
        $isAdminSummary = hasRole('Admin');
        $daysOld = $isAdminSummary ? 10 : null;

        $notifications = collect();
        $notificationUnreadCount = 0;
        if ($userId > 0) {
            $notifications = Notification::query()
                ->where('receiver_user_id', $userId)
                ->where(function ($query) {
                    $query->where('type', '!=', 'birthday')
                        ->where('type', '!=', 'birthday_reply');
                })
                ->when($daysOld !== null, fn ($query) => $query->where('created_at', '>=', now()->subDays($daysOld)))
                ->orderByDesc('created_at')
                ->limit(200)
                ->get();

            $this->attachSenderDisplay($notifications);
            $notificationUnreadCount = notification()->getUnreadCount($userId, $daysOld);

            if ($q !== '') {
                $notifications = $this->filterItemsByQuery($notifications, $q, ['title', 'message', 'sender_display']);
            }
        }

        $wishes = collect();
        if ($userId > 0) {
            $wishes = Notification::query()
                ->where('receiver_user_id', $userId)
                ->where('type', 'birthday')
                ->where(function ($query) {
                    $query->where('module_name', 'BirthdayWish')
                        ->orWhereNull('module_name');
                })
                ->orderByDesc('created_at')
                ->limit(200)
                ->get();

            $this->attachSenderDisplay($wishes);

            if ($q !== '') {
                $wishes = $this->filterItemsByQuery($wishes, $q, ['title', 'message', 'sender_display']);
            }
        }

        $noticeFeed = $this->buildNoticeFeedData($request, $q);
        $birthdays = $this->buildBirthdayData($userId, $q);

        $sectionCounts = [
            'notifications' => $notifications->count(),
            'notices' => $noticeFeed['totalNotices'],
            'birthdays' => $birthdays['today']->count() + $birthdays['upcoming']->count(),
            'wishes' => $wishes->count(),
        ];

        $sectionTitles = [
            'notifications' => 'Notifications',
            'notices' => 'Notices',
            'birthdays' => 'Birthdays',
            'wishes' => 'Wishes',
        ];

        return view('admin.communications.hub', [
            'activeSection' => $section,
            'sectionCounts' => $sectionCounts,
            'sectionTitles' => $sectionTitles,
            'q' => $q,
            'notifications' => $notifications,
            'notificationUnreadCount' => $notificationUnreadCount,
            'wishes' => $wishes,
            'noticeCategoryTabs' => $noticeFeed['noticeCategoryTabs'],
            'activeNoticeTabKey' => $noticeFeed['activeTabKey'],
            'highlightNoticePk' => $noticeFeed['highlightNoticePk'],
            'todayBirthdays' => $birthdays['today'],
            'upcomingBirthdays' => $birthdays['upcoming'],
        ]);
    }

    private function buildNoticeFeedData(Request $request, string $q): array
    {
        $notices = collect(get_notice_notification_by_role())->unique('pk');

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
                if (! empty($n->notice_category_master_pk)) {
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

        $highlightNoticePk = null;
        if ($request->filled('notice')) {
            $n = (int) $request->query('notice');
            $highlightNoticePk = $n > 0 ? $n : null;
        }

        $activeTabKey = (string) $request->get('tab', '');
        $firstTab = $noticeCategoryTabs->first();

        if ($highlightNoticePk !== null && $noticeCategoryTabs->isNotEmpty()) {
            foreach ($noticeCategoryTabs as $tab) {
                foreach ($tab['notices'] as $row) {
                    if ((int) ($row->pk ?? 0) === $highlightNoticePk) {
                        $activeTabKey = (string) $tab['key'];
                        break 2;
                    }
                }
            }
        }

        if ($activeTabKey === '' || $noticeCategoryTabs->firstWhere('key', $activeTabKey) === null) {
            $activeTabKey = $firstTab ? (string) $firstTab['key'] : '';
        }

        return [
            'noticeCategoryTabs' => $noticeCategoryTabs,
            'activeTabKey' => $activeTabKey,
            'highlightNoticePk' => $highlightNoticePk,
            'totalNotices' => $notices->count(),
        ];
    }

    private function buildBirthdayData(int $userId, string $q): array
    {
        $today = EmployeeMaster::query()
            ->where('status', 1)
            ->whereRaw("DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')")
            ->when($userId > 0, fn ($query) => $query->where('employee_master.pk', '!=', $userId))
            ->leftJoin('designation_master', 'employee_master.designation_master_pk', '=', 'designation_master.pk')
            ->select(
                'employee_master.pk',
                'employee_master.first_name',
                'employee_master.last_name',
                'employee_master.email',
                'employee_master.mobile',
                'employee_master.profile_picture',
                'designation_master.designation_name'
            )
            ->get();

        $upcoming = collect();
        for ($i = 1; $i <= 30; $i++) {
            $futureDate = now()->addDays($i);
            $rows = EmployeeMaster::query()
                ->where('status', 1)
                ->whereRaw("DATE_FORMAT(dob, '%m-%d') = ?", [$futureDate->format('m-d')])
                ->when($userId > 0, fn ($query) => $query->where('employee_master.pk', '!=', $userId))
                ->leftJoin('designation_master', 'employee_master.designation_master_pk', '=', 'designation_master.pk')
                ->select(
                    'employee_master.pk',
                    'employee_master.first_name',
                    'employee_master.last_name',
                    'employee_master.profile_picture',
                    'employee_master.dob',
                    'designation_master.designation_name'
                )
                ->get()
                ->each(function ($emp) use ($futureDate) {
                    $emp->birthday_date = $futureDate->format('d M');
                    $emp->days_away = $futureDate->diffInDays(now());
                });
            $upcoming = $upcoming->merge($rows);
        }

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $filterPerson = function ($person) use ($needle) {
                $name = mb_strtolower(trim(($person->first_name ?? '').' '.($person->last_name ?? '')));
                $designation = mb_strtolower((string) ($person->designation_name ?? ''));

                return str_contains($name, $needle) || str_contains($designation, $needle);
            };
            $today = $today->filter($filterPerson)->values();
            $upcoming = $upcoming->filter($filterPerson)->values();
        }

        return [
            'today' => $today,
            'upcoming' => $upcoming,
        ];
    }

    private function attachSenderDisplay($items): void
    {
        $senderPks = collect($items)->pluck('sender_user_id')->filter()->unique()->values();
        if ($senderPks->isEmpty()) {
            return;
        }

        $names = EmployeeMaster::query()
            ->whereIn('pk', $senderPks)
            ->get(['pk', 'first_name', 'last_name'])
            ->mapWithKeys(function ($row) {
                $full = trim(($row->first_name ?? '').' '.($row->last_name ?? ''));

                return [$row->pk => $full !== '' ? $full : '—'];
            });

        foreach ($items as $item) {
            $pk = $item->sender_user_id ?? null;
            $item->sender_display = ($pk && isset($names[$pk])) ? $names[$pk] : '—';
        }
    }

    private function filterItemsByQuery($items, string $q, array $fields)
    {
        $needle = mb_strtolower($q);

        return $items->filter(function ($item) use ($needle, $fields) {
            $parts = [];
            foreach ($fields as $field) {
                $parts[] = (string) ($item->{$field} ?? '');
            }

            return str_contains(mb_strtolower(implode(' ', $parts)), $needle);
        })->values();
    }
}
