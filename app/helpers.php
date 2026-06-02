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

/**
 * Resolve the user-identifier column for a given FC table.
 *
 * After the username→user_id migration all FC tables use `user_id`.
 * Before the migration they still use `username` (or `userid` for
 * fc_pre_history / fc_path_report). A static cache avoids repeated
 * Schema::hasColumn() calls within the same request.
 */
function fc_user_col(string $table): string
{
    static $cache = [];
    if (! array_key_exists($table, $cache)) {
        if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'user_id')) {
            $cache[$table] = 'user_id';
        } elseif (\Illuminate\Support\Facades\Schema::hasColumn($table, 'userid')) {
            $cache[$table] = 'userid';
        } else {
            $cache[$table] = 'username';
        }
    }
    return $cache[$table];
}

/**
 * Resolve the correct user-identifier VALUE for a given FC table and userId.
 *
 * Post-migration: all FC tables use `user_id` (integer) → returns $userId.
 * Pre-migration: tables use `username` or `userid` (string) → looks up the
 * username string from user_credentials and returns that.
 *
 * This ensures WHERE clauses, insertions and updateOrCreate calls always use
 * the right type and value regardless of whether the migration has run yet.
 *
 * A static per-request cache is used to avoid repeated DB lookups.
 */
function fc_user_val(string $table, int $userId): string|int
{
    static $usernameCache  = [];
    static $credPkCache    = []; // staged roster pk → resolved user_credentials.pk (or roster pk if not yet migrated)
    $col = fc_user_col($table);

    // Staged /fc/login (Auth id = -fc_registration_master.pk).
    if ($userId < 0) {
        if ($col === 'user_id') {
            // If the trainee has already been migrated to user_credentials, use that pk
            // so that FC form data (fc_pre_history etc.) is stored under the correct id
            // even when they log in via /fc/login after migration.
            $rosterPk = abs($userId);
            if (! array_key_exists($rosterPk, $credPkCache)) {
                $rosterUserName = \Illuminate\Support\Facades\DB::table('fc_registration_master')
                    ->where('pk', $rosterPk)
                    ->value('user_id');
                $credPk = ($rosterUserName !== null && trim((string) $rosterUserName) !== '')
                    ? \Illuminate\Support\Facades\DB::table('user_credentials')
                        ->where('user_name', trim((string) $rosterUserName))
                        ->value('pk')
                    : null;
                $credPkCache[$rosterPk] = $credPk ? (int) $credPk : $rosterPk;
            }
            return $credPkCache[$rosterPk];
        }
        if (! array_key_exists($userId, $usernameCache)) {
            $usernameCache[$userId] = trim((string) (\Illuminate\Support\Facades\DB::table('fc_registration_master')
                ->where('pk', abs($userId))
                ->value('user_id') ?? ''));
        }

        return $usernameCache[$userId];
    }

    if ($col === 'user_id') {
        return $userId;
    }

    // Pre-migration: resolve the username string from user_credentials.
    if (! array_key_exists($userId, $usernameCache)) {
        $usernameCache[$userId] = \Illuminate\Support\Facades\DB::table('user_credentials')
            ->where('pk', $userId)
            ->value('user_name') ?? '';
    }
    return $usernameCache[$userId];
}

/**
 * Folder segment for FC uploads (staged Auth id is negative; paths use roster pk).
 */
function fc_upload_path_segment(int $userId): string
{
    return (string) ($userId < 0 ? abs($userId) : $userId);
}

/**
 * Resolve a stored upload path to an absolute filesystem path (public disk and legacy locations).
 */
function fc_resolve_storage_file_path(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }
    if (! is_string($path)) {
        return null;
    }
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $path)) {
        return null;
    }
    $path = ltrim($path, '/');
    if (str_starts_with($path, 'public/')) {
        $path = substr($path, strlen('public/'));
    }
    if (str_starts_with($path, 'storage/')) {
        $path = substr($path, strlen('storage/'));
    }

    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        return \Illuminate\Support\Facades\Storage::disk('public')->path($path);
    }

    $candidates = [
        storage_path('app/public/'.$path),
        public_path('storage/'.$path),
        public_path($path),
        storage_path('app/'.$path),
    ];

    foreach ($candidates as $full) {
        if (is_file($full)) {
            return $full;
        }
    }

    return null;
}

