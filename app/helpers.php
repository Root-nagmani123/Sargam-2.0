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
// function hasRole($role)
// {
//     $user = Auth::user();
//     if (!$user) return false;

//     // Step 1: Check session roles first (Student static role bhi yahi me milega)
//     $sessionRoles = Session::get('user_roles', []);
//     if (in_array($role, $sessionRoles)) {
//         return true;
//     }

//     // Step 2: Check database roles + cache
//     $roles = Cache::remember('user_roles_' . $user->pk, 10, function () use ($user) {
//         return $user->roles()->pluck('user_role_name')->toArray();
//     });

//     return in_array($role, $roles);
// }


function hasRole($role)
{
    $user = Auth::user();
    if (!$user) return false;

    // Session roles take precedence. Students get the 'Student-OT' pseudo-role only
    // in the session (it is never assigned as a Spatie role), and admin/faculty
    // session roles are derived from their Spatie roles at login. Checking the
    // session first keeps both flows working — including Moodle token logins,
    // where the middleware logs the user in and sets user_roles in the session.
    $sessionRoles = Session::get('user_roles', []);
    if (is_array($sessionRoles) && in_array($role, $sessionRoles, true)) {
        return true;
    }

    // Backward-compatible alias: old code may use "SuperAdmin" while DB role is "Super Admin".
    if ($role === 'SuperAdmin' || $role === 'Super Admin') {
        return $user->hasRole('Super Admin') || $user->hasRole('SuperAdmin');
    }

    // Spatie already has hasRole() method
    return $user->hasRole($role);
}

/**
 * Officer Trainee portal user (session Student-OT pseudo-role or Spatie Officer Trainee).
 */
function isOfficerTraineeUser(): bool
{
    return hasRole('Student-OT') || hasRole('Officer Trainee');
}

/**
 * Whether the user has at least one Spatie role (user management → assign role).
 */
function userHasAssignedRoles(): bool
{
    $user = Auth::user();
    if (! $user) {
        return false;
    }

    return $user->roles()->exists();
}

/**
 * Full sidebar / setup category access (all groups without per-menu permission checks).
 * Actual DB role names: 'Super Admin' (id:1). 'Admin' does not exist in DB.
 */
function isSidebarPrivilegedUser(): bool
{
    return hasRole('Super Admin');
}

/**
 * Estate authority: can manage all estate records (Estate Admin role or Super Admin).
 * DB role names: 'Estate Admin' (id:8), 'Super Admin' (id:1).
 */
function isEstateAuthority(): bool
{
    return hasRole('Estate Admin') || hasRole('Super Admin');
}

/**
 * Estate HAC authority: can perform HAC-related actions.
 * DB role names: 'Estate HAC' (id:9), 'Estate Admin' (id:8), 'Super Admin' (id:1).
 */
function isEstateHacAuthority(): bool
{
    return hasRole('Estate HAC') || hasRole('Estate Admin') || hasRole('Super Admin');
}

/**
 * Training authority: Spatie training admin roles plus legacy session role names
 * (Training-Induction, Training-MCTP, IST) used across sidebar and calendar modules.
 */
function isTrainingOrEstateAuthority(): bool
{
    return hasRole('Estate Admin') || hasRole('Super Admin')
        || hasRole('Training Induction Admin') || hasRole('Training MCTP Admin') || hasRole('Training IST')
        || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST')
        || hasRole('Training');
}
/**
 * Faculty portal / faculty-facing modules (matches menu + CalendarController checks).
 */
function is_faculty_portal_user(): bool
{
    $user = Auth::user();
    // print_r($user);die;
    if (! $user) {
        return false;
    }

    if (($user->user_category ?? '') === 'F') {
        return true;
    }

    return hasRole('Internal Faculty') || hasRole('Guest Faculty') || hasRole('Faculty')
        || hasRole('CC') || hasRole('ACC');
}

/**
 * Resolve faculty_master.pk for the authenticated user.
 *
 * Mapping used across the app:
 * - user_category E: user_id → employee_master.pk → faculty_master.employee_master_pk
 * - user_category F / notice memo: user_id → faculty_master.pk (coordinator & notices)
 * - CalendarController::index: employee_master_pk lookup, then filter timetable by faculty pk
 * - CalendarController::feedbackList (legacy): user_id used directly on timetable.faculty_master
 */
