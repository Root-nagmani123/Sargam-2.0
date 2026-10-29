<?php

namespace Tests\Feature;

use App\Models\CourseMaster;
use App\Models\ExaminationDrive;
use App\Models\ExaminationTypeMaster;
use App\Models\FacultyMaster;
use App\Models\QuestionPaper;
use App\Models\QuestionPaperLog;
use App\Models\SubjectMaster;
use App\Models\TermMaster;
use App\Models\User;
use App\Services\COE\QuestionPaperLogger;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * COE → Question Paper Management Logs: the administrator's views, prints and
 * unfreezes are logged with who, the screen role, IP and location, and the Logs
 * screen lists them behind its own menu permission.
 */
class QuestionPaperLogTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $outsider;

    private QuestionPaper $paper;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(QuestionPaper::DISK);

        $users = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'))->limit(2)->get();
        $facultyPk = (int) FacultyMaster::where('active_inactive', 1)->value('pk');
        $subjectPk = SubjectMaster::where('active_inactive', 1)->value('pk');
        $typePk = ExaminationTypeMaster::value('pk');
        $termPk = TermMaster::value('pk');
        $coursePk = CourseMaster::value('pk');
        if ($users->count() < 2 || ! $facultyPk || ! $subjectPk || ! $typePk || ! $termPk || ! $coursePk) {
            $this->markTestSkipped('Users / faculty / subject / exam masters are missing.');
        }

        [$this->admin, $this->outsider] = [$users[0], $users[1]];
        $this->admin->givePermissionTo(['coe_question_paper', 'coe_question_paper_logs']);   // rolled back with the test
        if ($this->outsider->getAllPermissions()->contains('name', 'coe_question_paper_logs')) {
            $this->markTestSkipped('The second user already holds coe_question_paper_logs.');
        }

        $drive = ExaminationDrive::create([
            'drive_name' => 'Test Log Drive',
            'examination_type_master_pk' => $typePk,
            'term_master_pk' => $termPk,
            'course_master_pk' => $coursePk,
            'phase' => '2026',
            'academic_session' => 2026,
            'status' => 1,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
            'created_date' => now(),
        ]);

        $dir = 'coe/question-papers/'.$drive->id;
        Storage::disk(QuestionPaper::DISK)->put("$dir/o.html", '<!DOCTYPE html><html><body><p>Q1 Explain.</p></body></html>');
        Storage::disk(QuestionPaper::DISK)->put("$dir/t.html", '<!DOCTYPE html><html><body><p>प्रश्न 1</p></body></html>');
        $this->paper = QuestionPaper::create([
            'examination_drive_id' => $drive->id,
            'subject_master_pk' => $subjectPk,
            'faculty_master_pk' => $facultyPk,
            'paper_code' => sprintf('QPL-%d-%03d', $drive->id, $subjectPk),
            'paper_name' => 'Log Subject',
            'deadline' => '2026-12-01',
            'original_file_path' => "$dir/o.html",
            'original_file_name' => 'o_v2.html',
            'translated_file_path' => "$dir/t.html",
            'translated_file_name' => 't_hindi.html',
            'version' => 2,
            'status' => QuestionPaper::STATUS_PUBLISHED,
        ]);
    }

    public function test_admin_view_print_and_unfreeze_are_logged(): void
    {
        $original = route('admin.coe.question-paper.original', $this->paper->id);

        $this->actingAs($this->admin)->getJson($original)->assertOk();
        $this->actingAs($this->admin)->getJson($original.'?print=1')->assertOk();
        $this->actingAs($this->admin)->getJson(route('admin.coe.question-paper.translated', $this->paper->id))->assertOk();
        $this->actingAs($this->admin)->postJson(route('admin.coe.question-paper.unfreeze', $this->paper->id))->assertOk();

        $logs = QuestionPaperLog::where('question_paper_id', $this->paper->id)->orderBy('id')->get();
        $this->assertSame(
            [['Exam Paper', 'Viewed'], ['Exam Paper', 'Printed'], ['Translation', 'Viewed'], ['Exam Paper', 'Unfrozen']],
            $logs->map(fn ($l) => [$l->module, $l->action])->all()
        );
        $first = $logs->first();
        $this->assertSame(QuestionPaperLogger::ROLE_COE, $first->role);
        $this->assertSame((int) $this->admin->getAuthIdentifier(), (int) $first->user_id);
        $this->assertSame('V2', $first->detail);
        $this->assertSame('127.0.0.1', $first->ip_address);
        $this->assertSame('Internal Network', QuestionPaperLogger::location($first->ip_address));

        // A failed request (not published any more) logs nothing.
        $this->actingAs($this->admin)->postJson(route('admin.coe.question-paper.unfreeze', $this->paper->id))->assertStatus(422);
        $this->assertSame(4, QuestionPaperLog::where('question_paper_id', $this->paper->id)->count());
    }

    public function test_logs_screen_lists_entries_behind_its_permission(): void
    {
        $this->actingAs($this->admin)->getJson(route('admin.coe.question-paper.original', $this->paper->id))->assertOk();
        $this->actingAs($this->admin)->getJson(route('admin.coe.question-paper.translated', $this->paper->id))->assertOk();

        $this->actingAs($this->outsider)->get(route('admin.coe.question-paper-logs.index'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('admin.coe.question-paper-logs.index'))
            ->assertOk()
            ->assertSee('Question Paper Management Logs')
            ->assertSee($this->paper->paper_code)
            ->assertSee('coe-log-badge--blue">Viewed', false)
            ->assertSee('Exam Paper')
            ->assertSee('Internal Network')
            ->assertSee('data-drive="Test Log Drive"', false);

        // Translated tab: the translation's entries only.
        $html = $this->actingAs($this->admin)
            ->get(route('admin.coe.question-paper-logs.index', ['scope' => 'translated']))
            ->assertOk()
            ->getContent();
        $rows = array_filter(explode('</tr>', $html), fn ($r) => str_contains($r, $this->paper->paper_code));
        $this->assertCount(1, $rows);
        $this->assertStringContainsString('Translation', reset($rows));
    }

    public function test_location_is_internal_or_outside_network(): void
    {
        $this->assertSame('Internal Network', QuestionPaperLogger::location('192.168.1.23'));
        $this->assertSame('Internal Network', QuestionPaperLogger::location('10.0.0.5'));
        $this->assertSame('Outside Network', QuestionPaperLogger::location('8.8.8.8'));
        $this->assertSame('-', QuestionPaperLogger::location(null));
    }
}
