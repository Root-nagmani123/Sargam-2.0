<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Resolves which top-level nav tab is active for the current request.
 *
 * This is a behaviour-preserving extraction of the 70-line @php block that
 * previously lived in admin/layouts/master.blade.php. The branch ORDER is
 * significant: the "home modules" group is evaluated before "setup" because
 * some paths (e.g. admin/notice/*) match both, and home must win.
 *
 * Returned values map to the tab-pane ids in the master layout:
 *   #home, #tab-setup, #tab-communications, #tab-academics, #tab-material-management
 *
 * To re-bucket a module, move its route pattern / path prefix between the
 * groups below — one place, instead of editing a Blade template.
 */
class NavTabResolver
{
    /** Route-name patterns (support * wildcards via Request::routeIs). */
    private const HOME_ROUTES = [
        'admin.dashboard', 'admin.dashboard.*',
        'admin.estate.*', 'admin.mess.*', 'admin.issue-management*',
        'admin.issue-categories.*', 'admin.issue-sub-categories.*',
        'admin.issue-priorities.*', 'admin.issue-escalation-matrix.*',
        'admin.employee_idcard.*', 'admin.duplicate_idcard.*', 'admin.family_idcard.*',
        'admin.security.*', 'admin.dashboard.feed',
        'admin.notice.create', 'admin.notice.index', 'admin.notice.edit',
        'member.profile.edit',
    ];

    /** Path prefixes (case-sensitive, matched with str_starts_with). */
    private const HOME_PATHS = [
        'admin/estate', 'admin/mess', 'admin/issue-management', 'admin/issue-categories',
        'admin/issue-sub-categories', 'admin/issue-priorities', 'admin/issue-escalation-matrix',
        'admin/employee-idcard', 'admin/duplicate-idcard', 'admin/family-idcard',
        'security/', 'member/profile/edit',
    ];

    private const SETUP_ROUTES = [
        'member.*', 'faculty.*', 'programme.*', 'admin.roles.*', 'admin.users.*',
        'calendar.index', 'feedback.*',
    ];

    private const SETUP_PATHS = [
        'setup/', 'admin/setup', 'courseAttendanceNoticeMap', 'course_memo',
        'building_floor', 'group_mapping', 'course-repository', 'feedback', 'admin/feedback',
        'admin/notice', 'attendance', 'ot_notice', 'forms', 'registration',
        'mdo_escrot', 'student_medical', 'medical_exception', 'memo_discipline',
        'country', 'state', 'city', 'stream', 'subject', 'Venue-Master',
        'batch', 'curriculum', 'mapping', 'admin/master', 'master/', 'password',
        'calendar', 'expertise', 'faculty_notice', 'faculty_mdo',
    ];

    /** Substrings matched with str_contains (not prefix). */
    private const SETUP_PATH_CONTAINS = ['breadcrumb-showcase'];

    public static function resolve(Request $request): string
    {
        $path = $request->path();

        // 1) Home — dashboard + modules moved from Setup to Home.
        if ($request->routeIs('admin.dashboard') || $request->routeIs('admin.dashboard.*')) {
            return '#home';
        }
        if (self::matchesRoutes($request, self::HOME_ROUTES)
            || self::matchesPaths($path, self::HOME_PATHS)) {
            return '#home';
        }

        // 2) Setup.
        if (self::matchesRoutes($request, self::SETUP_ROUTES)
            || self::matchesPaths($path, self::SETUP_PATHS)
            || self::containsAny($path, self::SETUP_PATH_CONTAINS)) {
            return '#tab-setup';
        }

        // 3) Communications.
        if (str_starts_with($path, 'communications')
            || $request->routeIs('*communications*')
            || $request->routeIs('admin.birthday-wish.*')) {
            return '#tab-communications';
        }

        // 4) Academics.
        if (str_starts_with($path, 'academics') || $request->routeIs('*academics*')) {
            return '#tab-academics';
        }

        // 5) Material management.
        if (str_starts_with($path, 'material') || $request->routeIs('*material*')) {
            return '#tab-material-management';
        }

        return '#home';
    }

    private static function matchesRoutes(Request $request, array $patterns): bool
    {
        foreach ($patterns as $p) {
            if ($request->routeIs($p)) {
                return true;
            }
        }
        return false;
    }

    private static function matchesPaths(string $path, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }
        return false;
    }

    private static function containsAny(string $path, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($path, $needle)) {
                return true;
            }
        }
        return false;
    }
}