/**
 * Public URL for a file stored on the default public disk (storage/app/public) or an absolute URL.
 * Handles DB values like "uploads/user/photo.jpg", "storage/uploads/...", or full http(s) URLs.
 */
function view_file_link($path)
{
    if ($path === null || $path === '') {
        return null;
    }
    if (! is_string($path)) {
        return null;
    }
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $path = ltrim($path, '/');
    if (str_starts_with($path, 'public/')) {
        $path = substr($path, strlen('public/'));
    }
    if (str_starts_with($path, 'storage/')) {
        return asset($path);
    }

    if (fc_resolve_storage_file_path($path) !== null) {
        return asset('storage/' . $path);
    }

    return asset('storage/' . $path);
}

/**
 * Embed trainee photograph as a data URI for PDF output.
 */
function fc_photo_data_uri(?string $path): ?string
{
    $full = fc_resolve_storage_file_path($path);
    if ($full === null) {
        return null;
    }
    $mime = @mime_content_type($full) ?: 'image/jpeg';
    if (! str_starts_with((string) $mime, 'image/')) {
        return null;
    }

    $binary = (string) file_get_contents($full);
    if (function_exists('imagecreatefromstring')) {
        $src = @imagecreatefromstring($binary);
        if ($src !== false) {
            $w = imagesx($src);
            $h = imagesy($src);
            $maxW = 110;
            $maxH = 140;
            if ($w > 0 && $h > 0 && ($w > $maxW || $h > $maxH)) {
                $scale = min($maxW / $w, $maxH / $h);
                $nw = max(1, (int) round($w * $scale));
                $nh = max(1, (int) round($h * $scale));
                $dst = imagecreatetruecolor($nw, $nh);
                if ($dst !== false) {
                    if ($mime === 'image/png' || $mime === 'image/gif') {
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                        imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
                    } else {
                        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
                    }
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                    ob_start();
                    imagejpeg($dst, null, 88);
                    $binary = (string) ob_get_clean();
                    $mime = 'image/jpeg';
                    imagedestroy($dst);
                }
            }
            imagedestroy($src);
        }
    }

    return 'data:'.$mime.';base64,'.base64_encode($binary);
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
        $tsA = $a->sortDate ? \Carbon\Carbon::parse($a->sortDate)->startOfDay()->timestamp : 0;
        $tsB = $b->sortDate ? \Carbon\Carbon::parse($b->sortDate)->startOfDay()->timestamp : 0;
        if ($tsA !== $tsB) {
            return $tsB <=> $tsA;
        }

        return $b->sortId <=> $a->sortId;
    })->values();
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

if (! function_exists('fc_registration_progress_view')) {
    /**
     * Normalize FC registration $progress for dashboard/status Blade (legacy code sometimes passed a bare int percentage).
     *
     * @param  array|string|int|float|null  $progress
     * @return array{done:int,total:int,percentage:int,steps:array<string,bool>}
     */
    function fc_registration_progress_view($progress): array
    {
        $keys = ['step1', 'step2', 'step3', 'bank', 'travel', 'documents', 'confirmed'];
        $defaults = array_fill_keys($keys, false);

        if (is_array($progress)) {
            $steps = $progress['steps'] ?? [];
            if (! is_array($steps)) {
                $steps = [];
            }
            $steps = array_merge($defaults, $steps);
            $total = count($keys);
            $done = collect($steps)->filter()->count();
            $percentage = $progress['percentage'] ?? null;
            if ($percentage === null || $percentage === '') {
                $percentage = $total > 0 ? (int) round($done / $total * 100) : 0;
            } else {
                $percentage = (int) $percentage;
            }

            return [
                'done'       => $done,
                'total'      => (int) ($progress['total'] ?? $total),
                'percentage' => max(0, min(100, $percentage)),
                'steps'      => $steps,
            ];
        }

        $pct = is_numeric($progress) ? (int) $progress : 0;

        return [
            'done'       => 0,
            'total'      => count($keys),
            'percentage' => max(0, min(100, $pct)),
            'steps'      => $defaults,
        ];
    }
}

