<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Messaging\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * F-011 (PR #334): the SMS channel is audited like email, and a login outside the
 * group cannot message it. The SMS gateway is mocked — nothing is sent.
 */
class MyGroupsMessageSmsAndMembershipTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** @return array{0: User, 1: int, 2: int} */
    private function senderAndRecipientWithMobile(): array
    {
        $row = DB::table('user_credentials as u')
            ->join('student_course_group_map as mine', 'mine.student_master_pk', '=', 'u.user_id')
            ->join('group_type_master_course_master_map as g', 'g.pk', '=', 'mine.group_type_master_course_master_map_pk')
            ->join('student_course_group_map as other', 'other.group_type_master_course_master_map_pk', '=', 'g.pk')
            ->join('student_master as sm', 'sm.pk', '=', 'other.student_master_pk')
            ->where('u.user_category', 'S')
            ->where('mine.active_inactive', 1)
            ->where('g.active_inactive', 1)
            ->where('other.active_inactive', 1)
            ->whereColumn('other.student_master_pk', '!=', 'mine.student_master_pk')
            ->whereNotNull('sm.contact_no')
            ->where('sm.contact_no', '!=', '')
            ->first(['u.pk as user_pk', 'u.user_id as sender_student', 'g.pk as map_pk', 'sm.pk as recipient']);

        if (! $row) {
            $this->markTestSkipped('no OT whose group has another member with a mobile number');
        }

        return [User::findOrFail($row->user_pk), (int) $row->map_pk, (int) $row->recipient];
    }

    public function test_an_sms_send_goes_through_the_gateway_and_is_audited(): void
    {
        config(['cache.limiter' => 'array']);
        Log::spy();
        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('sendBulk')->once()->andReturn([]);
        $this->app->instance(SmsService::class, $sms);

        [$sender, $mapPk, $recipient] = $this->senderAndRecipientWithMobile();

        $this->as($sender, ['Student-OT'])
            ->postJson("/dashboard/my-groups/{$mapPk}/students/message", [
                'channel' => 'sms', 'message' => 'Meet at 5', 'student_ids' => [$recipient],
            ])
            ->assertOk();

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context = []) => $message === 'my_groups.message'
                && $context['user_pk'] === $sender->pk
                && $context['group_map_pk'] === $mapPk
                && $context['channel'] === 'sms'
                && $context['sent'] === 1
                && ! array_key_exists('message', $context))
            ->once();
    }

    public function test_an_officer_trainee_outside_the_group_is_refused_and_nothing_is_sent(): void
    {
        config(['cache.limiter' => 'array']);
        $sms = Mockery::mock(SmsService::class);
        $sms->shouldNotReceive('sendBulk');
        $this->app->instance(SmsService::class, $sms);

        [, $mapPk, $recipient] = $this->senderAndRecipientWithMobile();

        $outsider = DB::table('user_credentials as u')
            ->where('u.user_category', 'S')
            ->whereNotNull('u.user_id')
            ->whereNotExists(fn ($q) => $q->from('student_course_group_map as s')
                ->whereColumn('s.student_master_pk', 'u.user_id')
                ->where('s.group_type_master_course_master_map_pk', $mapPk)
                ->where('s.active_inactive', 1))
            ->value('u.pk');
        if (! $outsider) {
            $this->markTestSkipped('no OT outside the group');
        }

        $this->as(User::findOrFail($outsider), ['Student-OT'])
            ->postJson("/dashboard/my-groups/{$mapPk}/students/message", [
                'channel' => 'sms', 'message' => 'x', 'student_ids' => [$recipient],
            ])
            ->assertForbidden();
    }
}
