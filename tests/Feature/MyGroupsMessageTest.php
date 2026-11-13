<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Messaging\EmailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * My Groups lets an Officer Trainee send SMS / email to group mates through the
 * Academy's gateway (PR #334 F-005).
 *
 * Whether OTs may do that is a Product owner decision that is NOT on record, so the
 * send is off unless config('my_groups.messaging_enabled') is set. With it on, every
 * send names its sender, is limited by the dedicated `my-groups-message` limiter,
 * and writes an audit row. The limiter's counter is its own: the unnamed
 * throttle:5,60 it replaced shared a per-user key with every other unnamed
 * throttle, and a hit on a 1-minute route gave that key a 1-minute lifetime.
 */
class MyGroupsMessageTest extends TestCase
{
    // Aliased: this class needs its own setUp(), which would otherwise replace
    // the trait's and so never open the rolled-back transaction.
    use RollsBackAgainstAppDatabase {
        setUp as openRollbackTransaction;
    }

    protected function setUp(): void
    {
        $this->openRollbackTransaction();
        // A fresh in-memory limiter, so this test neither reads nor writes the
        // application's shared cache.
        config(['cache.limiter' => 'array']);
    }

    /** @return array{0: User, 1: int, 2: int}  sender, group map pk, a recipient student pk */
    private function senderAndRecipient(): array
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
            ->where('sm.email', 'like', '%@%')
            ->first(['u.pk as user_pk', 'g.pk as map_pk', 'sm.pk as recipient']);

        if (! $row) {
            $this->markTestSkipped('no OT whose group has another member with an email');
        }

        return [User::findOrFail($row->user_pk), (int) $row->map_pk, (int) $row->recipient];
    }

    private function send(User $sender, int $mapPk, int $recipient)
    {
        return $this->as($sender, ['Student-OT'])->postJson("/dashboard/my-groups/{$mapPk}/students/message", [
            'channel' => 'email', 'message' => 'Meet at 5', 'student_ids' => [$recipient],
        ]);
    }

    private function auditRows(User $sender): int
    {
        return DB::table('my_group_message_log')->where('sender_user_pk', $sender->pk)->count();
    }

    public function test_sending_is_off_until_the_environment_enables_it(): void
    {
        Mail::fake();
        [$sender, $mapPk, $recipient] = $this->senderAndRecipient();
        $this->assertFalse((bool) config('my_groups.messaging_enabled'), 'off by default');
        $audited = $this->auditRows($sender);

        $this->send($sender, $mapPk, $recipient)->assertForbidden();

        Mail::assertNothingSent();
        $this->assertSame($audited, $this->auditRows($sender), 'nothing to audit');
    }

    public function test_each_send_is_audited_and_the_sixth_in_an_hour_is_refused(): void
    {
        config(['my_groups.messaging_enabled' => true]);
        Mail::fake();
        Log::spy();

        [$sender, $mapPk, $recipient] = $this->senderAndRecipient();
        $audited = $this->auditRows($sender);

        for ($i = 1; $i <= 5; $i++) {
            $this->send($sender, $mapPk, $recipient)->assertOk();
        }
        $this->send($sender, $mapPk, $recipient)->assertStatus(429);

        $this->assertSame($audited + 5, $this->auditRows($sender), 'one audit row per send');
        $row = DB::table('my_group_message_log')->where('sender_user_pk', $sender->pk)->orderByDesc('pk')->first();
        $this->assertSame($mapPk, (int) $row->group_map_pk);
        $this->assertSame('email', $row->channel);
        $this->assertSame((int) $sender->user_id, (int) $row->sender_student_pk);

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context = []) => $message === 'my_groups.message'
                && $context['user_pk'] === $sender->pk
                && ! array_key_exists('message', $context))
            ->times(5);
    }

    public function test_the_message_names_its_sender(): void
    {
        config(['my_groups.messaging_enabled' => true]);
        [$sender, $mapPk, $recipient] = $this->senderAndRecipient();

        $sent = [];
        $email = Mockery::mock(EmailService::class);
        $email->shouldReceive('sendBulk')->once()->andReturnUsing(function ($to, $text) use (&$sent) {
            $sent[] = $text;

            return [];
        });
        $this->app->instance(EmailService::class, $email);

        $this->send($sender, $mapPk, $recipient)->assertOk();

        $this->assertStringStartsWith('Message from ', $sent[0]);
        $this->assertStringContainsString('via Sargam My Groups', $sent[0]);
        $this->assertStringEndsWith('Meet at 5', $sent[0]);
    }

    /**
     * The bypass the review executed: a hit on an unnamed 1-minute throttle gave the
     * shared per-user key a 1-minute lifetime, so waiting a minute reset the hourly
     * message count. The named limiter keeps its own key for the full hour.
     */
    public function test_interleaving_another_throttled_route_cannot_raise_the_allowance(): void
    {
        config(['my_groups.messaging_enabled' => true]);
        Mail::fake();
        [$sender, $mapPk, $recipient] = $this->senderAndRecipient();

        $accepted = 0;
        for ($round = 0; $round < 4; $round++) {
            // An unnamed throttle:120,1 route, hit first in each round.
            $this->as($sender, ['Student-OT'])->get('/course-repository/document/0/download');

            for ($i = 0; $i < 3; $i++) {
                if ($this->send($sender, $mapPk, $recipient)->getStatusCode() === 200) {
                    $accepted++;
                }
            }

            $this->travel(61)->seconds();
        }

        $this->assertSame(5, $accepted, '12 attempts across four minutes; the hourly allowance is 5');
    }
}