if (! function_exists('fc_registration_dynamic_form_step_accessible')) {
    /**
     * FC registration (dynamic form dashboard): allow opening a step only when prior steps are complete.
     * Completed steps stay open for review/edit ($stepCompleted === true).
     *
     * @param  array<string,bool>  $progressSteps  fc_registration_progress_view()['steps']
     */
    function fc_registration_dynamic_form_step_accessible(string $stepSlug, array $progressSteps, bool $stepCompleted): bool
    {
        if ($stepCompleted) {
            return true;
        }

        $required = match ($stepSlug) {
            'step1' => [],
            'step2' => ['step1'],
            'step3' => ['step2'],
            'bank' => ['step3'],
            'documents' => ['travel'],
            default => [],
        };

        foreach ($required as $key) {
            if (empty($progressSteps[$key])) {
                return false;
            }
        }

        return true;
    }
}

if (! function_exists('fc_registration_dynamic_form_step_blocked_message')) {
    function fc_registration_dynamic_form_step_blocked_message(string $stepSlug): string
    {
        return match ($stepSlug) {
            'step2' => 'Complete Basic Information first',
            'step3' => 'Complete Personal Details first',
            'bank' => 'Complete Other Details first',
            'documents' => 'Complete Travel Plan first',
            default => 'Complete the previous step first',
        };
    }
}

if (! function_exists('fc_numeric_display_value')) {
    /**
     * Strip trailing zeros from numeric strings for FC form display (e.g. DECIMAL 8745265.0000 → 8745265).
     */
    function fc_numeric_display_value(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $s = is_string($value) ? trim($value) : (string) $value;
        if ($s === '' || ! is_numeric($s)) {
            return $s;
        }
        if (! str_contains($s, '.')) {
            return $s;
        }

        return rtrim(rtrim($s, '0'), '.') ?: '0';
    }
}

if (! function_exists('fc_checkbox_multi_selected')) {
    /**
     * Selected option values for a multi-option checkbox (stored as JSON array string in DB).
     *
     * @param  array<int, array{value?: mixed, label?: string}>  $options
     * @return array<int, string>
     */
    function fc_checkbox_multi_selected(mixed $raw, array $options): array
    {
        if (count($options) === 0) {
            return [];
        }
        if (is_array($raw)) {
            return array_map('strval', $raw);
        }
        if ($raw === null || $raw === '') {
            return [];
        }
        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded)) {
            return array_map('strval', array_values($decoded));
        }

        return [];
    }
}

if (! function_exists('fc_checkbox_single_checked')) {
    /**
     * Whether a single yes/no checkbox field is checked (legacy tinyint or string).
     */
    function fc_checkbox_single_checked(mixed $raw): bool
    {
        if ($raw === null || $raw === '' || $raw === false || $raw === 0 || $raw === '0') {
            return false;
        }
        if ($raw === true) {
            return true;
        }
        if (is_numeric($raw)) {
            return (int) $raw === 1;
        }

        return in_array(strtolower((string) $raw), ['1', 'true', 'yes', 'on'], true);
    }
}

if (! function_exists('fc_form_group_active_index')) {
    /**
     * Index of the active group tab within a step (for ?group= query param after save).
     *
     * @param  iterable<int, object{group_name?: string}|array{group_name?: string}>  $groups
     */
    function fc_form_group_active_index(iterable $groups, ?string $activeGroupName = null): int
    {
        if ($activeGroupName === null || $activeGroupName === '') {
            return 0;
        }

        $index = 0;
        foreach ($groups as $group) {
            $name = is_object($group)
                ? ($group->group_name ?? null)
                : ($group['group_name'] ?? null);
            if ($name === $activeGroupName) {
                return $index;
            }
            $index++;
        }

        return 0;
    }
}

if (! function_exists('fc_form_first_group_name')) {
    /**
     * @param  iterable<int, object{group_name?: string}|array{group_name?: string}>  $groups
     */
    function fc_form_first_group_name(iterable $groups): ?string
    {
        foreach ($groups as $group) {
            $name = is_object($group)
                ? ($group->group_name ?? null)
                : ($group['group_name'] ?? null);
            if ($name !== null && $name !== '') {
                return (string) $name;
            }
        }

        return null;
    }
}

if (! function_exists('fc_ini_size_to_bytes')) {
    function fc_ini_size_to_bytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (float) $value,
        };
    }
}

