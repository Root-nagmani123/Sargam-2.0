<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GET /medical-exception-faculty-view — who reaches the admin branch (PR #335 F-019).
 *
 * The route is auth-only because Faculty logins must reach their own view. Every
 * other account used to fall through to the admin view, which lists every
 * faculty's courses, trainees and medical exemptions. Like the escort view, that
 * branch now needs the screen's permission (medical_exception_faculty) or
 * Super Admin; a Faculty login keeps its own view.
 */
class MedicalExceptionAdminViewAccessTest extends TestCase
{
    private const URL = '/medical-exception-faculty-view';

    private bool $inTransaction = false;

    private int $obLevel = 0;

    private int $seq = 0;

    private int $userIdBase = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->obLevel = ob_get_level();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No database connection: '.$e->getMessage());
        }

        DB::beginTransaction();
        $this->inTransaction = true;

        $this->userIdBase = (int) DB::table('faculty_master')->max('employee_master_pk') + 900100;
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > $this->obLevel) {
            ob_end_clean();
        }

        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        parent::tearDown();
    }

    /** A fresh login with the given Spatie role (or none) and the session roles login would set. */
    private function account(string $category, ?string $role, array $sessionRoles): array
    {
        if ($role !== null && ! DB::table('roles')->where('name', $role)->exists()) {
            $this->markTestSkipped("no '$role' role in this database");
        }

        $userId = $this->userIdBase + $this->seq++;
        $user = User::find(DB::table('user_credentials')->insertGetId([
            'user_name' => 'mefv.'.$userId, 'user_id' => $userId, 'user_category' => $category,
        ]));
        if ($role !== null) {
            $user->assignRole($role);
        }

        return [$user->fresh(), $sessionRoles];
    }

    private function open(array $actor)
    {
        [$user, $sessionRoles] = $actor;

        return $this->actingAs($user)->withSession(['user_roles' => $sessionRoles])->get(self::URL);
    }

    public function test_accounts_without_the_screen_permission_get_403(): void
    {
        $actors = [
            'Officer Trainee' => $this->account('S', 'Officer Trainee', ['Student-OT']),
            'Employee-only' => $this->account('E', 'Employee', ['Employee']),
            'no role' => $this->account('E', null, []),
        ];

        foreach ($actors as $label => $actor) {
            $this->assertSame(403, $this->open($actor)->status(), $label);
        }
    }

    public function test_permission_holder_and_super_admin_get_the_admin_view(): void
    {
        if (! DB::table('permissions')->where('name', 'medical_exception_faculty')->exists()) {
            $this->markTestSkipped("no 'medical_exception_faculty' permission in this database");
        }

        $holder = $this->account('E', null, []);
        $holder[0]->givePermissionTo('medical_exception_faculty');

        $actors = [
            'permission holder' => [$holder[0]->fresh(), []],
            'Super Admin' => $this->account('E', 'Super Admin', ['Super Admin']),
        ];

        foreach ($actors as $label => $actor) {
            $response = $this->open($actor);
            $this->assertSame(200, $response->status(), $label);
            $this->assertArrayHasKey('facultyData', $response->original->getData(), "$label: admin view");
        }
    }

    public function test_faculty_login_keeps_its_own_view(): void
    {
        $response = $this->open($this->account('E', null, ['Faculty']));

        $response->assertOk();
        $this->assertNotNull($response->viewData('data'));
        $this->assertArrayNotHasKey('facultyData', $response->original->getData(), 'Faculty gets the faculty view, not the admin view');
    }
}
