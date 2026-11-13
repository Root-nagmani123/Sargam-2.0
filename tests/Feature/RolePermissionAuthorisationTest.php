<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * PR #309 F-027 / PR #317 L-8: who may decide what a role can do.
 *
 * `POST roles/permissions/{id}` carried `auth` and nothing else, and
 * RoleController::assignPermission() checked nothing of its own. It took a role
 * id from the URL and a permission NAME from the request body,
 * `Permission::firstOrCreate()`d that name whether or not it had ever existed,
 * and granted it to the role. So any authenticated account could:
 *
 *   1. post the name of any existing permission to any role - including one it
 *      holds - and hold that permission itself a moment later; or
 *   2. invent a permission name, have the endpoint create it, and have it
 *      granted, which matters because `can()` returns false for names that do
 *      not exist and true for ones that do.
 *
 * Either way every `can()`-based gate in this application was decorative,
 * including `member_pii_read`, which this same PR introduces. The gate is
 * closed in the CONTROLLER rather than on the route, because RoleController is
 * mounted twice - `roles/*` and a separate hand-written `admin/roles/*` block -
 * and a route-level gate would have protected one of them.
 *
 * WHAT THESE TESTS HAVE TO AVOID. Asserting 403 alone is not enough: an
 * endpoint that refuses the response but still writes the row is the same
 * defect with better manners. So the permission table and the role's grants are
 * read after the refusal, and the assertion is that nothing was created and
 * nothing was granted.
 */
class RolePermissionAuthorisationTest extends TestCase
{
    /** A name no seeder, migration or menu slug can collide with. */
    private const PROBE_PERMISSION = 'zz_probe_escalation_f027';

