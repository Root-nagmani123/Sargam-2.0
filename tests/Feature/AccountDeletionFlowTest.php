<?php

namespace Tests\Feature;

use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Request -> email OTP -> pending -> reviewer (Computer Section) verifies -> admin approves -> login blocked.
 * Writes roll back.
 */
class AccountDeletionFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function superAdminPks()
    {
        return fn ($q) => $q->select('model_id')->from('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'Super Admin');
    }

    /** Non-admin users with an email; the first files requests, the second reviews. */
    private function plainUsers(int $count): array
    {
        $users = User::query()->where('email_id', 'like', '%@%')
            ->whereNotIn('pk', $this->superAdminPks())->limit($count)->get()->all();
        if (count($users) < $count) {
            $this->markTestSkipped("Needs {$count} non-admin users with an email.");
        }

        return $users;
    }

    private function superAdmin(): User
    {
        $user = User::query()->whereIn('pk', $this->superAdminPks())->first();
        if (! $user) {
            $this->markTestSkipped('No Super Admin user to approve as.');
        }

        return $user;
    }

    private function reviewer(User $user): User
    {
        $user->assignRole('Computer Section');

        return $user;
    }

    private function form(User $user, array $overrides = []): array
    {
        return $overrides + [
            'user_name' => $user->user_name,
            'reason' => AccountDeletionRequest::REASONS[0],
            'comments' => '',
            'confirm' => '1',
        ];
    }

    private function fileRequest(User $user): AccountDeletionRequest
    {
        $this->actingAs($user)->postJson(route('account-deletion.otp'), $this->form($user))->assertOk();
        $otp = Cache::get('fc_otp:account_deletion:'.$user->pk);
        $this->postJson(route('account-deletion.verify'), ['otp' => $otp])->assertOk();

        return AccountDeletionRequest::where('user_credentials_pk', $user->pk)->latest('id')->firstOrFail();
    }

    private function decide(string $stage, AccountDeletionRequest $request, string $decision, ?string $remarks = 'checked')
    {
        $route = $stage === 'review' ? 'admin.account-deletion-review.decide' : 'admin.account-deletion-requests.decide';

        return $this->postJson(route($route, $request->id), array_filter(['decision' => $decision, 'remarks' => $remarks]));
    }

    public function test_user_files_a_request_only_after_a_valid_otp(): void
    {
        [$user] = $this->plainUsers(1);
        $this->actingAs($user)->get(route('account-deletion.index'))->assertOk()->assertSee('Request account deletion');

        $this->postJson(route('account-deletion.otp'), $this->form($user, ['user_name' => 'someone-else']))
            ->assertStatus(422)->assertJsonValidationErrors('user_name');
        $this->postJson(route('account-deletion.otp'), $this->form($user, ['reason' => AccountDeletionRequest::REASON_OTHER]))
            ->assertStatus(422)->assertJsonValidationErrors('comments');
        $this->postJson(route('account-deletion.otp'), $this->form($user, ['confirm' => null]))
            ->assertStatus(422)->assertJsonValidationErrors('confirm');

        $this->postJson(route('account-deletion.otp'), $this->form($user))
            ->assertOk()->assertJsonStructure(['masked_email', 'expires_in', 'resend_in']);
        $otp = Cache::get('fc_otp:account_deletion:'.$user->pk);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $otp);

        $wrong = $otp === '000000' ? '111111' : '000000';
        $this->postJson(route('account-deletion.verify'), ['otp' => $wrong])->assertStatus(422);
        $this->assertFalse(AccountDeletionRequest::where('user_credentials_pk', $user->pk)->exists());

        $this->postJson(route('account-deletion.verify'), ['otp' => $otp])->assertOk()->assertJsonStructure(['code']);
        $this->assertSame(AccountDeletionRequest::STATUS_PENDING,
            AccountDeletionRequest::where('user_credentials_pk', $user->pk)->value('status'));

        // A second request while one is open is refused.
        $this->postJson(route('account-deletion.otp'), $this->form($user))->assertStatus(422);
    }

    private function notificationFor(User $receiver, AccountDeletionRequest $request, string $module)
    {
        return DB::table('notifications')->where('receiver_user_id', $receiver->user_id)
            ->where('type', 'account_deletion')->where('module_name', $module)
            ->where('reference_pk', $request->id)->first();
    }

    public function test_reviewers_then_approvers_are_notified_with_a_link_to_their_queue(): void
    {
        [$user, $reviewer] = $this->plainUsers(2);
        $this->reviewer($reviewer);
        $admin = $this->superAdmin();
        $request = $this->fileRequest($user);

        $toReviewer = $this->notificationFor($reviewer, $request, 'Review');
        $this->assertNotNull($toReviewer, 'The Computer Section reviewer is told about the new request.');
        $this->assertStringContainsString($request->code, $toReviewer->message);
        $this->assertStringNotContainsString((string) $request->full_name, $toReviewer->message); // user_id is not unique
        $this->assertStringEndsWith('/admin/setup/account-deletion-review?status=pending',
            notification()->getRedirectUrl($toReviewer->pk));
        $this->assertNull($this->notificationFor($admin, $request, 'Review'), 'Super Admin is not pinged while a reviewer exists.');

        $this->actingAs($reviewer);
        $this->decide('review', $request, 'accept', 'Records checked')->assertOk();

        $toAdmin = $this->notificationFor($admin, $request, 'Approval');
        $this->assertNotNull($toAdmin, 'The approver is told once the request is verified.');
        $this->assertStringEndsWith('/admin/setup/account-deletion-requests?status=verified',
            notification()->getRedirectUrl($toAdmin->pk));

        $this->actingAs($admin);
        $count = DB::table('notifications')->where('type', 'account_deletion')->where('reference_pk', $request->id)->count();
        $this->decide('approve', $request, 'accept', 'Approved')->assertOk();
        $this->assertSame($count, DB::table('notifications')->where('type', 'account_deletion')->where('reference_pk', $request->id)->count(),
            'A final decision sends no further stage notification.');
    }

    public function test_super_admin_reviews_when_no_one_holds_the_review_permission(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'account_deletion_review')->value('id');
        DB::table('model_has_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('model_has_roles')->whereIn('role_id', DB::table('role_has_permissions')
            ->where('permission_id', $permissionId)->pluck('role_id'))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        [$user] = $this->plainUsers(1);
        $request = $this->fileRequest($user);

        $this->assertNotNull($this->notificationFor($this->superAdmin(), $request, 'Review'));
    }

    public function test_reviewer_verifies_then_admin_approves_and_login_is_blocked(): void
    {
        [$user, $reviewer] = $this->plainUsers(2);
        $request = $this->fileRequest($user);

        // The requester holds neither stage.
        $this->actingAs($user)->get(route('admin.account-deletion-review.index'))->assertForbidden();
        $this->get(route('admin.account-deletion-requests.index'))->assertForbidden();

        // Stage 1 belongs to the Computer Section, and only stage 1.
        $this->actingAs($this->reviewer($reviewer))->get(route('admin.account-deletion-review.index'))
            ->assertOk()->assertSee($request->code);
        $this->get(route('admin.account-deletion-requests.index'))->assertForbidden();
        $this->decide('approve', $request, 'accept')->assertForbidden();

        // The admin cannot skip the review.
        $admin = $this->superAdmin();
        $this->actingAs($admin);
        $this->decide('approve', $request, 'accept')->assertStatus(422);
        $this->assertSame('pending', $request->fresh()->status);

        $this->actingAs($reviewer);
        $this->decide('review', $request, 'accept', null)->assertStatus(422)->assertJsonValidationErrors('remarks');
        $this->decide('review', $request, 'accept', 'Records checked')->assertOk();
        $this->assertSame('verified', $request->fresh()->status);
        $this->decide('review', $request, 'reject')->assertStatus(422); // already past stage 1
        $this->assertSame([], $this->outcomeMails($request->email), 'Verifying is not a final outcome: no email yet.');

        // The user still sees an open request and cannot file another.
        $this->actingAs($user)->get(route('account-deletion.index'))->assertSee('awaiting final approval');

        $this->assertFalse(AccountDeletionRequest::blocksLogin($user->pk));
        $this->actingAs($admin);
        $this->decide('approve', $request, 'accept', 'Approved')->assertOk();
        $fresh = $request->fresh();
        $this->assertSame('accepted', $fresh->status);
        $this->assertSame((int) $reviewer->pk, (int) $fresh->reviewed_by);
        $this->assertSame((int) $admin->pk, (int) $fresh->decided_by);
        $this->assertTrue(AccountDeletionRequest::blocksLogin($user->pk));

        $mails = $this->outcomeMails($request->email);
        $this->assertCount(1, $mails);
        $this->assertStringContainsString("{$request->code} approved", $mails[0]->getSubject());
        $this->assertStringContainsString('can no longer be used to sign in', $mails[0]->getTextBody());
        $this->assertStringContainsString('Remarks: Approved', $mails[0]->getTextBody());

        $this->decide('approve', $request, 'reject')->assertStatus(422); // already decided
        $this->assertCount(1, $this->outcomeMails($request->email), 'A refused second decision sends nothing.');
    }

    public function test_an_open_session_is_logged_out_once_the_deletion_is_approved(): void
    {
        [$user, $reviewer] = $this->plainUsers(2);
        $admin = $this->superAdmin();
        $request = $this->fileRequest($user);
        $this->actingAs($this->reviewer($reviewer));
        $this->decide('review', $request, 'accept', 'Records checked')->assertOk();

        // The requester is still signed in and browsing; this also caches the closed-account list without them.
        $this->actingAs($user)->get(route('account-deletion.index'))->assertOk();

        $this->actingAs($admin);
        $this->decide('approve', $request, 'accept', 'Approved')->assertOk();

        // Their very next request ends the session - the approval must have dropped the cached list.
        $this->actingAs($user)->get(route('account-deletion.index'))
            ->assertRedirect('/')->assertSessionHas('error');
        $this->assertGuest();

        $this->actingAs($user)->postJson(route('account-deletion.otp'), [])->assertStatus(401);
        $this->assertGuest();

        // Nobody else is affected.
        $this->actingAs($admin)->get(route('admin.account-deletion-requests.index'))->assertOk();
    }

    /** Outcome emails (not the OTP email) delivered to $email by the array mailer. */
    private function outcomeMails(string $email): array
    {
        return collect(app('mailer')->getSymfonyTransport()->messages())
            ->map(fn ($sent) => $sent->getOriginalMessage())
            ->filter(fn ($m) => str_contains((string) $m->getSubject(), 'Account deletion request')
                && collect($m->getTo())->contains(fn ($a) => $a->getAddress() === $email))
            ->values()->all();
    }

    public function test_a_rejected_request_shows_its_remarks_and_can_be_filed_again(): void
    {
        [$user, $reviewer] = $this->plainUsers(2);
        $request = $this->fileRequest($user);

        $this->actingAs($this->reviewer($reviewer));
        $this->decide('review', $request, 'reject', 'Dues outstanding')->assertOk();
        $this->assertSame('rejected', $request->fresh()->status);

        $mails = $this->outcomeMails($request->email);
        $this->assertCount(1, $mails, 'A stage-1 rejection is final: the requester is emailed.');
        $this->assertStringContainsString("{$request->code} not approved", $mails[0]->getSubject());
        $this->assertStringContainsString('Your account remains active', $mails[0]->getTextBody());
        $this->assertStringContainsString('Remarks: Dues outstanding', $mails[0]->getTextBody());

        $this->actingAs($user)->get(route('account-deletion.index'))
            ->assertSee('Dues outstanding')->assertSee('data-bs-target="#adRequestModal"', false);
        $this->assertFalse(AccountDeletionRequest::blocksLogin($user->pk));
    }
}