function get_auth_faculty_master_pk(): ?int
{
    $user = Auth::user();
    if (! $user) {
        return null;
    }

    $userId = (int) $user->user_id;
    if ($userId <= 0) {
        return null;
    }

    // 1) Standard employee-linked faculty (Dashboard, UserController, MedicalException faculty view)
    $pk = \App\Models\FacultyMaster::where('employee_master_pk', $userId)->value('pk');
    if ($pk) {
        return (int) $pk;
    }

    // 2) user_id stores faculty_master.pk (Notice/Memo, some faculty credentials)
    if (\App\Models\FacultyMaster::where('pk', $userId)->exists()) {
        return $userId;
    }

    // 3) employee_master.pk / pk_old alias then faculty link
    if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'pk_old')) {
        $employeePk = \Illuminate\Support\Facades\DB::table('employee_master')
            ->where(function ($q) use ($userId) {
                $q->where('pk', $userId)->orWhere('pk_old', $userId);
            })
            ->value('pk');

        if ($employeePk) {
            $pk = \App\Models\FacultyMaster::where('employee_master_pk', $employeePk)->value('pk');
            if ($pk) {
                return (int) $pk;
            }
        }
    }

    // 4) Match faculty by login mobile / email (guest faculty without employee link)
    if (! empty($user->mobile_no)) {
        $pk = \App\Models\FacultyMaster::where('mobile_no', $user->mobile_no)->value('pk');
        if ($pk) {
            return (int) $pk;
        }
    }

    if (! empty($user->user_name)) {
        $pk = \App\Models\FacultyMaster::where('email_id', $user->user_name)
            ->orWhere('alternate_email_id', $user->user_name)
            ->value('pk');
        if ($pk) {
            return (int) $pk;
        }
    }

    // 5) Legacy alignment with CalendarController::feedbackList & coordinator rows using user_id as faculty pk
    if (is_faculty_portal_user()) {
        if (\Illuminate\Support\Facades\DB::table('topic_feedback')
            ->where('faculty_pk', $userId)
            ->where('is_submitted', 1)
            ->exists()) {
            return $userId;
        }

        if (\Illuminate\Support\Facades\DB::table('timetable')
            ->where('faculty_master', $userId)
            ->exists()) {
            return $userId;
        }

        if (\App\Models\CourseCordinatorMaster::where('Coordinator_name', $userId)
            ->orWhere('Assistant_Coordinator_name', $userId)
            ->exists()) {
            return $userId;
        }
    }

    return provision_faculty_profile_from_employee_user();
}

if (!function_exists('extract_faculty_ids_from_raw')) {
    /**
     * Parse faculty identifiers from scalar, CSV, or JSON array values.
     *
     * @param mixed $raw
     * @return array<int>
     */
    function extract_faculty_ids_from_raw($raw): array
    {
        if ($raw === null) {
            return [];
        }

        if (is_int($raw)) {
            return $raw > 0 ? [$raw] : [];
        }

        if (is_array($raw)) {
            $ids = [];
            foreach ($raw as $v) {
                if (is_numeric($v)) {
                    $ids[] = (int) $v;
                }
            }

            return array_values(array_unique(array_filter($ids)));
        }

        $text = trim((string) $raw);
        if ($text === '') {
            return [];
        }

        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $ids = [];
            foreach ($decoded as $v) {
                if (is_numeric($v)) {
                    $ids[] = (int) $v;
                }
            }

            return array_values(array_unique(array_filter($ids)));
        }

        if (strpos($text, ',') !== false) {
            $ids = [];
            foreach (explode(',', $text) as $part) {
                $part = trim($part);
                if (is_numeric($part)) {
                    $ids[] = (int) $part;
                }
            }

            return array_values(array_unique(array_filter($ids)));
        }

        return is_numeric($text) ? [(int) $text] : [];
    }
}

if (!function_exists('get_timetable_faculty_ids')) {
    /**
     * Resolve faculty ids from timetable-like object.
     *
     * @param mixed $timetable
     * @return array<int>
     */
    function get_timetable_faculty_ids($timetable): array
    {
        if (!$timetable) {
            return [];
        }

        $ids = [];
        $ids = array_merge($ids, extract_faculty_ids_from_raw(data_get($timetable, 'faculty_master')));
        $ids = array_merge($ids, extract_faculty_ids_from_raw(data_get($timetable, 'internal_faculty')));

        return array_values(array_unique(array_filter($ids)));
    }
}

if (!function_exists('get_timetable_faculty_names')) {
    /**
     * Resolve displayable faculty names from timetable-like object.
     *
     * @param mixed $timetable
     * @param string $fallback
     * @return string
     */
    function get_timetable_faculty_names($timetable, string $fallback = 'N/A'): string
    {
        if (!$timetable) {
            return $fallback;
        }

        $ids = get_timetable_faculty_ids($timetable);
        if (empty($ids)) {
            $directName = trim((string) data_get($timetable, 'faculty.full_name', ''));
            return $directName !== '' ? $directName : $fallback;
        }

        $nameById = \App\Models\FacultyMaster::query()
            ->whereIn('pk', $ids)
            ->whereNotNull('full_name')
            ->pluck('full_name', 'pk')
            ->toArray();

        $ordered = [];
        foreach ($ids as $id) {
            if (!empty($nameById[$id])) {
                $ordered[] = (string) $nameById[$id];
            }
        }

        $ordered = array_values(array_unique(array_filter($ordered)));
        return !empty($ordered) ? implode(', ', $ordered) : $fallback;
    }
}

