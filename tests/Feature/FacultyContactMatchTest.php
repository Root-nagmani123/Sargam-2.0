<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * get_auth_faculty_master_pk() / provision_faculty_profile_from_employee_user()
 * resolve a login to a faculty_master row by mobile or email when there is no
 * employee link. Contact data is full of placeholders ("0", "2303",
 * "1234512345", "LBSNAA@nic.in") shared by unrelated rows; matching on them
 * tied an employee to someone else's faculty record (and wrote that link).
 * faculty_pk_by_contact() only accepts a real, unique contact whose faculty
 * row shares a name with the person.
 */
class FacultyContactMatchTest extends TestCase
{
    private bool $inTransaction = false;

    /** Manual transaction so a database-less host skips instead of erroring in setUp(). */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('No database connection: '.$e->getMessage());
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

        parent::tearDown();
    }

    private function faculty(string $fullName, ?string $mobile, ?string $email = null, ?int $employeePk = null): int
    {
        return DB::table('faculty_master')->insertGetId([
            'faculty_type' => '2', 'first_name' => $fullName, 'full_name' => $fullName,
            'country_master_pk' => 0, 'state_master_pk' => 0, 'state_district_mapping_pk' => 0, 'city_master_pk' => 0,
            'mobile_no' => $mobile, 'email_id' => $email, 'employee_master_pk' => $employeePk,
        ]);
    }

    /** A unique 10-digit mobile no faculty row holds yet. */
    private function freshMobile(): string
    {
        do {
            $mobile = '9'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (DB::table('faculty_master')->where('mobile_no', $mobile)->exists());

        return $mobile;
    }

    /**
     * An employee login (user_category E) with the Faculty session role, logged in.
     * Returns the employee pk.
     */
    private function loginEmployee(string $first, string $last, $mobile, ?string $email): int
    {
        $employeePk = DB::table('employee_master')->insertGetId([
            'first_name' => $first, 'last_name' => $last, 'mobile' => $mobile, 'email' => $email,
        ]);
        $userPk = DB::table('user_credentials')->insertGetId([
            'user_name' => 'fcm.'.$employeePk, 'user_id' => $employeePk, 'user_category' => 'E',
        ]);
        $this->actingAs(User::find($userPk));
        Session::put('user_roles', ['Faculty', 'Employee']);

        return $employeePk;
    }

    public function test_placeholder_shared_or_name_mismatched_contacts_identify_no_one(): void
    {
        $mobile = $this->freshMobile();
        $this->faculty('Fcm Rao Unique', $mobile);
        $shared = $this->freshMobile();
        $this->faculty('Fcm Rao One', $shared);
        $this->faculty('Fcm Rao Two', $shared);
        $this->faculty('Fcm Zero', '0');

        $this->assertNull(faculty_pk_by_contact('0', null, ['Fcm', 'Zero']), 'placeholder "0"');
        $this->assertNull(faculty_pk_by_contact('2303', null, ['Fcm', 'Zero']), 'short number');
        $this->assertNull(faculty_pk_by_contact('9999999999', null, ['Fcm', 'Zero']), 'one repeated digit');
        $this->assertNull(faculty_pk_by_contact($shared, null, ['Fcm', 'Rao']), 'mobile on two rows');
        $this->assertNull(faculty_pk_by_contact($mobile, null, ['Subroto', 'Bagchi']), 'unique mobile, different person');
        $this->assertNull(faculty_pk_by_contact(null, 'not-an-email', ['Fcm', 'Rao']), 'not an email');
        $this->assertNull(faculty_pk_by_contact($mobile, null, ['', '']), 'no name to compare');
    }

    public function test_a_real_unique_contact_with_an_agreeing_name_identifies_the_faculty(): void
    {
        $mobile = $this->freshMobile();
        $byMobile = $this->faculty('Ms Fcmpryati Fcmsharma', $mobile);
        $email = 'fcm.'.$mobile.'@example.gov.in';
        $byEmail = $this->faculty('Dr Fcmlalitha Fcmlakshmi', null, $email);

        $this->assertSame($byMobile, faculty_pk_by_contact($mobile, null, ['Fcmpryati', 'Fcmsharma']));
        $this->assertSame($byMobile, faculty_pk_by_contact(' '.$mobile.' ', null, ['', 'FCMSHARMA']), 'trimmed, case-insensitive, one name is enough');
        $this->assertSame($byEmail, faculty_pk_by_contact('0', $email, ['Fcmlalitha', 'Venkataramani']));
    }

    public function test_provisioning_does_not_tie_an_employee_to_an_unrelated_faculty_by_a_placeholder_mobile(): void
    {
        // An unrelated, unlinked faculty with mobile "0" (as "Subroto Bagchi" had);
        // the database may already hold more such rows, none of them may be taken.
        $this->faculty('Fcm Subroto Stranger', '0', 'fcm-stranger@example.gov.in');
        $strangers = DB::table('faculty_master')->whereNull('employee_master_pk')->where('mobile_no', '0')->pluck('pk')->map(fn ($pk) => (int) $pk)->all();
        $employeePk = $this->loginEmployee('Fcmabhijit', 'Fcmlokre', 0, 'a@a.com');

        $pk = provision_faculty_profile_from_employee_user();

        $this->assertNotContains($pk, $strangers);
        $this->assertSame(0, DB::table('faculty_master')->whereIn('pk', $strangers)->whereNotNull('employee_master_pk')->count(), 'every mobile-"0" faculty must stay unlinked');
        $this->assertSame($employeePk, (int) DB::table('faculty_master')->where('pk', $pk)->value('employee_master_pk'));
        $this->assertSame('Fcmabhijit Fcmlokre', DB::table('faculty_master')->where('pk', $pk)->value('full_name'));
    }

    public function test_provisioning_links_the_faculty_a_real_mobile_and_name_identify(): void
    {
        $mobile = $this->freshMobile();
        $own = $this->faculty('Ms Fcmpryati Fcmsharma', $mobile);
        $employeePk = $this->loginEmployee('Fcmpryati', 'Fcmsharma', (int) $mobile, 'fcm-pyati@example.com');

        $this->assertSame($own, provision_faculty_profile_from_employee_user());
        $this->assertSame($employeePk, (int) DB::table('faculty_master')->where('pk', $own)->value('employee_master_pk'));
    }

    public function test_provisioning_never_relinks_a_faculty_already_tied_to_another_employee(): void
    {
        $mobile = $this->freshMobile();
        $otherEmployee = DB::table('employee_master')->insertGetId(['first_name' => 'Fcm', 'last_name' => 'Other']);
        $taken = $this->faculty('Ms Fcmpryati Fcmsharma', $mobile, null, $otherEmployee);
        $this->loginEmployee('Fcmpryati', 'Fcmsharma', (int) $mobile, null);

        $pk = provision_faculty_profile_from_employee_user();

        $this->assertNotSame($taken, $pk);
        $this->assertSame($otherEmployee, (int) DB::table('faculty_master')->where('pk', $taken)->value('employee_master_pk'));
    }

    /**
     * PR #335 review F-013: a real, unique mobile names an unlinked faculty
     * spelt differently ("Premkumar VR" / "Prem V R"). The name rule rejects
     * the match, and the resolver used to fall through to the create branch,
     * writing a second faculty row for the same person. It must write nothing.
     */
    public function test_a_unique_contact_naming_an_unlinked_faculty_under_another_spelling_writes_no_faculty_row(): void
    {
        $mobile = $this->freshMobile();
        $existing = $this->faculty('Premkumar  VR', $mobile);
        $employeePk = $this->loginEmployee('Prem', 'V R', (int) $mobile, null);
        DB::table('user_credentials')->where('user_name', 'fcm.'.$employeePk)->update(['mobile_no' => $mobile]);
        $this->actingAs(User::where('user_name', 'fcm.'.$employeePk)->first());
        $before = DB::table('faculty_master')->count();

        $this->assertNull(get_auth_faculty_master_pk());
        $this->assertNull(provision_faculty_profile_from_employee_user());

        $this->assertSame($before, DB::table('faculty_master')->count(), 'no faculty_master row may be written');
        $this->assertSame(0, DB::table('faculty_master')->where('employee_master_pk', $employeePk)->count());
        $this->assertNull(DB::table('faculty_master')->where('pk', $existing)->value('employee_master_pk'), 'the existing record stays unlinked, for an administrator to link');
    }

    public function test_auth_lookup_does_not_resolve_a_login_through_a_shared_placeholder_mobile(): void
    {
        $this->faculty('Fcm Placeholder Holder', '1234512345');
        $this->faculty('Fcm Placeholder Holder Two', '1234512345');
        $holders = DB::table('faculty_master')->where('mobile_no', '1234512345')->pluck('pk')->map(fn ($pk) => (int) $pk)->all();
        $employeePk = $this->loginEmployee('Fcmabhijit', 'Fcmlokre', 1234512345, null);
        DB::table('user_credentials')->where('user_id', $employeePk)->update(['mobile_no' => '1234512345']);
        $this->actingAs(User::where('user_id', $employeePk)->first());

        $pk = get_auth_faculty_master_pk();

        $this->assertNotContains($pk, $holders);
        $this->assertSame(0, DB::table('faculty_master')->whereIn('pk', $holders)->where('employee_master_pk', $employeePk)->count());
    }
}
