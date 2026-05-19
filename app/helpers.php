<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;


/**
 * Get all employee ids (pk and pk_old) that represent the given user/employee id.
 * employee_master: pk and pk_old both identify the same employee.
 *
 * @param int|string $userId
 * @return array
 */
function getEmployeeIdsForUser($userId)
{
    if ($userId === null || $userId === '') {
        return [];
    }
    $userId = (string) $userId;
    $ids = [$userId];
    if (Schema::hasTable('employee_master') && Schema::hasColumn('employee_master', 'pk_old')) {
        $row = DB::table('employee_master')
            ->where('pk', $userId)
            ->orWhere('pk_old', $userId)
            ->select('pk', 'pk_old')
            ->first();
        if ($row) {
            $ids = array_filter(array_unique([$row->pk, $row->pk_old]));
        }
    }
    return array_map('strval', $ids);
}

function view_file_link($path)
{
    return $path ? asset('storage/' . $path) : null;
}

function format_date($date, $format = 'd-m-Y')
{
     if (empty($date) || $date == '0000-00-00') {
        return '-'; // or return '';
    }
    return \Carbon\Carbon::parse($date)->format($format);
}

/**
 * Core buyer name for Sale Voucher CLIENT TYPE line (e.g. strip trailing "(Officers Mess)" from the voucher label).
 */
function mess_buyer_core_name_for_client_type(?string $buyerName): string
{
    $name = trim((string) $buyerName);
    if ($name === '') {
        return '';
    }
    $stripped = preg_replace('/\s*\([^)]*\)\s*$/u', '', $name);
    $stripped = trim((string) $stripped);

    return $stripped !== '' ? $stripped : $name;
}

/**
 * Left part of CLIENT TYPE on Sale Voucher category-wise slip: type label + (buyer core, mess client category e.g. Faculty).
 * Course/OT keep category out of parentheses; course name is appended separately as [Course].
 */
function mess_category_wise_client_type_line_base(
    string $clientTypeLabel,
    string $slug,
    string $buyerName,
    ?string $messClientCategoryName
): string {
    $cat = trim((string) $messClientCategoryName);
    $cat = $cat === '' ? null : $cat;

    if ($slug === 'employee') {
        if ($cat !== null) {
            return $clientTypeLabel . ' (' . $cat . ')';
        }

        return $clientTypeLabel;
    }

    if ($cat !== null && ! in_array($slug, ['course', 'ot'], true)) {
        return $clientTypeLabel . ' (' . $cat . ')';
    }

    return $clientTypeLabel;
}

/**
 * Combined bill slip / invoice number (Process Mess Bills & Sale Voucher Report).
 * Format: CB-YYYYMMDD-XXXXX (deterministic per buyer + client type; date is current day).
 */
function mess_combined_bill_slip_no(string $buyerName, string $clientTypeSlug): string
{
    $seed = trim($buyerName) . '|' . $clientTypeSlug;
    $num = abs(crc32($seed)) % 100000;

    return 'CB-' . date('Ymd') . '-' . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
}

/**
 * One buyer-section on the Sale Voucher Report may contain multiple voucher records that share the same slip no.
 * Flatten to display rows and sort by line request date (item issue_date, else voucher issue_date) descending.
 *
 * @param  \Illuminate\Support\Collection<int, mixed>  $sectionVouchers
 * @return \Illuminate\Support\Collection<int, object{kind: string, voucher: mixed, item?: mixed, sortDate: mixed, sortId: int}>
 */