if (!function_exists('resolve_chat_sender_identity')) {
    /**
     * Resolve a memo/notice chat message's sender name + role.
     *
     * role_type 's' → created_by is student_master.pk.
     * role_type 'f' (or anything else) → created_by is user_credentials.user_id,
     * which maps to employee_master.pk (see App\Models\User doc comment). Multiple
     * distinct admins/faculty can post in the same conversation, so callers must not
     * collapse all of them to a generic "Admin" label — resolve the real name + role.
     *
     * @return array{display_name: string, role_name: ?string}
     */
    function resolve_chat_sender_identity($createdBy, ?string $roleType, string $fallback = 'Unknown'): array
    {
        if ($roleType === 's') {
            $name = \Illuminate\Support\Facades\DB::table('student_master')->where('pk', $createdBy)->value('display_name');
            return ['display_name' => $name ?: 'Student', 'role_name' => 'Officer Trainee'];
        }

        $uc = \Illuminate\Support\Facades\DB::table('user_credentials')
            ->where('user_id', $createdBy)
            ->where('user_category', '!=', 'S')
            ->first();

        // Fallback for legacy rows saved with user_credentials.pk instead of .user_id.
        if (!$uc) {
            $uc = \Illuminate\Support\Facades\DB::table('user_credentials')
                ->where('pk', $createdBy)
                ->where('user_category', '!=', 'S')
                ->first();
        }

        if (!$uc) {
            return ['display_name' => $fallback, 'role_name' => null];
        }

        $employeeName = \Illuminate\Support\Facades\DB::table('employee_master')
            ->where('pk', $uc->user_id)
            ->selectRaw("TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(middle_name,''),' ',COALESCE(last_name,''))) as full_name")
            ->value('full_name');

        $roleName = \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $uc->pk)
            ->where('model_has_roles.model_type', \App\Models\User::class)
            ->value('roles.name');

        return [
            'display_name' => $employeeName ?: ($uc->user_name ?: $fallback),
            'role_name' => $roleName,
        ];
    }
}

if (!function_exists('expected_feedback_count_sql')) {
    /**
     * SQL expression for the number of feedbacks EXPECTED for a timetable session.
     *
     * Student feedback is collected only for Teaching-role faculty. Each session
     * stores its faculty in the faculty_details JSON column:
     *   [{ "faculty_pk": int, "faculty_type": int, "role": string, "feedback": string }, ...]
     * and the student feedback form lists exactly those entries whose role is
     * 'Teaching' (CalendarController::studentFeedback). The "expected" count must
     * match that filter, otherwise Sectional/Administration faculty inflate the
     * pending count even though no student can ever submit feedback for them.
     *
     * Resolution order (per row):
     *   1. Valid faculty_details JSON -> count of entries with role = 'Teaching'.
     *   2. Else valid faculty_master JSON array -> its length (legacy rows).
     *   3. Else -> 1 (legacy scalar faculty_master).
     *
     * @param string $alias Timetable table alias used in the query (default 't').
     * @return string Raw SQL expression, already wrapped in parentheses.
     */
    function expected_feedback_count_sql(string $alias = 't'): string
    {
        $details = "{$alias}.faculty_details";
        $master  = "{$alias}.faculty_master";

        return "(CASE
            WHEN JSON_VALID({$details})
                THEN COALESCE(JSON_LENGTH(JSON_SEARCH({$details}, 'all', 'Teaching', NULL, '\$[*].role')), 0)
            WHEN JSON_VALID({$master})
                THEN JSON_LENGTH({$master})
            ELSE 1
        END)";
    }
}

/**
 * For employee logins (user_category E) with Internal/Guest Faculty role but no faculty_master row:
 * link an existing faculty by mobile/email, or create a minimal faculty_master linked to employee_master.
 */
