<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/**
 * PR #309 review F-082.
 *
 * The admin/migrate-students routes carried `web` alone and StudentImportController
 * checks nothing itself, so a guest could download the officer-trainee roster export
 * (names, usernames, emails, mobiles). They now need a signed-in account holding the
 * "Data Migration Students" menu permission; Super Admin always passes.
 */
class StudentImportRouteAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSION = 'data_migration_students';

    private function actor(int $offset, ?string $permission = null, bool $superAdmin = false): User
    {
        $user = User::query()->orderBy('pk')->skip($offset)->first();
        if (! $user) {
            $this->markTestSkipped('Not enough user_credentials rows.');
        }

        $user->syncRoles([]);
        $user->syncPermissions([]);
        $role = Role::create(['name' => 'zz_f082_role_'.$offset, 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::where('name', 'dashboard')->where('guard_name', 'web')->firstOrFail());
        if ($permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }
        $user->assignRole($role);
        if ($superAdmin) {
            $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'web')->firstOrFail());
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    public function test_a_guest_is_sent_to_login_and_gets_no_roster(): void
    {
        foreach (['/admin/migrate-students/counts', '/admin/migrate-students/export/migrated/excel'] as $url) {
            $response = $this->get($url);
            $this->assertNotInstanceOf(BinaryFileResponse::class, $response->baseResponse, $url);
            $response->assertRedirect(route('login'));
        }

        $this->post('/admin/migrate-fc-registration', ['selected_pks' => '1'])->assertRedirect(route('login'));
    }

    public function test_a_signed_in_account_without_the_permission_is_refused(): void
    {
        $actor = $this->actor(5);

        foreach (['/admin/migrate-students/counts', '/admin/migrate-students/export/migrated/excel'] as $url) {
            $response = $this->actingAs($actor)->get($url);
            $this->assertNotInstanceOf(BinaryFileResponse::class, $response->baseResponse, $url);
            $response->assertStatus(403);
        }

        $this->actingAs($actor)->post('/admin/migrate-fc-registration', ['selected_pks' => '1'])->assertStatus(403);
    }

    public function test_the_permission_holder_and_super_admin_still_get_through(): void
    {
        $holder = $this->actor(5, self::PERMISSION);
        $this->actingAs($holder)->get('/admin/migrate-students/counts')->assertOk();
        $this->assertInstanceOf(
            BinaryFileResponse::class,
            $this->actingAs($holder)->get('/admin/migrate-students/export/migrated/excel')->baseResponse
        );

        $admin = $this->actor(6, null, true);
        $this->actingAs($admin)->get('/admin/migrate-students/counts')->assertOk();
    }
}