if (! function_exists('fc_file_upload_hint')) {
    /**
     * Human-readable upload hint from Laravel validation_rules (e.g. "nullable|file|mimes:pdf,jpg|max:10240").
     */
    function fc_file_upload_hint(?string $validationRules, ?int $fileMaxKb = null): string
    {
        $maxKb = $fileMaxKb ?? 10240;
        $mimes = ['pdf', 'jpg', 'jpeg', 'png'];

        if ($validationRules) {
            if ($fileMaxKb === null && preg_match('/max:(\d+)/', $validationRules, $m)) {
                $maxKb = (int) $m[1];
            }
            if (preg_match('/mimes:([^|]+)/', $validationRules, $m)) {
                $mimes = array_map('trim', explode(',', $m[1]));
            }
        }

        $labels = [];
        foreach ($mimes as $ext) {
            $labels[] = match (strtolower($ext)) {
                'pdf' => 'PDF',
                'jpg', 'jpeg' => 'JPG',
                'png' => 'PNG',
                default => strtoupper($ext),
            };
        }
        $types = implode(', ', array_values(array_unique($labels)));

        $sizeLabel = $maxKb >= 1024 && $maxKb % 1024 === 0
            ? ($maxKb / 1024).' MB'
            : ($maxKb >= 1024 ? round($maxKb / 1024, 1).' MB' : $maxKb.' KB');

        return $types.', max '.$sizeLabel;
    }
}

if (! function_exists('fc_report_apply_tracker_user_resolution')) {
    /**
     * Join credentials (+ roster) so admin reports can show login name, not raw tracker user_id.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     */
    function fc_report_apply_tracker_user_resolution($query, string $trackerTable, ?string $alias = null): string
    {
        $t = $alias ?? $trackerTable;
        $u = fc_user_col($trackerTable);

        if ($u !== 'user_id') {
            $query->leftJoin('user_credentials as uc', "{$t}.{$u}", '=', 'uc.user_name');

            return 'username_legacy';
        }

        $query->leftJoin('user_credentials as uc', "{$t}.user_id", '=', 'uc.pk');

        if (Schema::hasTable('fc_registration_master')) {
            $query->leftJoin('fc_registration_master as frm', 'frm.pk', '=', "{$t}.user_id")
                ->leftJoin('user_credentials as uc_frm', 'uc_frm.user_name', '=', 'frm.user_id');
        }

        return 'user_id';
    }
}

if (! function_exists('fc_report_login_username_sql')) {
    function fc_report_login_username_sql(string $trackerTable, ?string $alias = null): string
    {
        $t = $alias ?? $trackerTable;
        $parts = ["NULLIF(TRIM(uc.user_name), '')"];

        if (Schema::hasTable('fc_registration_master')) {
            $parts[] = "NULLIF(TRIM(frm.user_id), '')";
            $parts[] = "NULLIF(TRIM(uc_frm.user_name), '')";
        }

        $parts[] = "CAST(`{$t}`.`user_id` AS CHAR)";

        return 'COALESCE('.implode(', ', $parts).')';
    }
}

if (! function_exists('fc_report_route_user_id_sql')) {
    function fc_report_route_user_id_sql(string $trackerTable, ?string $alias = null): string
    {
        $t = $alias ?? $trackerTable;

        if (Schema::hasTable('fc_registration_master')) {
            return "COALESCE(uc.pk, uc_frm.pk, `{$t}`.`user_id`)";
        }

        return "COALESCE(uc.pk, `{$t}`.`user_id`)";
    }
}

if (! function_exists('fc_report_join_student_master_firsts')) {
    /**
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     */
    function fc_report_join_student_master_firsts($query, string $trackerTable, ?string $alias = null): void
    {
        $t = $alias ?? $trackerTable;
        $u = fc_user_col($trackerTable);
        $s1Col = fc_user_col('student_master_firsts');

        $query->leftJoin('student_master_firsts as s1', function ($join) use ($t, $u, $s1Col) {
            if ($u !== 'user_id') {
                $join->on("s1.{$s1Col}", '=', "{$t}.{$u}");

                return;
            }

            $join->on(function ($join) use ($t, $s1Col) {
                $join->on("s1.{$s1Col}", '=', "{$t}.user_id");

                if (Schema::hasColumn('student_master_firsts', 'user_id')) {
                    $join->orOn('s1.user_id', '=', 'uc.pk');
                    if (Schema::hasTable('fc_registration_master')) {
                        $join->orOn('s1.user_id', '=', 'uc_frm.pk');
                    }
                }

                if (Schema::hasTable('fc_registration_master')
                    && Schema::hasColumn('student_master_firsts', 'username')) {
                    $join->orOn('s1.username', '=', 'frm.user_id');
                }
            });
        });
    }
}
