<?php

namespace Tests\Feature;

use Adldap\AdldapInterface;
use Adldap\Laravel\Facades\Adldap;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * PR #337 review F-004: an earlier fix for F-001 made user_credentials.jbp_password
 * a credential that skipped LDAP for every non-student account. That change is gone;
 * these tests keep it gone. Outside `local`, a non-student login (NULL category
 * included) signs in through LDAP alone: an LDAP refusal or an LDAP error is final,
 * whatever jbp_password holds. Inactive and locked accounts stay out even when LDAP
 * says yes.
 *
 * LDAP is replaced by a fake bound under the facade's accessor (ext-ldap is not
 * needed). The one LDAP-accepted control shows the refusals are LDAP's. Fixtures are
 * fresh rows; every write is rolled back.
 */
class LoginStoredPasswordIsNotACredentialTest extends TestCase
{
    use DatabaseTransactions;

    private const STORED = 'F004-stored-password';

    private object $ldap;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'production';
    }

    /** @param bool|\Throwable $outcome what Adldap::auth()->attempt() does */
    private function ldapAnswers($outcome): void
    {
        $this->ldap = new class($outcome)
        {
            public int $calls = 0;

            public function __construct(private $outcome) {}

            public function auth()
            {
                return $this;
            }

            public function attempt($username, $password): bool
            {
                $this->calls++;
                if ($this->outcome instanceof \Throwable) {
                    throw $this->outcome;
                }

                return $this->outcome;
            }
        };
        $this->app->instance(AdldapInterface::class, $this->ldap);
        Adldap::clearResolvedInstance(AdldapInterface::class);
    }

    /** A login whose jbp_password is STORED, hashed and in plain text alike. */
    private function login(?string $category, int $employeeStatus = 1, array $extra = []): User
    {
        $employee = DB::table('employee_master')->insertGetId([
            'first_name' => 'F004',
            'last_name' => 'Probe',
            'status' => $employeeStatus,
        ]);
        $pk = DB::table('user_credentials')->insertGetId(array_merge([
            'user_name' => 'f004-probe-'.uniqid(),
            'user_id' => $employee,
            'user_category' => $category,
            'jbp_password' => Hash::make(self::STORED),
        ], $extra));

        return User::findOrFail($pk);
    }

    private function signIn(User $user, string $password)
    {
        // Laravel skips CSRF only under APP_ENV=testing, which setUp() switched off.
        return $this->withoutMiddleware(VerifyCsrfToken::class)
            ->from('/')
            ->post('/login', ['username' => $user->user_name, 'password' => $password]);
    }

    /** @return array<string, array{0: ?string}> */
    public static function nonStudentCategories(): array
    {
        return ['employee' => ['E'], 'NULL category' => [null]];
    }

    /** @dataProvider nonStudentCategories */
    public function test_ldap_refusal_is_final_even_with_the_stored_password(?string $category): void
    {
        $this->ldapAnswers(false);
        $user = $this->login($category);

        $this->signIn($user, self::STORED)->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame(1, $this->ldap->calls, 'LDAP was not consulted');
    }

    /** @dataProvider nonStudentCategories */
    public function test_ldap_error_does_not_fall_back_to_the_stored_password(?string $category): void
    {
        $this->ldapAnswers(new \RuntimeException('LDAP server unreachable'));
        $user = $this->login($category);

        $this->signIn($user, self::STORED)->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_the_stored_hash_itself_is_not_a_password(): void
    {
        $this->ldapAnswers(false);
        $user = $this->login('E', 1, ['jbp_password' => self::STORED]);

        $this->signIn($user, self::STORED);

        $this->assertGuest();
    }

    /** CONTROL - LDAP's yes still signs a non-student in. */
    public function test_control_ldap_acceptance_signs_in(): void
    {
        $this->ldapAnswers(true);
        $user = $this->login('E');

        $this->signIn($user, 'whatever-ldap-accepts');

        $this->assertAuthenticatedAs($user);
    }

    public function test_an_inactive_employee_stays_out_when_ldap_accepts(): void
    {
        $this->ldapAnswers(true);
        $user = $this->login('E', 2);

        $this->signIn($user, self::STORED)->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_a_locked_account_stays_out_when_ldap_accepts(): void
    {
        $this->ldapAnswers(true);
        $user = $this->login('E', 1, ['locked_until' => now()->addMinutes(10)]);

        $this->signIn($user, self::STORED)->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame(0, $this->ldap->calls, 'a locked account reached LDAP');
    }

    public function test_a_student_with_a_wrong_password_is_refused_without_ldap(): void
    {
        $this->ldapAnswers(true);
        $user = $this->login('S');

        $this->signIn($user, 'definitely-not-the-password')->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame(0, $this->ldap->calls, 'a student login reached LDAP');
    }
}