function provision_faculty_profile_from_employee_user(): ?int
{
    $user = Auth::user();
    if (! $user || ($user->user_category ?? '') !== 'E' || ! is_faculty_portal_user()) {
        return null;
    }

    $employeePk = (int) $user->user_id;
    if ($employeePk <= 0) {
        return null;
    }

    $employee = \Illuminate\Support\Facades\DB::table('employee_master')
        ->where('pk', $employeePk)
        ->first();

    if (! $employee) {
        return null;
    }

    $mobile = trim((string) ($user->mobile_no ?: $employee->mobile ?? ''));
    $email = trim((string) ($employee->email ?? ''));

    // Link existing faculty that was never tied to this employee
    $existingQuery = \App\Models\FacultyMaster::query()->whereNull('employee_master_pk');

    if ($mobile !== '') {
        $linked = (clone $existingQuery)->where('mobile_no', $mobile)->first();
        if ($linked) {
            $linked->update(['employee_master_pk' => $employeePk]);

            return (int) $linked->pk;
        }
    }

    if ($email !== '') {
        $linked = (clone $existingQuery)->where('email_id', $email)
            ->orWhere('alternate_email_id', $email)
            ->first();
        if ($linked && empty($linked->employee_master_pk)) {
            $linked->update(['employee_master_pk' => $employeePk]);

            return (int) $linked->pk;
        }
    }

    // Already linked elsewhere
    if (\App\Models\FacultyMaster::where('employee_master_pk', $employeePk)->exists()) {
        return (int) \App\Models\FacultyMaster::where('employee_master_pk', $employeePk)->value('pk');
    }

    $facultyType = hasRole('Guest Faculty') ? 2 : 1;
    $firstName = trim((string) ($employee->first_name ?? 'Faculty'));
    $lastName = trim((string) ($employee->last_name ?? ''));
    $fullName = trim($firstName.' '.$lastName) ?: $firstName;

    $prefix = \Illuminate\Support\Facades\DB::table('faculty_type_master')
        ->where('pk', $facultyType)
        ->value('shot_faculty_type_name') ?: ($facultyType === 2 ? 'GST' : 'INT');

    $latestCode = \App\Models\FacultyMaster::where('faculty_code', 'like', $prefix.'-%')
        ->orderByRaw("CAST(SUBSTRING_INDEX(faculty_code, '-', -1) AS UNSIGNED) DESC")
        ->value('faculty_code');

    $nextNumber = 1;
    if ($latestCode && preg_match('/-0*(\d+)$/', $latestCode, $matches)) {
        $nextNumber = (int) $matches[1] + 1;
    }

    $template = \App\Models\FacultyMaster::where('faculty_type', $facultyType)
        ->whereNotNull('country_master_pk')
        ->first();

    $faculty = \App\Models\FacultyMaster::create([
        'faculty_type' => $facultyType,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'full_name' => $fullName,
        'email_id' => $email ?: null,
        'mobile_no' => $mobile ?: null,
        'employee_master_pk' => $employeePk,
        'faculty_code' => $prefix.'-'.str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT),
        'active_inactive' => 1,
        'faculty_sector' => $template->faculty_sector ?? 1,
        'country_master_pk' => $template->country_master_pk ?? 1,
        'state_master_pk' => $template->state_master_pk ?? 35,
        'state_district_mapping_pk' => $template->state_district_mapping_pk ?? 212,
        'city_master_pk' => $template->city_master_pk ?? 1622,
        'created_by' => $employeePk,
        'created_date' => now(),
    ]);

    return (int) $faculty->pk;
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
        || hasRole('IST')
        || hasRole('Employee');
}

function get_Role_by_course()
{
    $user = Auth::user();

    // Return empty array if user is not authenticated
    if (!$user) {
        return [];
    }

    // Admin / Super Admin / PA see all courses — no restriction
    if (hasRole('Admin') || hasRole('Super Admin') || hasRole('PA')) {
        return [];
    }

    // Get Spatie role IDs assigned to this user
    $userRoleIds = DB::table('model_has_roles')
        ->where('model_id', $user->pk)
        ->where('model_type', \App\Models\User::class)
        ->pluck('role_id')
        ->toArray();

    if (empty($userRoleIds)) {
        // Non-admin user without assigned roles should see no course-scoped data.
        return [-1];
    }

    $epoch = Cache::get('role_by_course_epoch', 1);
    $cacheKey = 'role_by_course_v2_' . $user->pk . '_' . md5(implode(',', $userRoleIds)) . '_e' . $epoch;
    $role_course = Cache::remember($cacheKey, 600, function () use ($userRoleIds) {
        return DB::table('course_master as cm')
            ->join('roles as r', 'cm.user_role_master_pk', '=', 'r.id')
            ->whereIn('r.id', $userRoleIds)
            ->pluck('cm.pk')
            ->toArray();
    });
    if (empty($role_course)) {
        // Non-admin user with roles but no mapped courses should see no data.
        return [-1];
        // return [-1];
    }

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
        $maxKb = $fileMaxKb ?? 5120;
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
