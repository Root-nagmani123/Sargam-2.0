<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserRoleMaster;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for PR #319 review round 8, F-036 (Low): the step-5 upload
 * error messages stated limits that contradicted the rules actually enforced —
 * "must not exceed 2MB" on a rule that enforces 500KB, and a type list naming
 * doc/docx on a rule that only accepts pdf/jpg/jpeg/png. The rules themselves
 * were already correct; only the messages had drifted, so this fix touches
 * StoreMemberStep5Request::messages() only — the enforced rules are unchanged.
 */
class MemberStep5UploadMessageTest extends TestCase
{
    use DatabaseTransactions;

    private function makeActor(string $suffix): User
    {
        $user = new User();
        $user->timestamps = false;
        $user->first_name = 'Test';
        $user->last_name = $suffix;
        $user->user_name = 'step5_msg_test_' . $suffix . '_' . uniqid();
        $user->user_category = 'E';
        $user->reg_date = now();
        $user->save();

        // member.store is admin-only (MemberController::store() aborts unless
        // actingUserCanManageMembers(), i.e. hasRole('Super Admin') — creating a member is
        // never self-service). That gate did not exist when this test was first written; it
        // arrived via the PR's own authorization hardening. Without this, both tests below
        // hit 403 before ever reaching the picture/document validation they exercise.
        $user->assignRole('Super Admin');

        return $user;
    }

    private function basicMemberPayload(string $suffix): array
    {
        $castePk = DB::table('caste_category_master')->where('active_inactive', 1)->value('pk');
        $countryPk = DB::table('country_master')->value('pk');
        $statePk = DB::table('state_master')->value('pk');
        $departmentPk = DB::table('department_master')->where('pk', '>', 0)->value('pk');
        $designationPk = DB::table('designation_master')->value('pk');
        $groupPk = DB::table('employee_group_master')->value('pk');
        $employeeTypePk = DB::table('employee_type_master')->value('pk');
        $doctorPk = UserRoleMaster::where('user_role_display_name', 'Doctor')->value('pk');

        $unique = $suffix . '_' . uniqid();

        return [
            'first_name' => 'Wizard',
            'last_name' => $suffix,
            'father_husband_name' => 'Father Name',
            'marital_status' => 'Unmarried',
            'gender' => 'Male',
            'caste_category' => $castePk,
            'date_of_birth' => '1990-01-01',
            'type' => $employeeTypePk,
            'id' => 'WZ-' . $unique,
            'group' => $groupPk,
            'designation' => $designationPk,
            'userid' => 'wizard_uid_' . $unique,
            'section' => $departmentPk,
            'userrole' => [$doctorPk],
            'address' => 'Some Address',
            'country' => $countryPk,
            'state' => $statePk,
            'city' => 'Some City',
            'postal' => '110001',
            'permanentaddress' => 'Some Address',
            'permanentcountry' => $countryPk,
            'permanentstate' => $statePk,
            'permanentcity' => 'Some City',
            'permanentpostal' => '110001',
            'personalemail' => 'wizard_' . $unique . '@example.com',
            'officialemail' => 'wizard_official_' . $unique . '@example.com',
            'mnumber' => '9999999998',
        ];
    }

    /** F-036: the picture-too-large message must state the limit the rule actually enforces (500KB, not 2MB). */
    public function test_oversized_picture_message_states_the_enforced_500kb_limit(): void
    {
        $actor = $this->makeActor('PictureSize');

        $payload = $this->basicMemberPayload('PictureSize');
        $payload['picture'] = UploadedFile::fake()->image('oversized.jpg')->size(600);

        $response = $this->actingAs($actor)->post(route('member.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.picture.0', 'Picture size must not exceed 500 KB.');
    }

    /** F-036: the document-type message must list only the types the rule actually accepts (no doc/docx). */
    public function test_unsupported_document_type_message_lists_only_the_enforced_types(): void
    {
        $actor = $this->makeActor('DocType');

        $payload = $this->basicMemberPayload('DocType');
        $payload['additionaldocument'] = UploadedFile::fake()->create('supporting.docx', 100);

        $response = $this->actingAs($actor)->post(route('member.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.additionaldocument.0', 'Document must be of type: pdf, jpg, jpeg, or png.');
    }
}