function mess_cw_slip_section_display_rows(\Illuminate\Support\Collection $sectionVouchers): \Illuminate\Support\Collection
{
    $rows = collect();
    foreach ($sectionVouchers as $voucher) {
        $voucherRequestDate = $voucher->issue_date ?? null;
        if ($voucher->items->isEmpty()) {
            $rows->push((object) [
                'kind' => 'empty',
                'voucher' => $voucher,
                'sortDate' => $voucherRequestDate,
                'sortId' => (int) $voucher->getKey(),
            ]);
            continue;
        }
        foreach ($voucher->items as $item) {
            $rows->push((object) [
                'kind' => 'item',
                'voucher' => $voucher,
                'item' => $item,
                'sortDate' => $item->issue_date ?? $voucherRequestDate,
                'sortId' => (int) $item->getKey(),
            ]);
        }
    }

    return $rows->sort(function ($a, $b) {
        $dateA = mess_cw_slip_row_display_date($a);
        $dateB = mess_cw_slip_row_display_date($b);
        $tsA = mess_cw_slip_display_date_timestamp($dateA);
        $tsB = mess_cw_slip_display_date_timestamp($dateB);
        if ($tsA !== $tsB) {
            return $tsB <=> $tsA;
        }

        return $b->sortId <=> $a->sortId;
    })->values();
}

/**
 * Request-date label shown in the Sale Voucher Report table for one display row.
 */
function mess_cw_slip_row_display_date(object $row): string
{
    $voucher = $row->voucher;
    if ($row->kind === 'empty') {
        return $voucher->issue_date
            ? ($voucher->issue_date instanceof \Carbon\Carbon
                ? $voucher->issue_date->format('d-m-Y')
                : \Carbon\Carbon::parse($voucher->issue_date)->format('d-m-Y'))
            : 'N/A';
    }

    $item = $row->item;
    $requestDate = $voucher->issue_date
        ? ($voucher->issue_date instanceof \Carbon\Carbon
            ? $voucher->issue_date->format('d-m-Y')
            : \Carbon\Carbon::parse($voucher->issue_date)->format('d-m-Y'))
        : 'N/A';
    $itemIssueDate = $item->issue_date ?? null;
    if ($itemIssueDate) {
        return $itemIssueDate instanceof \Carbon\Carbon
            ? $itemIssueDate->format('d-m-Y')
            : \Carbon\Carbon::parse($itemIssueDate)->format('d-m-Y');
    }

    return $requestDate;
}

function mess_cw_slip_display_date_timestamp(string $displayDate): int
{
    if ($displayDate === '' || $displayDate === 'N/A') {
        return 0;
    }

    try {
        return \Carbon\Carbon::createFromFormat('d-m-Y', $displayDate)->startOfDay()->timestamp;
    } catch (\Throwable $e) {
        return 0;
    }
}

/**
 * Remark label for a date group (unique voucher remarks merged when same date).
 *
 * @param  string[]  $remarks
 */
function mess_cw_slip_remark_for_date_group(string $displayDate, array $remarks): string
{
    $unique = array_values(array_unique(array_filter(array_map(
        static fn ($r) => trim((string) $r),
        $remarks
    ))));

    if ($unique === []) {
        return '—';
    }

    $text = count($unique) === 1 ? $unique[0] : implode('; ', $unique);

    if ($displayDate !== '' && $displayDate !== 'N/A') {
        return $displayDate . ' → ' . $text;
    }

    return $text;
}

/**
 * Per-row remark layout: rowspan when consecutive rows share the same display date.
 *
 * @param  \Illuminate\Support\Collection<int, object>  $displayRows
 * @return array<int, array{show: bool, rowspan: int, remark: string}>
 */
function mess_cw_slip_section_remark_layout(\Illuminate\Support\Collection $displayRows): array
{
    $layout = [];
    $count = $displayRows->count();
    $i = 0;

    while ($i < $count) {
        $displayDate = mess_cw_slip_row_display_date($displayRows[$i]);
        $remarks = [];
        $j = $i;

        while ($j < $count && mess_cw_slip_row_display_date($displayRows[$j]) === $displayDate) {
            $remarks[] = (string) ($displayRows[$j]->voucher->remarks ?? '');
            $j++;
        }

        $span = $j - $i;
        $label = mess_cw_slip_remark_for_date_group($displayDate, $remarks);

        for ($k = $i; $k < $j; $k++) {
            $layout[$k] = [
                'show' => $k === $i,
                'rowspan' => $span,
                'remark' => $label,
            ];
        }

        $i = $j;
    }

    return $layout;
}

