<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * My Groups lets an Officer Trainee send SMS / email to group mates through the
 * institutional gateway. Each send is audited and an account may send at most
 * five an hour (PR #334 F-011, product decision 2026-10-06: keep the feature,
 * throttled and audited).
 */
class MyGroupsMessageTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

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

    public function test_each_send_is_audited_and_the_sixth_in_an_hour_is_refused(): void
    {
        // A fresh in-memory limiter, so this test neither reads nor writes the
        // application's shared cache.
        config(['cache.limiter' => 'array']);
        Mail::fake();
        Log::spy();

        [$sender, $mapPk, $recipient] = $this->senderAndRecipient();
        $url = "/dashboard/my-groups/{$mapPk}/students/message";
        $payload = ['channel' => 'email', 'message' => 'Meet at 5', 'student_ids' => [$recipient]];

        for ($i = 1; $i <= 5; $i++) {
            $this->as($sender, ['Student-OT'])->postJson($url, $payload)->assertOk();
        }

        $this->as($sender, ['Student-OT'])->postJson($url, $payload)->assertStatus(429);

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context = []) => $message === 'my_groups.message'
                && $context['user_pk'] === $sender->pk
                && $context['group_map_pk'] === $mapPk
                && $context['channel'] === 'email'
                && ! array_key_exists('message', $context))
            ->times(5);
    }
}
