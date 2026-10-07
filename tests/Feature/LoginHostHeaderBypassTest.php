<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PR #309 review F-066: the passwordless sign-in branch of LoginController::authenticate()
 * was chosen by request()->getHost() - the client-controlled Host header, with TrustHosts
 * not registered - so a request carrying `Host: localhost` (or one of the listed IPs) signed
 * in as any user with no password. It is now chosen by APP_ENV, which a request cannot set.
 *
 * The actor is a student-category (`S`) account: outside `local` its password is checked
 * against a fixed value without an LDAP call, so a wrong password is refused
 * deterministically. Every write (failed-attempt counters, last_login) is rolled back.
 */
class LoginHostHeaderBypassTest extends TestCase
{
    use DatabaseTransactions;

    private function student(): User
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

        return $user;
    }

    private function loginAs(User $user, string $host)
    {
        // Laravel skips CSRF only while APP_ENV is `testing`; these tests switch the
        // environment, so CSRF - and only CSRF - is taken out, or every request is a 419
        // that never reaches authenticate().
        // The host goes in the URL: a request built from route('post_login') carries
        // APP_URL's host, and the URL's host overrides any HTTP_HOST server variable.
        return $this->withoutMiddleware(VerifyCsrfToken::class)
            ->from('/')
            ->post('http://'.$host.'/login', [
                'username' => $user->user_name,
                'password' => 'definitely-not-the-password',
            ]);
    }

    /** @return array<string, array{0: string}> */
    public static function spoofedHosts(): array
    {
        return [
            'localhost' => ['localhost'],
            '127.0.0.1' => ['127.0.0.1'],
            'dev.local' => ['dev.local'],
            'first listed IP' => ['98.70.99.215'],
            'second listed IP' => ['74.225.234.234'],
        ];
    }

    /**
     * @dataProvider spoofedHosts
     */
    public function test_a_spoofed_host_header_does_not_sign_in_without_a_password_outside_local(string $host): void
    {
        $this->app['env'] = 'production';
        $user = $this->student();

        $this->loginAs($user, $host);

        $this->assertGuest();
        $this->assertSame(
            1,
            (int) DB::table('user_credentials')->where('pk', $user->pk)->value('failed_login_attempts')
                - (int) $user->failed_login_attempts,
            'the wrong password was not even counted as a failed attempt'
        );
    }

    /** CONTROL - local development keeps its passwordless sign-in, whatever the host. */
    public function test_local_environment_still_signs_in_without_a_password(): void
    {
        $this->app['env'] = 'local';
        $user = $this->student();

        $this->loginAs($user, 'sargam.example.test');

        $this->assertAuthenticatedAs($user);
    }
}
