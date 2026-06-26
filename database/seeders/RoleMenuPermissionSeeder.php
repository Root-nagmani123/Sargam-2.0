<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * RoleMenuPermissionSeeder
 *
 * Assigns Spatie permissions to roles so that CheckMenuPermission middleware
 * can enforce URL-level access control.  Everything is database-driven —
 * no URLs are hardcoded in the middleware.
 *
 * RUN ONCE after deploying the CheckMenuPermission middleware:
 *
 *   php artisan db:seed --class=RoleMenuPermissionSeeder
 *
 * Idempotent — safe to run multiple times (uses givePermissionTo, not sync).
 * After running, fine-tune assignments via the /admin/roles UI.
 *
 * HOW IT WORKS
 * ─────────────
 * Each role gets permissions whose names contain the listed keywords.
 * Permission names are auto-generated slugs of menu item names
 * (e.g. "Attendance Report" → "attendance_report").
 *
 * STUDENT-OT SPECIAL CASE
 * ────────────────────────
 * Students never hold a DB role (user_category='S' + session pseudo-role).
 * The middleware resolves their access by looking up the 'Student-OT' role
 * directly in the DB.  This seeder creates that role (if absent) and assigns
 * the correct permissions so admins can manage student access via /admin/roles.
 */
class RoleMenuPermissionSeeder extends Seeder
{
    /**
     * Map: Spatie role name → permission keywords.
     * A permission is given to a role if its name CONTAINS any listed keyword.
     * Use '*' to assign ALL permissions to a role.
     */
    private const ROLE_KEYWORD_MAP = [

        // ── Admins ──────────────────────────────────────────────────────────
        'Super Admin' => ['*'],
        'Admin'       => ['*'],

        // ── Estate ──────────────────────────────────────────────────────────
        'Estate Admin' => [
            'estate', 'house', 'possession', 'meter', 'block', 'campus',
            'electric', 'unit_type', 'unit_sub', 'pay_scale', 'eligibility',
            'hac', 'migration', 'bill', 'define',
        ],
        'Estate HAC' => [
            'estate', 'house', 'hac', 'put_in_hac', 'request_for_estate',
            'change_request', 'possession',
        ],
        'HAC Person' => [
            'estate', 'hac', 'request_for_estate', 'put_in_hac',
        ],

        // ── Faculty ─────────────────────────────────────────────────────────
        'Internal Faculty' => [
            'faculty', 'feedback', 'session_feedback', 'calendar', 'timetable',
            'attendance', 'course_repository', 'notice', 'memo', 'whos_who',
            'directory', 'medical_exception', 'mdo', 'escort',
        ],
        'Guest Faculty' => [
            'faculty', 'feedback', 'session_feedback', 'calendar', 'timetable',
            'attendance', 'course_repository', 'notice', 'memo', 'whos_who',
            'directory',
        ],
        'Faculty' => [
            'faculty', 'feedback', 'session_feedback', 'calendar', 'timetable',
            'attendance', 'course_repository', 'notice', 'memo', 'whos_who',
            'directory',
        ],

        // ── Mess ────────────────────────────────────────────────────────────
        'Mess Staff' => [
            'mess', 'material_management', 'selling_voucher', 'my_bills',
            'purchase_order', 'inventory', 'stock', 'menu_rate', 'sale_counter',
            'client_type', 'vendor', 'store',
        ],
        'Mess Admin' => [
            'mess', 'material_management', 'selling_voucher', 'my_bills',
            'purchase_order', 'inventory', 'stock', 'menu_rate', 'sale_counter',
            'client_type', 'vendor', 'store', 'monthly_bill', 'finance',
            'credit_limit', 'report',
        ],

        // ── Training ────────────────────────────────────────────────────────
        'Training Induction Admin' => [
            'calendar', 'timetable', 'attendance', 'programme', 'course',
            'group_mapping', 'subject', 'stream', 'member', 'faculty',
            'mdo', 'notice', 'memo', 'feedback',
        ],
        'Training MCTP Admin' => [
            'calendar', 'timetable', 'attendance', 'programme', 'course',
            'group_mapping', 'subject', 'stream', 'member', 'faculty',
            'mdo', 'notice', 'memo', 'feedback',
        ],
        'Training IST' => [
            'calendar', 'timetable', 'attendance', 'programme', 'course',
            'group_mapping', 'subject', 'stream', 'member', 'faculty',
            'notice', 'memo',
        ],

        // ── Security ────────────────────────────────────────────────────────
        'Security Card' => [
            'security', 'idcard', 'vehicle_pass', 'visitor_pass',
            'employee_idcard', 'family_idcard', 'duplicate_idcard',
        ],
        'Admin Security' => [
            'security', 'idcard', 'vehicle_pass', 'visitor_pass',
            'employee_idcard', 'family_idcard', 'duplicate_idcard',
            'card_type', 'sub_type',
        ],

        // ── Other ───────────────────────────────────────────────────────────
        'Doctor'   => ['medical_exemption', 'student_medical'],
        'Staff'    => ['dashboard', 'directory', 'whos_who', 'birthday', 'course_repository_user'],
        'Employee' => [
            'dashboard', 'directory', 'whos_who', 'birthday',
            'course_repository_user', 'estate', 'request_for_estate', 'issue_management',
        ],

        // ── Student-OT ──────────────────────────────────────────────────────
        // Students use this role for permission checks (no user-level DB assignment;
        // the middleware looks up this role directly).
        // Keywords map to menu permission_names that students should access.
        'Student-OT' => [
            'dashboard',
            'calendar_ot', 'ot_calendar', 'ot_timetable', 'ot_index',
            'student_feedback', 'faculty_feedback',
            'attendance',
            'ot_notice', 'notice_memo',
            'mdo_escrot', 'ot_mdo',
            'medical_exception_ot', 'ot_medical',
            'course_repository_user',
            'whos_who', 'directory',
            'birthday',
        ],
    ];