/**
 * @deprecated Use mess_cw_slip_remark_for_date_group() via mess_cw_slip_section_remark_layout().
 */
function mess_cw_slip_voucher_remark_display($voucher, ?string $rowDateFormatted = null): string
{
    return mess_cw_slip_remark_for_date_group(
        $rowDateFormatted ?? 'N/A',
        [(string) ($voucher->remarks ?? '')]
    );
}

function createDirectory($path)
{
    $directory = public_path('storage/' . $path);
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
}

function safeDecrypt($value, $default = null)
{
    try {
        return decrypt($value);
    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
        return $default;
    }
}
function hasRole($role)
{
    $user = Auth::user();
    if (!$user) return false;

    // Step 1: Check session roles first (Student static role bhi yahi me milega)
    $sessionRoles = Session::get('user_roles', []);
    if (in_array($role, $sessionRoles)) {
        return true;
    }

    // Step 2: Check database roles + cache
    $roles = Cache::remember('user_roles_' . $user->pk, 10, function () use ($user) {
        return $user->roles()->pluck('user_role_name')->toArray();
    });

    return in_array($role, $roles);
}

/**
 * Whether the current user can access full Mess Management
 * (Mess Staff/Mess Admin role, or employee in Officers Mess department).
 */
function canSeeLowStockAlert()
{
    $user = Auth::user();
    if (!$user) return false;

    if (hasRole('Mess Staff') || hasRole('mess staff')) return true;
    if (hasRole('Mess Admin') || hasRole('mess admin')) return true;

    // Mess staff dropdown list = employees in Officers Mess department; show alert to them when they login
    if (isset($user->user_category) && $user->user_category === 'E' && !empty($user->user_id)) {
        $officersMess = \App\Models\DepartmentMaster::where('department_name', 'Officers Mess')->first();
        if ($officersMess && \App\Models\EmployeeMaster::where('pk', $user->user_id)->where('department_master_pk', $officersMess->pk)->exists()) {
            return true;
        }
    }
    return false;
}

/**
 * Setup sidebar: Mess (self-service my bills + full Mess Management when applicable).
 * Aligns with staff-facing modules such as Estate self-service roles.
 */
function canSeeMessSelfServiceSetup(): bool
{
    if (canSeeLowStockAlert()) {
        return true;
    }

    return hasRole('Staff')
        || hasRole('Student-OT')
        || hasRole('Doctor')
        || hasRole('Guest Faculty')
        || hasRole('Internal Faculty')
        || hasRole('Training-Induction')
        || hasRole('Training-MCTP')
        || hasRole('IST');
}

function get_Role_by_course()
{
    $user = Auth::user();

    // Return empty array if user is not authenticated
    if (!$user) {
        return [];
    }

    $sessionRoles = Session::get('user_roles', []);
    if (empty($sessionRoles)) {
        return [];
    }
    $cacheKey = 'role_by_course_' . $user->user_id;
    $role_course = Cache::remember($cacheKey, 600, function () use ($user, $sessionRoles) {
        return DB::table('course_master as cm')
            ->join('user_role_master as urm', 'cm.user_role_master_pk', '=', 'urm.pk')
            ->whereIn('urm.user_role_name', $sessionRoles)
            ->pluck('cm.pk')
            ->toArray();
    });
    return $role_course;
}

