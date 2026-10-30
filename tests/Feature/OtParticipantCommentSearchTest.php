<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\OtParticipantComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * The comment / feedback history table searches the COMMENT rows: author, message,
 * notify flag and date. The merge 7082e5204 left the participants-list search body
 * here, which closes over an undefined $rowMeta, so any search term threw
 * "Undefined variable $rowMeta" and the page returned 500 (PR #334 F-019).
 */
class OtParticipantCommentSearchTest extends TestCase
{
    // The trait's setUp() opens the rolled-back transaction; a class-level setUp()
    // would silently replace it and commit the rows below.
    use RollsBackAgainstAppDatabase {
        setUp as rollsBackSetUp;
    }

    private int $studentPk;

    protected function setUp(): void
    {
        $this->rollsBackSetUp();

        $studentPk = DB::table('student_master')->orderBy('pk')->value('pk');
        if (! $studentPk) {
            $this->markTestSkipped('no student_master row');
        }
        $this->studentPk = (int) $studentPk;

        foreach ([['Rahul Verma', 'Punctual and attentive', 1], ['Anita Rao', 'Missed the field visit', 0]] as [$by, $msg, $notify]) {
            $comment = new OtParticipantComment([
                'student_master_pk' => $this->studentPk,
                'message' => $msg,
                'notify_ot' => $notify,
                'comment_date' => '2026-10-01',
            ]);
            $comment->comment_by_name = $by;
            $comment->active_inactive = 1;
            $comment->save();
        }
    }

    private function rows(string $search)
    {
        $method = new \ReflectionMethod(UserController::class, 'otParticipantCommentRows');
        $method->setAccessible(true);

        $request = Request::create('/', 'GET', ['search' => ['value' => $search]]);

        return $method->invoke(app(UserController::class), $request, $this->studentPk);
    }

    public function test_a_search_term_does_not_throw_and_matches_the_message(): void
    {
        $messages = $this->rows('field visit')->pluck('message')->all();

        $this->assertContains('Missed the field visit', $messages);
        $this->assertNotContains('Punctual and attentive', $messages);
    }

    public function test_a_search_matches_the_comment_author(): void
    {
        $authors = $this->rows('rahul')->pluck('comment_by_name')->all();

        $this->assertSame(['Rahul Verma'], array_values(array_unique($authors)));
    }

    public function test_a_search_matches_the_notify_flag_and_the_date(): void
    {
        $this->assertContains('Missed the field visit', $this->rows('no')->pluck('message')->all());
        $this->assertCount(2, $this->rows('01 oct 2026')->filter(
            fn ($r) => in_array($r->message, ['Punctual and attentive', 'Missed the field visit'], true)
        ));
    }
}