    public function run(): void
    {
        // Clear Spatie's permission cache before starting.
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = Permission::where('guard_name', 'web')->get();

        if ($allPermissions->isEmpty()) {
            $this->command->warn(
                'No permissions found. Create menu items via Sidebar Manager first, then re-run this seeder.'
            );
            return;
        }

        $this->command->info("Found {$allPermissions->count()} web permissions.");

        foreach (self::ROLE_KEYWORD_MAP as $roleName => $keywords) {
            $this->assignToRole($roleName, $keywords, $allPermissions);
        }

        // Flush cache after all assignments so the middleware sees fresh data.
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->newLine();
        $this->command->info('Role-permission seeding complete.');
        $this->command->newLine();
        $this->command->line('NEXT STEPS:');
        $this->command->line('  1. php artisan cache:clear          ← clears RBAC middleware cache');
        $this->command->line('  2. Open /admin/roles in the app     ← review & fine-tune assignments');
        $this->command->line('  3. Students can now be managed via the Student-OT role in /admin/roles');
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function assignToRole(string $roleName, array $keywords, $allPermissions): void
    {
        // Create the role if it doesn't exist (needed for Student-OT).
        $role = Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web']
        );

        $isNew = $role->wasRecentlyCreated;

        if (in_array('*', $keywords, true)) {
            $role->givePermissionTo($allPermissions);
            $label = $isNew ? '(created) ' : '';
            $this->command->info("  {$label}{$roleName} → all {$allPermissions->count()} permissions");
            return;
        }

        $matched = $allPermissions->filter(function (Permission $perm) use ($keywords): bool {
            $name = strtolower($perm->name);
            foreach ($keywords as $keyword) {
                if (str_contains($name, strtolower($keyword))) {
                    return true;
                }
            }
            return false;
        });

        if ($matched->isNotEmpty()) {
            $role->givePermissionTo($matched);
            $label = $isNew ? '(created) ' : '';
            $this->command->info("  {$label}{$roleName} → {$matched->count()} permissions");
        } else {
            $label = $isNew ? '(created, ' : '(';
            $this->command->warn("  {$label}no keyword match) {$roleName} — keywords: " . implode(', ', $keywords));
        }
    }
}
