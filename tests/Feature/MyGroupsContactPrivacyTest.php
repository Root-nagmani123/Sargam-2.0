<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Messaging\EmailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Mockery;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * My Groups roster and send (PR #334).
 *
 * F-040: the roster JSON, its Excel and its PDF gave every officer trainee the
 * personal email and mobile number of every member of their groups. They now carry
 * name and OT code only; sending still resolves addresses on the server.
 *
 * F-048: the audit row was written only after a synchronous send, so a send that
 * threw left no record; and the sent count was taken on the raw list although both
 * gateways de-duplicate, so two trainees sharing a number with no gateway configured
 * reported "sent to 1" and stored sent_count 1 when nothing left.
 *
 * Every write rolls back; no message leaves (gateways faked or unconfigured).
 */
class MyGroupsContactPrivacyTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as openRollbackTransaction;
    }

    private const EMAIL_A = 'pr334-f040-a@example.invalid';

    private const EMAIL_B = 'pr334-f040-b@example.invalid';

    private const MOBILE_A = '9000033401';

    private const MOBILE_B = '9000033402';

    protected function setUp(): void
    {
        $this->openRollbackTransaction();
        // The send route's limiter in memory, so no shared cache is touched.
        config(['cache.limiter' => 'array']);
    }

    /**
     * An OT login and an active group they share with two other members, whose
     * contact details are set to known probe values.
     *
     * @return array{0: User, 1: int, 2: int, 3: int} sender, group map pk, member A, member B
     */
    private function groupWithTwoMates(): array
    {
        $row = DB::table('user_credentials as u')
            ->join('student_course_group_map as mine', 'mine.student_master_pk', '=', 'u.user_id')
            ->join('group_type_master_course_master_map as g', 'g.pk', '=', 'mine.group_type_master_course_master_map_pk')
            ->where('u.user_category', 'S')
            ->where('mine.active_inactive', 1)
            ->where('g.active_inactive', 1)
            ->whereRaw('(SELECT COUNT(DISTINCT o.student_master_pk) FROM student_course_group_map o
                          WHERE o.group_type_master_course_master_map_pk = g.pk AND o.active_inactive = 1
                            AND o.student_master_pk <> mine.student_master_pk) >= 2')
            ->orderByDesc('g.pk')
            ->first(['u.pk as user_pk', 'u.user_id as student', 'g.pk as map_pk']);

        if (! $row) {
            $this->markTestSkipped('no OT in a group with two other members');
        }

        $mates = DB::table('student_course_group_map')
            ->where('group_type_master_course_master_map_pk', $row->map_pk)
            ->where('active_inactive', 1)
            ->where('student_master_pk', '!=', $row->student)
            ->distinct()->orderBy('student_master_pk')->limit(2)
            ->pluck('student_master_pk');

        DB::table('student_master')->where('pk', $mates[0])->update(['email' => self::EMAIL_A, 'contact_no' => self::MOBILE_A]);
        DB::table('student_master')->where('pk', $mates[1])->update(['email' => self::EMAIL_B, 'contact_no' => self::MOBILE_B]);

        return [User::findOrFail($row->user_pk), (int) $row->map_pk, (int) $mates[0], (int) $mates[1]];
    }

    private function assertNoContactDetails(string $where, string $text): void
    {
        foreach ([self::EMAIL_A, self::EMAIL_B, self::MOBILE_A, self::MOBILE_B] as $secret) {
            $this->assertStringNotContainsString($secret, $text, "{$where} carries a member's contact detail");
        }
    }

    /* ---------------- F-040 ---------------- */

    public function test_the_roster_json_has_no_email_or_mobile(): void
    {
        [$sender, $mapPk, $a] = $this->groupWithTwoMates();

        $response = $this->as($sender, ['Student-OT'])->getJson(route('admin.dashboard.my-groups.students', $mapPk))->assertOk();

        $member = collect($response->json('students'))->firstWhere('pk', $a);
        $this->assertNotNull($member, 'the member is still listed');
        $this->assertSame(['pk', 'name', 'ot_code'], array_keys($member));
        $this->assertNoContactDetails('roster JSON', $response->getContent());
    }

    public function test_the_roster_excel_has_no_email_or_mobile(): void
    {
        [$sender, $mapPk] = $this->groupWithTwoMates();

        $response = $this->as($sender, ['Student-OT'])->get(route('admin.dashboard.my-groups.students.export', $mapPk))->assertOk();

        $cells = [];
        foreach (IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet()->toArray() as $row) {
            $cells = array_merge($cells, array_map('strval', $row));
        }
        $text = implode("\n", $cells);

        $this->assertStringContainsString('OT Code', $text, 'it is the roster');
        $this->assertStringNotContainsString('Email', $text);
        $this->assertStringNotContainsString('Mobile', $text);
        $this->assertNoContactDetails('roster Excel', $text);
    }

    public function test_the_roster_pdf_has_no_email_or_mobile(): void
    {
        [$sender, $mapPk] = $this->groupWithTwoMates();

        // The PDF's text streams are compressed, so read what the view was given.
        $rendered = null;
        View::composer('admin.exports.table_pdf', function ($view) use (&$rendered) {
            $rendered = $view->getData();
        });

        $this->as($sender, ['Student-OT'])
            ->get(route('admin.dashboard.my-groups.students.export', ['mapPk' => $mapPk, 'format' => 'pdf']))
            ->assertOk();

        $this->assertNotNull($rendered, 'the PDF view rendered');
        $this->assertSame(['S. No.', 'Student Name', 'OT Code'], $rendered['headings']);
        $this->assertNoContactDetails('roster PDF', json_encode($rendered['rows']));
    }

    /** Control: membership is still enforced. */
    public function test_a_group_the_ot_is_not_in_is_still_refused(): void
    {
        [$sender] = $this->groupWithTwoMates();
        $other = DB::table('group_type_master_course_master_map')->where('active_inactive', 1)
            ->whereNotExists(fn ($q) => $q->from('student_course_group_map')
                ->whereColumn('group_type_master_course_master_map_pk', 'group_type_master_course_master_map.pk')
                ->where('student_master_pk', $sender->user_id))
            ->value('pk');

        $this->as($sender, ['Student-OT'])->getJson(route('admin.dashboard.my-groups.students', $other))->assertForbidden();
        $this->as($sender, ['Student-OT'])->get(route('admin.dashboard.my-groups.students.export', $other))->assertForbidden();
    }

    /** Control: sending still finds the addresses on the server. */
    public function test_sending_still_resolves_recipients_server_side(): void
    {
        config(['my_groups.messaging_enabled' => true]);
        [$sender, $mapPk, $a, $b] = $this->groupWithTwoMates();

        $to = null;
        $email = Mockery::mock(EmailService::class);
        $email->shouldReceive('sendBulk')->once()->andReturnUsing(function ($recipients) use (&$to) {
            $to = collect($recipients)->sort()->values()->all();

            return [];
        });
        $this->app->instance(EmailService::class, $email);

        $this->as($sender, ['Student-OT'])->postJson(route('admin.dashboard.my-groups.students.message', $mapPk), [
            'channel' => 'email', 'message' => 'Meet at 5', 'student_ids' => [$a, $b],
        ])->assertOk();

        $this->assertSame([self::EMAIL_A, self::EMAIL_B], $to);
    }

    /* ---------------- F-048 ---------------- */

    private function lastAudit(User $sender): ?object
    {
        return DB::table('my_group_message_log')->where('sender_user_pk', $sender->pk)->orderByDesc('pk')->first();
    }

    public function test_a_shared_number_with_no_gateway_is_one_recipient_and_none_sent(): void
    {
        config(['my_groups.messaging_enabled' => true]);
        config(['services.twilio.sid' => null, 'services.twilio.token' => null, 'services.twilio.from' => null]);
        [$sender, $mapPk, $a, $b] = $this->groupWithTwoMates();
        DB::table('student_master')->where('pk', $b)->update(['contact_no' => self::MOBILE_A]);
        $before = $this->lastAudit($sender)->pk ?? 0;

        $this->as($sender, ['Student-OT'])->postJson(route('admin.dashboard.my-groups.students.message', $mapPk), [
            'channel' => 'sms', 'message' => 'Meet at 5', 'student_ids' => [$a, $b],
        ])->assertStatus(500)->assertJson(['status' => 'error']);

        $row = $this->lastAudit($sender);
        $this->assertNotNull($row);
        $this->assertGreaterThan($before, $row->pk, 'a new audit row');
        $this->assertSame(1, (int) $row->recipient_count, 'one distinct number');
        $this->assertSame(0, (int) $row->sent_count, 'nothing left');
    }

    public function test_a_send_that_throws_still_leaves_its_audit_row(): void
    {
        config(['my_groups.messaging_enabled' => true]);
        [$sender, $mapPk, $a] = $this->groupWithTwoMates();
        $before = $this->lastAudit($sender)->pk ?? 0;

        $email = Mockery::mock(EmailService::class);
        $email->shouldReceive('sendBulk')->once()->andThrow(new \RuntimeException('gateway down'));
        $this->app->instance(EmailService::class, $email);

        $this->as($sender, ['Student-OT'])->postJson(route('admin.dashboard.my-groups.students.message', $mapPk), [
            'channel' => 'email', 'message' => 'Meet at 5', 'student_ids' => [$a],
        ])->assertStatus(500);

        $row = $this->lastAudit($sender);
        $this->assertNotNull($row, 'the send is on record');
        $this->assertGreaterThan($before, $row->pk);
        $this->assertSame('email', $row->channel);
        $this->assertSame(0, (int) $row->sent_count);
    }
}