    private bool $inTransaction = false;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('the role authorisation tests need the application database');
        }

        DB::beginTransaction();
        $this->inTransaction = true;
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }

        // The rollback restores the tables; it does not restore Spatie's cache,
        // which these tests can have warmed with a row that no longer exists.
        // Leaving it stale would leak into whatever runs next.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        parent::tearDown();
    }

    private function user(): User
    {
        $user = User::query()->orderBy('pk')->first();

        if (! $user) {
            $this->markTestSkipped('no user_credentials row to act as');
        }

        return $user;
    }

    /**
     * An authenticated account that is NOT a Super Admin.
     *
     * hasRole() reads the session's user_roles before it asks the role tables,
     * and login writes them there, so a session role is the production shape of
     * "this account holds exactly this role".
     */
    private function actAsOrdinaryUser(): void
    {
        $this->actingAs($this->user());
        session(['user_roles' => ['FC-Sec-Audit']]);

        $this->assertFalse(
            isSidebarPrivilegedUser(),
            'this case is meaningless unless the actor is genuinely non-privileged'
        );
    }

    private function actAsSuperAdmin(): void
    {
        $this->actingAs($this->user());
        session(['user_roles' => ['Super Admin']]);

        $this->assertTrue(isSidebarPrivilegedUser());
    }

    private function anyRole(): Role
    {
        $role = Role::query()->orderBy('id')->first();

        if (! $role) {
            $this->markTestSkipped('no role to grant a permission to');
        }

        return $role;
    }

    private function probePermissionExists(): bool
    {
        return DB::table('permissions')->where('name', self::PROBE_PERMISSION)->exists();
    }

    /**
     * Is the probe permission granted to this role?
     *
     * Read from the pivot rather than through Role::hasPermissionTo(), which
     * THROWS PermissionDoesNotExist when the name has never been created - the
     * refusal case, and the one this test most needs to assert about. (That is
     * the same asymmetry EnsureMemberPiiAccess documents from the other side:
     * User::can() routes through checkPermissionTo() and returns false, Role
     * does not.) A query answers both cases the same way.
     */
    private function probePermissionIsGrantedTo(Role $role): bool
    {
        return DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('role_has_permissions.role_id', $role->id)
            ->where('permissions.name', self::PROBE_PERMISSION)
            ->exists();
    }

    /**
     * The defect itself: an ordinary account inventing a permission and having
     * it granted. Refused, and - the half that matters - nothing written.
     */
    public function test_an_ordinary_account_cannot_grant_a_permission_to_a_role(): void
    {
        $this->actAsOrdinaryUser();
        $role = $this->anyRole();

        $this->assertFalse(
            $this->probePermissionExists(),
            'the probe permission must not exist before the call, or this test proves nothing'
        );

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('assign.roles.permissions', $role->id), [
                'permission' => self::PROBE_PERMISSION,
                'status' => 1,
            ])
            ->assertForbidden();

        // A 403 that still wrote would be the same escalation with a tidier
        // response, so the tables are read rather than the status code trusted.
        $this->assertFalse(
            $this->probePermissionExists(),
            'the endpoint refused the caller and created the permission anyway'
        );

        $this->assertFalse(
            $this->probePermissionIsGrantedTo($role),
            'the endpoint refused the caller and granted the permission anyway'
        );
    }

    /** The same account cannot revoke one either - the other direction is a denial of service. */
    public function test_an_ordinary_account_cannot_revoke_a_permission_from_a_role(): void
    {
        $this->actAsOrdinaryUser();
        $role = $this->anyRole();

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('assign.roles.permissions', $role->id), [
                'permission' => self::PROBE_PERMISSION,
                'status' => 0,
            ])
            ->assertForbidden();
    }

    /**
     * The grant side: a Super Admin is still admitted, or this "fix" is an
     * outage. Asserted through the real endpoint and read back from the
     * database, not from the response body.
     */
    public function test_a_super_admin_may_still_grant_a_permission(): void
    {
        $this->actAsSuperAdmin();
        $role = $this->anyRole();

        // An EXISTING permission the role does not hold yet. The endpoint refuses to
        // invent names for anybody (see the next test), so the probe name - which
        // exists nowhere - cannot be the thing a Super Admin grants.
        $name = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereNotIn('id', DB::table('role_has_permissions')->where('role_id', $role->id)->pluck('permission_id'))
            ->orderBy('id')
            ->value('name');

        if (! $name) {
            $this->markTestSkipped('the role already holds every permission');
        }

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('assign.roles.permissions', $role->id), [
                'permission' => $name,
                'status' => 1,
            ]);

        $this->assertNotSame(
            403,
            $response->getStatusCode(),
            'the gate refused a Super Admin - role administration is now unreachable for everybody'
        );

        $this->assertTrue(
            DB::table('role_has_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('role_has_permissions.role_id', $role->id)
                ->where('permissions.name', $name)
                ->exists(),
            'a Super Admin was admitted but the permission was not granted'
        );
    }

    /**
     * Not even a Super Admin may INVENT a permission name. firstOrCreate() used to mint
     * a row for any string; the endpoint now accepts only names that already exist or
     * that a `menus` row defines, so typos cannot become permissions.
     */
    public function test_a_super_admin_cannot_invent_a_permission_name(): void
    {
        $this->actAsSuperAdmin();
        $role = $this->anyRole();

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('assign.roles.permissions', $role->id), [
                'permission' => self::PROBE_PERMISSION,
                'status' => 1,
            ])
            ->assertStatus(422);

        $this->assertFalse($this->probePermissionExists(), 'an unknown permission name was minted');
    }

    /**
     * The reason the check lives in the controller and not on the route.
     *
     * RoleController answers on two mounts: `roles/*` and a separate,
     * hand-written `admin/roles/*` block at routes/web.php:180-185. Gating one
     * route group leaves the other open on the same methods - the same shape as
     * PR #309 F-041 in the member module. Both are asserted here, in one test,
     * because splitting them invites a later change to fix one and leave the
     * other.
     *
     * @dataProvider roleMutationRoutes
     */
    public function test_every_mount_of_the_role_mutations_refuses_an_ordinary_account(
        string $method,
        string $uri
    ): void {
        $this->actAsOrdinaryUser();
        $role = $this->anyRole();

        $before = DB::table('roles')->count();

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->call($method, str_replace('{id}', (string) $role->id, $uri), [
                'name' => 'zz probe role f027',
                'user_role_name' => 'zz probe role f027',
            ])
            ->assertForbidden();

        $this->assertSame(
            $before,
            DB::table('roles')->count(),
            "{$method} {$uri} refused the caller and changed the roles table anyway"
        );
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function roleMutationRoutes(): array
    {
        return [
            'roles store' => ['POST', '/roles'],
            'roles update' => ['PUT', '/roles/{id}'],
            'roles destroy' => ['DELETE', '/roles/{id}'],
            'admin mount: store' => ['POST', '/admin/roles'],
            'admin mount: update' => ['PUT', '/admin/roles/{id}'],
            'admin mount: destroy' => ['DELETE', '/admin/roles/{id}'],
        ];
    }
}
