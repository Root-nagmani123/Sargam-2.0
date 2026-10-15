<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * F-001: outside `local`, LoginController::authenticate() used to accept a
 * single hard-coded literal for every student account, and a second literal
 * that let every non-student account skip LDAP entirely. Both are removed in
 * favour of User::checkStoredPassword(), which checks the signing-in
 * account's own jbp_password (bcrypt, or a legacy 32-char MD5 hash).
 *
 * Fixture passwords are written to a real row's jbp_password inside a
 * transaction and rolled back afterwards; nothing is left mutated in the
 * shared dev database.
 */
class LoginProductionPasswordTest extends TestCase
{
    use DatabaseTransactions;

    private function studentWithPassword(string $hash): User
    {
        $user = User::query()
            ->where('user_category', 'S')
            ->where(function ($q) {
                $q->whereNull('locked_until')->orWhere('locked_until', '<', now());
            })
            ->orderBy('pk')
            ->first();

        if (! $user) {
            $this->markTestSkipped('needs a student-category account that is not locked');
        }

        DB::table('user_credentials')->where('pk', $user->pk)->update(['jbp_password' => $hash]);

        return $user->fresh();
    }

    private function nonStudentWithPassword(string $hash): User
    {
        $user = User::query()
            ->where('user_category', '!=', 'S')
            ->where(function ($q) {
                $q->whereNull('locked_until')->orWhere('locked_until', '<', now());
            })
            ->orderBy('pk')
            ->first();

        if (! $user) {
            $this->markTestSkipped('needs a non-student account that is not locked');
        }

        DB::table('user_credentials')->where('pk', $user->pk)->update(['jbp_password' => $hash]);

        return $user->fresh();
    }

    private function attemptLogin(User $user, string $password)
    {
        return $this->withoutMiddleware(VerifyCsrfToken::class)
            ->from('/')
            ->post(route('post_login'), [
                'username' => $user->user_name,
                'password' => $password,
            ]);
    }

    public function test_student_can_sign_in_with_their_own_bcrypt_password(): void
    {
        $this->app['env'] = 'production';
        $user = $this->studentWithPassword(Hash::make('a-real-student-password'));

        $this->attemptLogin($user, 'a-real-student-password');

        $this->assertAuthenticatedAs($user);
    }

    public function test_student_can_sign_in_with_a_legacy_md5_password(): void
    {
        $this->app['env'] = 'production';
        $user = $this->studentWithPassword(md5('legacy-student-password'));

        $this->attemptLogin($user, 'legacy-student-password');

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_old_shared_student_literal_no_longer_signs_a_student_in(): void
    {
        $this->app['env'] = 'production';
        $user = $this->studentWithPassword(Hash::make('a-real-student-password'));

        $this->attemptLogin($user, 'm2WZjg7iyfqbrPWO3aqDHVQL2PO8ZI6GHxxtVhypINY=');

        $this->assertGuest();
    }

    public function test_the_old_shared_non_student_literal_no_longer_skips_ldap(): void
    {
        $this->app['env'] = 'production';
        $user = $this->nonStudentWithPassword(Hash::make('a-real-staff-password'));

        // The literal must now fall through to the real LDAP attempt (which
        // this test environment cannot satisfy) instead of being accepted
        // outright.
        $this->attemptLogin($user, 'm2WZjg7iyfqbrPWO');

        $this->assertGuest();
    }

    public function test_non_student_can_sign_in_with_their_own_stored_password_without_ldap(): void
    {
        $this->app['env'] = 'production';
        $user = $this->nonStudentWithPassword(Hash::make('a-real-staff-password'));

        $this->attemptLogin($user, 'a-real-staff-password');

        $this->assertAuthenticatedAs($user);
    }
}
