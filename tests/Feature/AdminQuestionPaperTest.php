<?php

namespace Tests\Feature;

use App\Models\CourseMaster;
use App\Models\ExaminationDrive;
use App\Models\ExaminationDriveFacultyMap;
use App\Models\ExaminationTypeMaster;
use App\Models\FacultyMaster;
use App\Models\QuestionPaper;
use App\Models\SubjectMaster;
use App\Models\TermMaster;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * COE → Question Paper (administrator): every subject owed on a drive, where its
 * paper stands, the files the admin may view and print (never Rajbhasha's unsent
 * translation, never as a download), unfreeze, and delete.
 */
class AdminQuestionPaperTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSION = 'coe_question_paper';

    private User $admin;

    private User $outsider;

    private ExaminationDrive $drive;

    /** @var array<int, int> subject pks mapped on the drive */
    private array $subjects;

    private int $facultyPk;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(QuestionPaper::DISK);

        $users = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'))->limit(2)->get();
        $this->facultyPk = (int) FacultyMaster::where('active_inactive', 1)->value('pk');
        $this->subjects = SubjectMaster::where('active_inactive', 1)->limit(4)->pluck('pk')->all();
        $typePk = ExaminationTypeMaster::value('pk');
        $termPk = TermMaster::value('pk');
        $coursePk = CourseMaster::value('pk');
        if ($users->count() < 2 || ! $this->facultyPk || count($this->subjects) < 4 || ! $typePk || ! $termPk || ! $coursePk) {
            $this->markTestSkipped('Users / faculty / subjects / exam masters are missing.');
        }

        [$this->admin, $this->outsider] = [$users[0], $users[1]];
        $this->admin->givePermissionTo(self::PERMISSION);   // rolled back with the test
        if ($this->outsider->getAllPermissions()->contains('name', self::PERMISSION)) {
            $this->markTestSkipped('The second user already holds '.self::PERMISSION.'.');
        }

        $this->drive = ExaminationDrive::create([
            'drive_name' => 'Test Admin Drive',
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

        foreach ($this->subjects as $subjectPk) {
            ExaminationDriveFacultyMap::create([
                'examination_drive_id' => $this->drive->id,
                'subject_master_pk' => $subjectPk,
                'faculty_master_pk' => $this->facultyPk,
                'created_date' => now(),
            ]);
        }

        // Subject 0 owes a paper (no row); 1 is uploaded, with Rajbhasha; 2 is with
        // Rajbhasha, translated but not sent; 3 is published.
        $this->paper(1, QuestionPaper::STATUS_PENDING_RAJBHASHA);
        $this->paper(2, QuestionPaper::STATUS_TRANSLATED, true);
        $this->paper(3, QuestionPaper::STATUS_PUBLISHED, true);
    }

    public function test_needs_the_menu_permission(): void
    {
        $paper = $this->paperFor(3);

        $this->actingAs($this->outsider)->get(route('admin.coe.question-paper.index'))->assertForbidden();
        $this->actingAs($this->outsider)->getJson(route('admin.coe.question-paper.original', $paper->id))->assertForbidden();
        $this->actingAs($this->outsider)->postJson(route('admin.coe.question-paper.unfreeze', $paper->id))->assertForbidden();
        $this->actingAs($this->outsider)->deleteJson(route('admin.coe.question-paper.destroy', $paper->id))->assertForbidden();
        $this->assertSame(QuestionPaper::STATUS_PUBLISHED, $this->paperFor(3)->status);
    }

    public function test_lists_every_owed_subject_with_its_admin_status(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.coe.question-paper.index'))->assertOk()->getContent();

        // Uploaded goes straight to Rajbhasha; translated-not-sent is still with Rajbhasha.
        $this->assertSame('pending-faculty', $this->statusOf($html, 0));
        $this->assertSame('pending-rajbhasha', $this->statusOf($html, 1));
        $this->assertSame('pending-rajbhasha', $this->statusOf($html, 2));
        $this->assertSame('published', $this->statusOf($html, 3));
    }

    public function test_translated_tab_lists_only_papers_with_a_sent_translation(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.coe.question-paper.index', ['scope' => 'translated']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($this->code(3), $html);
        foreach ([0, 1, 2] as $i) {
            $this->assertStringNotContainsString($this->code($i), $html);
        }
    }

    public function test_admin_never_gets_rajbhashas_draft(): void
    {
        // Rajbhasha's unsent translation.
        $this->actingAs($this->admin)->getJson(route('admin.coe.question-paper.translated', $this->paperFor(2)->id))->assertNotFound();

        // An uploaded original, and a sent / published translation.
        $this->actingAs($this->admin)->getJson(route('admin.coe.question-paper.original', $this->paperFor(1)->id))->assertOk();
        $this->actingAs($this->admin)->getJson(route('admin.coe.question-paper.original', $this->paperFor(2)->id))->assertOk();
        $this->actingAs($this->admin)->getJson(route('admin.coe.question-paper.original', $this->paperFor(3)->id))->assertOk();
        $this->actingAs($this->admin)->getJson(route('admin.coe.question-paper.translated', $this->paperFor(3)->id))
            ->assertOk()
            ->assertJson(['format' => 'docx', 'content' => base64_encode('hindi 3')]);
    }

    public function test_papers_are_viewed_and_printed_never_downloaded(): void
    {
        $url = route('admin.coe.question-paper.original', $this->paperFor(3)->id);

        $response = $this->actingAs($this->admin)->getJson($url)->assertOk();
        $this->assertNull($response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        // Opening the address in the browser gets no file.
        $this->actingAs($this->admin)->get($url)->assertNotFound();

        // Unfreeze on the published row only; Print enabled there only; no Delete or Download.
        $html = $this->actingAs($this->admin)->get(route('admin.coe.question-paper.index'))->getContent();
        foreach ([0, 1, 2, 3] as $i) {
            $row = $this->rowOf($html, $i);
            $this->assertSame($i === 3, str_contains($row, 'coe-act--unfreeze'), "Unfreeze on row $i");
            $this->assertSame($i === 3 ? 2 : 0, substr_count($row, 'data-coe-print='), "Print on row $i");
            $this->assertStringNotContainsString('coe-act--del', $row);
            $this->assertStringNotContainsString('Download', $row);
        }
    }

    public function test_unfreeze_sends_a_published_paper_back_to_the_faculty(): void
    {
        $paper = $this->paperFor(3);
        $paper->update(['frozen_at' => now(), 'frozen_by' => $this->admin->pk]);

        // Not published: refused.
        $this->actingAs($this->admin)->postJson(route('admin.coe.question-paper.unfreeze', $this->paperFor(2)->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors('paper');
        $this->assertSame(QuestionPaper::STATUS_TRANSLATED, $this->paperFor(2)->status);

        $this->actingAs($this->admin)->postJson(route('admin.coe.question-paper.unfreeze', $paper->id))
            ->assertOk()
            ->assertJson(['message' => 'Question paper unfrozen. It is back with the faculty for verification.']);

        $paper = $this->paperFor(3);
        $this->assertSame(QuestionPaper::STATUS_SENT_FOR_APPROVAL, $paper->status);
        $this->assertNull($paper->frozen_at);
        $this->assertNull($paper->frozen_by);
        $this->assertNotNull($paper->translated_file_path);   // the faculty verifies the same translation

        $html = $this->actingAs($this->admin)->get(route('admin.coe.question-paper.index'))->getContent();
        $this->assertSame('pending-faculty', $this->statusOf($html, 3));
        $this->assertStringNotContainsString('coe-act--unfreeze', $this->rowOf($html, 3));

        // Only once.
        $this->actingAs($this->admin)->postJson(route('admin.coe.question-paper.unfreeze', $paper->id))->assertStatus(422);
    }

    public function test_delete_removes_the_paper_and_files_and_the_faculty_starts_over(): void
    {
        $paper = $this->paperFor(3);
        [$original, $translated] = [$paper->original_file_path, $paper->translated_file_path];

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.coe.question-paper.destroy', $paper->id))
            ->assertOk()
            ->assertJson(['message' => 'Question paper deleted.']);

        $this->assertNull($this->paperFor(3));
        $this->assertNotNull(QuestionPaper::withTrashed()->find($paper->id));   // the record stays
        Storage::disk(QuestionPaper::DISK)->assertMissing($original);
        Storage::disk(QuestionPaper::DISK)->assertMissing($translated);

        // Still listed, back with the faculty.
        $html = $this->actingAs($this->admin)->get(route('admin.coe.question-paper.index'))->getContent();
        $this->assertSame('pending-faculty', $this->statusOf($html, 3));

        $this->actingAs($this->admin)->deleteJson(route('admin.coe.question-paper.destroy', $paper->id))->assertNotFound();
    }

    private function paper(int $i, string $status, bool $translated = false): QuestionPaper
    {
        $dir = 'coe/question-papers/'.$this->drive->id;
        Storage::disk(QuestionPaper::DISK)->put("$dir/o$i.docx", "original $i");
        if ($translated) {
            Storage::disk(QuestionPaper::DISK)->put("$dir/t$i.docx", "hindi $i");
        }

        return QuestionPaper::create([
            'examination_drive_id' => $this->drive->id,
            'subject_master_pk' => $this->subjects[$i],
            'faculty_master_pk' => $this->facultyPk,
            'paper_code' => $this->code($i),
            'paper_name' => "Subject $i",
            'deadline' => '2026-12-01',
            'original_file_path' => "$dir/o$i.docx",
            'original_file_name' => $this->code($i).'_v1.docx',
            'translated_file_path' => $translated ? "$dir/t$i.docx" : null,
            'translated_file_name' => $translated ? $this->code($i).'_hindi.docx' : null,
            'version' => 1,
            'status' => $status,
        ]);
    }

    /** The badge key on the list row of subject $i (the page lists every drive, not just this one). */
    private function statusOf(string $html, int $i): ?string
    {
        return preg_match('/coe-state coe-state--([a-z-]+)/', $this->rowOf($html, $i), $m) ? $m[1] : null;
    }

    /** The list row of subject $i, as HTML. */
    private function rowOf(string $html, int $i): string
    {
        foreach (explode('</tr>', $html) as $row) {
            if (str_contains($row, '<td>'.$this->code($i).'</td>')) {
                return $row;
            }
        }

        return '';
    }

    private function paperFor(int $i): ?QuestionPaper
    {
        return QuestionPaper::where('examination_drive_id', $this->drive->id)
            ->where('subject_master_pk', $this->subjects[$i])
            ->first();
    }

    private function code(int $i): string
    {
        return sprintf('QP-%d-%03d', $this->drive->id, $this->subjects[$i]);
    }
}