function service_find()
{
    $user = Auth::user();

    // Return null if user is not authenticated
    if (!$user) {
        return null;
    }

    $cacheKey = 'service_name_' . $user->user_id;

    $service_name = Cache::remember($cacheKey, 600, function () use ($user) {
        return DB::table('student_master')
            ->join('service_master', 'student_master.service_master_pk', '=', 'service_master.pk')
            ->where('student_master.pk', $user->user_id)
            ->value('service_master.service_short_name');
    });
    return $service_name;
}
function employee_designation_search()
{
    $user = Auth::user();

    // Return null if user is not authenticated
    if (!$user) {
        return null;
    }

    // print_r($user);
    $cacheKey = 'employee_designation_' . $user->user_id;
    $designation = Cache::remember($cacheKey, 600, function () use ($user) {
        return DB::table('employee_master')
            ->join('designation_master', 'employee_master.designation_master_pk', '=', 'designation_master.pk')
            ->where('employee_master.pk', $user->user_id)
            ->value('designation_master.designation_name', 'designation_master.*');
    });
    return $designation;
}
function get_profile_pic()
{
    $user = Auth::user();

    // Return default image if user is not authenticated
    if (!$user) {
        return 'https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
    }

    $cacheKey = 'profile_pic_' . $user->user_id;
    if ($user->user_category == 'S') {

        $profile_pic = Cache::remember($cacheKey, 600, function () use ($user) {

            $data = DB::table('student_master')
                ->where('pk', $user->user_id)
                ->value('photo_path');

            if ($data == null) {
                return 'https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
            } else {
                return asset('storage/form-uploads/photo/' . $data);
            }
        });

        return $profile_pic;
    } else {
        $profile_pic = Cache::remember($cacheKey, 600, function () use ($user) {
            $data = DB::table('employee_master')
                ->where('employee_master.pk', $user->user_id)
                ->value('profile_picture');
            if ($data == null) {
                return 'https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
            } else {
                // return 'https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
                return asset('storage/' . $data);
            }
        });
        return $profile_pic;
    }
}
if (!function_exists('get_notice_notification_by_role')) {
    function get_notice_notification_by_role()
    {
        $user = Auth::user();

        // Return empty collection if user is not authenticated
        if (!$user) {
            return collect([]);
        }

        $sessionRoles = Session::get('user_roles', []);

        $roleStaffFaculty = ['Internal Faculty', 'Guest Faculty', 'Training', 'Staff'];
        $roleStudent      = ['Student-OT'];

        $isStaffFaculty = !empty(array_intersect($roleStaffFaculty, $sessionRoles));
        $isStudent      = !empty(array_intersect($roleStudent, $sessionRoles));


        $commonNotices = DB::table('notices_notification')
            ->where('target_audience', 'All')
            ->where('active_inactive', 1)
            ->where('expiry_date', '>=', date('Y-m-d'))
            ->orderBy('display_date', 'desc')
            ->get();

        // 🔥 Staff/Faculty Notices
        if ($isStaffFaculty) {

            $data = DB::table('notices_notification')
                ->where('target_audience', 'like', '%Staff/Faculty%')
                ->where('active_inactive', 1)
                ->where('expiry_date', '>=', date('Y-m-d'))
                ->orderBy('display_date', 'desc')
                ->get();


            return $commonNotices->merge($data);
        }

        // 🔥 Student OT Notices
        if ($isStudent) {
            $roleNotices =  DB::table('notices_notification')
                ->join('student_master_course__map as smcm', 'notices_notification.course_master_pk', '=', 'smcm.course_master_pk')
                ->where('target_audience', 'like', '%Office trainee%')
                ->where('notices_notification.active_inactive', 1)
                ->where('smcm.student_master_pk', $user->user_id)
                ->where('expiry_date', '>=', date('Y-m-d'))
                ->orderBy('display_date', 'desc')
                ->get();


            return $commonNotices->merge($roleNotices);
        }

        // Roles not matching → return only "All"
        return $commonNotices;
    }
}

/**
 * Get NotificationService instance
 *
 * @return \App\Services\NotificationService
 */
if (!function_exists('notification')) {
    function notification()
    {
        return app(\App\Services\NotificationService::class);
    }
}

    function getcoursevalue()
    {
        $user_role_master_pk = Session::get('user_role_master_pk');
        $courseval = DB::table('course_master')
            ->select('course_name','pk')
            ->where('user_role_master_pk', $user_role_master_pk)
            ->get();

        return $courseval;
    }
