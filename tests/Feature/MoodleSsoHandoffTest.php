<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pins the two Moodle SSO entry points, which sit outside the auth group and log
 * the caller in as whoever the request names (PR #317 L-10).
 *
 * Every refusal is asserted as "nobody is logged in", not as a status code:
 * studentFacultyFeedback() wraps its abort(403) in a catch-all that redirects
 * back, so the status is not what protects anyone - Auth::login() not running is.
 */
class MoodleSsoHandoffTest extends TestCase
{
    use DatabaseTransactions {
        beginDatabaseTransaction as openTransaction;
    }

    /**
     * The trait opens its transaction inside parent::setUp(), before any test body,
     * so with no database it throws there and the skips below never fire. Probe
     * first, so a missing database is a skip rather than an error.
     */
    public function beginDatabaseTransaction()
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('the Moodle SSO tests need the application database');
        }

        $this->openTransaction();
    }

    private const KEY = '0123456789abcdef';
    private const IV = 'fedcba9876543210';

    private function anyUser(): User
    {
        // Both routes trim the decrypted name, as they always have, so a user_name
        // stored with surrounding whitespace (78 rows on testsargam6) can never match.
        $user = User::query()
            ->whereNotNull('user_name')
            ->where('user_name', '!=', '')
            ->whereRaw('user_name = TRIM(user_name)')
            ->first();
        if (!$user) {
            $this->markTestSkipped('no user with a user_name in this database');
        }

        return $user;
    }

    private function token(string $username, string $key, string $iv): string
    {
        return base64_encode(openssl_encrypt($username, 'AES-128-CBC', $key, 0, $iv));
    }

    private function configureKey(?string $key, ?string $iv): void
    {
        config(['services.moodle.key' => $key, 'services.moodle.iv' => $iv]);
    }

    public function test_a_plaintext_username_logs_nobody_in(): void
    {
        $this->configureKey(self::KEY, self::IV);
        $user = $this->anyUser();

        $this->get('/feedback/student-feedback-url?username=' . urlencode($user->user_name))
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_a_valid_token_logs_in_the_user_it_names(): void
    {
        $this->configureKey(self::KEY, self::IV);
        $user = $this->anyUser();

        $this->get('/feedback/student-feedback-url?token=' . urlencode($this->token($user->user_name, self::KEY, self::IV)))
            ->assertRedirect(route('feedback.get.studentFeedbackUrl'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_token_under_the_wrong_key_logs_nobody_in(): void
    {
        $this->configureKey(self::KEY, self::IV);
        $user = $this->anyUser();
        $forged = $this->token($user->user_name, 'attacker-key-000', self::IV);

        $this->get('/feedback/student-feedback-url?token=' . urlencode($forged))->assertForbidden();
        $this->assertGuest();

        $this->get('/student-faculty-feedback?token=' . urlencode($forged));
        $this->assertGuest();
    }

    /**
     * With MOODLE_SHARED_KEY / MOODLE_SHARED_IV unset, openssl_decrypt() treats the
     * null key and IV as zero bytes, so a token anyone can mint used to decrypt.
     */
    public function test_an_unset_key_refuses_a_zero_key_forgery_on_the_feedback_url(): void
    {
        $this->configureKey(null, null);
        $user = $this->anyUser();
        $forged = $this->token($user->user_name, '', str_repeat("\0", 16));

        $this->get('/feedback/student-feedback-url?token=' . urlencode($forged))->assertForbidden();
        $this->assertGuest();
    }

    /**
     * Separate from the test above so this route is exercised even when that one
     * fails first. Before the fix this route logged the forger in.
     */
    public function test_an_unset_key_refuses_a_zero_key_forgery_on_the_faculty_feedback_route(): void
    {
        $this->configureKey(null, null);
        $user = $this->anyUser();
        $forged = $this->token($user->user_name, '', str_repeat("\0", 16));

        $this->get('/student-faculty-feedback?token=' . urlencode($forged));
        $this->assertGuest();
    }
    /**
     * A stored user_name with stray whitespace (78 rows on testsargam6) used to be
     * unreachable: the decrypted name is trimmed and the lookup was exact.
     */
    public function test_a_user_name_stored_with_whitespace_can_sign_in(): void
    {
        $this->configureKey(self::KEY, self::IV);
        $name = 'zz_sso_ws_'.bin2hex(random_bytes(4));
        $pk = DB::table('user_credentials')->insertGetId(['user_name' => ' '.$name.' ']);

        $this->get('/feedback/student-feedback-url?token='.urlencode($this->token($name, self::KEY, self::IV)))
            ->assertRedirect(route('feedback.get.studentFeedbackUrl'));

        $this->assertAuthenticatedAs(User::find($pk));
    }

    /**
     * Two accounts that differ only by whitespace, and neither an exact match: we
     * cannot tell which one Moodle means, so nobody is signed in.
     */
    public function test_an_ambiguous_trimmed_user_name_signs_nobody_in(): void
    {
        $this->configureKey(self::KEY, self::IV);
        $name = 'zz_sso_amb_'.bin2hex(random_bytes(4));
        DB::table('user_credentials')->insert([['user_name' => ' '.$name], ['user_name' => $name.' ']]);

        $this->get('/feedback/student-feedback-url?token='.urlencode($this->token($name, self::KEY, self::IV)))
            ->assertNotFound();

        $this->assertGuest();
    }

    // -- The third token login: the `auth` middleware itself -----------------
    //
    // App\Http\Middleware\Authenticate accepts ?token= only on the landing paths
    // MoodleSso::tokenPaths() lists - the timetable by default. It used to accept
    // it on every route in the auth group (PR #324 review F-001).

    private const AUTH_ROUTE = '/calendar';

    /** An auth-only GET route that is NOT a Moodle landing path. */
    private const OTHER_AUTH_ROUTE = '/directory/lbsnaa';

    public function test_a_valid_token_is_ignored_off_the_landing_paths(): void
    {
        $this->configureKey(self::KEY, self::IV);
        $user = $this->anyUser();

        $this->get(self::OTHER_AUTH_ROUTE.'?token='.urlencode($this->token($user->user_name, self::KEY, self::IV)));

        $this->assertGuest();
    }

    /**
     * ?token= that does not resolve to an account must leave a signed-in session
     * alone, on a landing path or not. It used to log the user out on presence
     * alone, so any link with ?token= appended signed a victim out (F-003).
     */
    public function test_an_invalid_token_does_not_end_the_current_session(): void
    {
        $this->configureKey(self::KEY, self::IV);
        $user = $this->anyUser();

        // The request is expected to reach the timetable page itself, which is not
        // under test, so what it renders after the middleware has run is beside the
        // point - including a buffer the admin layout leaves open until PR #322 lands.
        foreach (['', 'garbage', $this->token('no-such-user-'.bin2hex(random_bytes(4)), self::KEY, self::IV)] as $token) {
            $this->actingAs($user);
            $level = ob_get_level();
            $this->get(self::AUTH_ROUTE.'?token='.urlencode($token));
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            $this->assertAuthenticatedAs($user);
        }
    }

    /** A token for the account already signed in is not a reason to sign it out. */
    public function test_a_token_for_the_signed_in_user_keeps_the_session(): void
    {
        $this->configureKey(self::KEY, self::IV);
        $user = $this->anyUser();

        $this->actingAs($user)
            ->withSession(['marker' => 'kept'])
            ->get(self::AUTH_ROUTE.'?token='.urlencode($this->token($user->user_name, self::KEY, self::IV)))
            ->assertRedirect()
            ->assertSessionHas('marker', 'kept');

        $this->assertAuthenticatedAs($user);
    }

    /** The middleware resolves a whitespace-stored user_name exactly as the feedback routes do (F-004). */
    public function test_a_user_name_stored_with_whitespace_signs_in_through_the_auth_middleware(): void
    {
        $this->configureKey(self::KEY, self::IV);
        $name = 'zz_sso_mw_'.bin2hex(random_bytes(4));
        $pk = DB::table('user_credentials')->insertGetId(['user_name' => ' '.$name.' ']);

        $this->get(self::AUTH_ROUTE.'?token='.urlencode($this->token($name, self::KEY, self::IV)))
            ->assertRedirect();

        $this->assertAuthenticatedAs(User::find($pk));
    }

    /**
     * Session roles follow the account, as they do on a password login. They used
     * to be ['Student-OT'] whoever the token named (F-006).
     */
    public function test_a_token_login_takes_the_session_roles_of_the_account(): void
    {
        $this->configureKey(self::KEY, self::IV);

        $staffId = DB::table('model_has_roles as mr')
            ->join('user_credentials as u', 'u.pk', '=', 'mr.model_id')
            ->where('u.user_category', '!=', 'S')
            ->whereNotNull('u.user_name')->where('u.user_name', '!=', '')
            ->whereRaw('u.user_name = TRIM(u.user_name)')
            ->value('u.pk');
        $staff = $staffId ? User::find($staffId) : null;
        if (! $staff) {
            $this->markTestSkipped('no non-student user with a role in this database');
        }

        $this->get(self::AUTH_ROUTE.'?token='.urlencode($this->token($staff->user_name, self::KEY, self::IV)))
            ->assertRedirect();

        $this->assertAuthenticatedAs($staff);
        $expected = $staff->roles()->pluck('name')->all();
        $this->assertSame($expected, session('user_roles'));
        $this->assertNotContains('Student-OT', $expected, 'the fixture should not itself be a trainee');
    }

    /**
     * With the key and IV unset, the middleware used to decrypt with zero bytes,
     * so an unauthenticated caller could mint a token for any user_name and be
     * logged in as that user on every auth-only route.
     */
    public function test_an_unset_key_refuses_a_zero_key_forgery_in_the_auth_middleware(): void
    {
        $this->configureKey(null, null);
        $user = $this->anyUser();
        $forged = $this->token($user->user_name, '', str_repeat("\0", 16));

        $this->get(self::AUTH_ROUTE.'?token='.urlencode($forged));
        $this->assertGuest();
    }

    /**
     * The middleware also carried a literal fallback key and IV in source. A key
     * anyone can read is no key: a token minted with it must not log anyone in.
     */
    public function test_the_old_hardcoded_fallback_key_logs_nobody_in(): void
    {
        // The fallback was config()'s DEFAULT, so it applies only when the keys are
        // absent from config altogether - a null value would not reach it.
        config(['services.moodle' => []]);
        $user = $this->anyUser();
        $forged = $this->token($user->user_name, '1234567890abcdef', 'abcdef1234567890');

        $this->get(self::AUTH_ROUTE.'?token='.urlencode($forged));
        $this->assertGuest();
    }

    /** The control: with the key configured, a genuine Moodle token still signs in. */
    public function test_a_valid_token_still_signs_in_through_the_auth_middleware(): void
    {
        $this->configureKey(self::KEY, self::IV);
        $user = $this->anyUser();

        $this->get(self::AUTH_ROUTE.'?token='.urlencode($this->token($user->user_name, self::KEY, self::IV)))
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    /** ?token[]= made urldecode() throw a TypeError the middleware's catch (\Exception) missed. */
    public function test_an_array_token_is_refused_quietly(): void
    {
        $this->configureKey(self::KEY, self::IV);

        $response = $this->get(self::AUTH_ROUTE.'?token[]=x');

        $this->assertLessThan(500, $response->getStatusCode());
        $this->assertGuest();
    }
}
