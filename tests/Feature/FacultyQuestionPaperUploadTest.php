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
use Illuminate\Http\UploadedFile;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * COE → My Question Paper (faculty): the list, Add Question Paper (upload a
 * .docx or write it in the editor → V1, straight to Rajbhasha - no freeze),
 * reupload / edit (next version) until Rajbhasha translates it, download, and
 * the OTP freeze that publishes a verified translation. Hits the
 * real routes; every rejection also checks that nothing reached the table or the disk.
 */
class FacultyQuestionPaperUploadTest extends TestCase
{
    use DatabaseTransactions;

    private User $faculty;

    private ExaminationDriveFacultyMap $map;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(QuestionPaper::DISK);

        $this->faculty = User::role('Faculty')->get()
            ->first(fn (User $u) => FacultyMaster::where('employee_master_pk', $u->user_id)->exists())
            ?? $this->markTestSkipped('No Faculty-role user linked to faculty_master.');

        $typePk = ExaminationTypeMaster::value('pk');
        $termPk = TermMaster::value('pk');
        $coursePk = CourseMaster::value('pk');
        $subjectPk = SubjectMaster::where('active_inactive', 1)->value('pk');
        if (! $typePk || ! $termPk || ! $coursePk || ! $subjectPk) {
            $this->markTestSkipped('Exam type / term / course / subject master rows are missing.');
        }

        $drive = ExaminationDrive::create([
            'drive_name' => 'Test Question Paper Drive',
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

        $facultyPk = FacultyMaster::where('employee_master_pk', $this->faculty->user_id)->value('pk');
        // The freeze OTP goes to this number (rolled back with the test).
        FacultyMaster::where('pk', $facultyPk)->update(['mobile_no' => '9876543210']);

        $this->map = ExaminationDriveFacultyMap::create([
            'examination_drive_id' => $drive->id,
            'subject_master_pk' => $subjectPk,
            'faculty_master_pk' => $facultyPk,
            'created_date' => now(),
        ]);
    }

    public function test_faculty_sees_their_mapped_paper_as_pending(): void
    {
        $this->actingAs($this->faculty)
            ->get(route('admin.coe.faculty-question-paper.index'))
            ->assertOk()
            ->assertSee($this->code())
            ->assertSee('Test Question Paper Drive');
    }

    public function test_docx_upload_is_stored_as_v1_and_goes_straight_to_rajbhasha(): void
    {
        $this->upload(['mode' => 'upload', 'question_paper' => $this->docx()])
            ->assertOk()
            ->assertJson(['message' => 'Question paper uploaded and sent for translation.']);

        $paper = $this->paper();
        $this->assertNotNull($paper);
        $this->assertSame(QuestionPaper::STATUS_PENDING_RAJBHASHA, $paper->status);
        $this->assertNull($paper->frozen_at);
        $this->assertSame(1, (int) $paper->version);
        $this->assertSame($this->code().'_v1.docx', $paper->original_file_name);
        Storage::disk(QuestionPaper::DISK)->assertExists($paper->original_file_path);

        $this->actingAs($this->faculty)
            ->get(route('admin.coe.faculty-question-paper.index'))
            ->assertSee('data-coe-state="pending-rajbhasha"', false)
            ->assertSee('Pending at Rajbhasha');
    }

    public function test_reupload_while_pending_at_rajbhasha_makes_the_next_version(): void
    {
        $this->upload(['mode' => 'upload', 'question_paper' => $this->docx()])->assertOk();
        $oldPath = $this->paper()->original_file_path;

        // "Reupload or Edit Original Paper" opens the same page.
        $this->actingAs($this->faculty)
            ->get(route('admin.coe.faculty-question-paper.create', $this->map->id))
            ->assertOk();

        $this->upload(['mode' => 'create', 'paper_html' => '<p>Q1 Revised.</p>'])
            ->assertOk()
            ->assertJson(['message' => 'Question paper updated (V2).']);

        $paper = $this->paper();
        $this->assertSame(2, (int) $paper->version);
        $this->assertSame(QuestionPaper::STATUS_PENDING_RAJBHASHA, $paper->status);
        $this->assertSame($this->code().'_v2.html', $paper->original_file_name);
        Storage::disk(QuestionPaper::DISK)->assertExists($paper->original_file_path);
        Storage::disk(QuestionPaper::DISK)->assertMissing($oldPath);
        $this->assertCount(1, Storage::disk(QuestionPaper::DISK)->allFiles());

        $this->upload(['mode' => 'upload', 'question_paper' => UploadedFile::fake()->createWithContent('p.pdf', "%PDF-1.4\n")])
            ->assertStatus(422)
            ->assertJsonValidationErrors('question_paper');
        $this->assertSame(2, (int) $this->paper()->version);
    }

    public function test_edit_starts_from_the_paper_written_in_the_editor(): void
    {
        $this->upload(['mode' => 'create', 'paper_html' => '<p>Q1 Explain the Constitution.</p>'])->assertOk();

        $this->actingAs($this->faculty)
            ->get(route('admin.coe.faculty-question-paper.create', $this->map->id))
            ->assertOk()
            ->assertSee('<p>Q1 Explain the Constitution.</p>', false);
    }

    public function test_paper_is_locked_once_rajbhasha_translates_it(): void
    {
        $this->upload(['mode' => 'upload', 'question_paper' => $this->docx()])->assertOk();
        $this->paper()->update(['status' => QuestionPaper::STATUS_TRANSLATED]);

        $this->actingAs($this->faculty)
            ->get(route('admin.coe.faculty-question-paper.create', $this->map->id))
            ->assertRedirect(route('admin.coe.faculty-question-paper.index'));
        $this->upload(['mode' => 'upload', 'question_paper' => $this->docx()])
            ->assertStatus(422)
            ->assertJsonValidationErrors('paper');
        $this->actingAs($this->faculty)
            ->get(route('admin.coe.faculty-question-paper.index'))
            ->assertDontSee(route('admin.coe.faculty-question-paper.create', $this->map->id));

        $this->assertSame(1, (int) $this->paper()->version);
        $this->assertCount(1, Storage::disk(QuestionPaper::DISK)->allFiles());
    }

    public function test_faculty_downloads_their_original_paper_as_an_attachment(): void
    {
        $url = route('admin.coe.faculty-question-paper.original', $this->map->id);

        // Nothing uploaded yet.
        $this->actingAs($this->faculty)->get($url)->assertNotFound();

        $this->upload(['mode' => 'create', 'paper_html' => '<p>Q1 Explain.</p>'])->assertOk();

        $this->actingAs($this->faculty)
            ->get(route('admin.coe.faculty-question-paper.index'))
            ->assertSee($url, false);

        $response = $this->actingAs($this->faculty)->get($url)->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString($this->code().'_v1.html', $response->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('Q1 Explain.', $response->streamedContent());
    }

    public function test_non_faculty_cannot_download_the_paper(): void
    {
        $this->upload(['mode' => 'create', 'paper_html' => '<p>Q1 Explain.</p>'])->assertOk();

        $this->actingAs($this->nonFaculty())
            ->get(route('admin.coe.faculty-question-paper.original', $this->map->id))
            ->assertForbidden();
    }

    public function test_an_uploaded_paper_cannot_be_frozen(): void
    {
        $this->upload(['mode' => 'upload', 'question_paper' => $this->docx()])->assertOk();

        // No freeze on upload: it is already with Rajbhasha.
        $this->action('freeze-otp')->assertStatus(422);
        $this->action('freeze', ['otp' => '12345'])->assertStatus(422);
        $this->assertSame(QuestionPaper::STATUS_PENDING_RAJBHASHA, $this->paper()->status);
        $this->assertNull($this->paper()->frozen_at);
    }

    public function test_freeze_with_the_right_otp_publishes_the_verified_translation(): void
    {
        $this->upload(['mode' => 'upload', 'question_paper' => $this->docx()])->assertOk();
        $this->paper()->update(['status' => QuestionPaper::STATUS_SENT_FOR_APPROVAL]);   // Rajbhasha sent it back

        $this->actingAs($this->faculty)
            ->get(route('admin.coe.faculty-question-paper.index'))
            ->assertSee('+91 ******3210');

        $otp = $this->sendOtp();
        $wrong = str_pad((string) (((int) $otp + 1) % 100000), 5, '0', STR_PAD_LEFT);

        $this->action('freeze', ['otp' => $wrong])->assertStatus(422)->assertJsonValidationErrors('otp');
        $this->assertSame(QuestionPaper::STATUS_SENT_FOR_APPROVAL, $this->paper()->status);

        $this->action('freeze', ['otp' => $otp])
            ->assertOk()
            ->assertJson(['message' => 'Question paper verified and published.']);

        $paper = $this->paper();
        $this->assertSame(QuestionPaper::STATUS_PUBLISHED, $paper->status);
        $this->assertNotNull($paper->frozen_at);
        $this->assertSame((int) $this->faculty->pk, (int) $paper->frozen_by);

        // Published is final: the OTP is spent and no new one is sent.
        $this->action('freeze', ['otp' => $otp])->assertStatus(422);
        $this->action('freeze-otp')->assertStatus(422);
        $this->assertSame(QuestionPaper::STATUS_PUBLISHED, $this->paper()->status);
    }

    public function test_freeze_without_an_otp_sent_is_refused(): void
    {
        $this->upload(['mode' => 'upload', 'question_paper' => $this->docx()])->assertOk();
        $this->paper()->update(['status' => QuestionPaper::STATUS_SENT_FOR_APPROVAL]);

        $this->action('freeze', ['otp' => '12345'])->assertStatus(422)->assertJsonValidationErrors('otp');
        $this->action('freeze', ['otp' => '12'])->assertStatus(422)->assertJsonValidationErrors('otp');
        $this->assertSame(QuestionPaper::STATUS_SENT_FOR_APPROVAL, $this->paper()->status);
    }

    public function test_non_faculty_cannot_freeze(): void
    {
        $this->upload(['mode' => 'upload', 'question_paper' => $this->docx()])->assertOk();
        $this->paper()->update(['status' => QuestionPaper::STATUS_SENT_FOR_APPROVAL]);
        $other = $this->nonFaculty();

        $this->actingAs($other)->postJson(route('admin.coe.faculty-question-paper.freeze-otp', $this->map->id))->assertForbidden();
        $this->actingAs($other)->postJson(route('admin.coe.faculty-question-paper.freeze', $this->map->id), ['otp' => '12345'])->assertForbidden();

        $this->assertSame(QuestionPaper::STATUS_SENT_FOR_APPROVAL, $this->paper()->status);
    }

    public function test_non_docx_uploads_are_rejected(): void
    {
        $files = [
            UploadedFile::fake()->createWithContent('paper.pdf', "%PDF-1.4\n%test"),
            UploadedFile::fake()->createWithContent('paper.zip', $this->zipBytes()),          // real zip, wrong name
            UploadedFile::fake()->createWithContent('paper.docx', 'plain text, not a Word file'),
        ];

        foreach ($files as $file) {
            $this->upload(['mode' => 'upload', 'question_paper' => $file])
                ->assertStatus(422)
                ->assertJsonValidationErrors('question_paper');
        }

        $this->upload(['mode' => 'upload'])->assertStatus(422)->assertJsonValidationErrors('question_paper');

        $this->assertNull($this->paper());
        $this->assertSame([], Storage::disk(QuestionPaper::DISK)->allFiles());
    }

    public function test_editor_paper_is_cleaned_and_stored_as_html(): void
    {
        $html = '<h2 style="text-align: center;" onclick="alert(1)">प्रश्न पत्र</h2>'
            .'<p>Q1 <strong>Explain</strong>.<script>alert(1)</script></p>'
            .'<a href="javascript:evil()">bad</a><iframe src="x"></iframe><img src="x" onerror="alert(1)">';

        $this->upload(['mode' => 'create', 'paper_html' => $html])->assertOk();

        $paper = $this->paper();
        $this->assertSame($this->code().'_v1.html', $paper->original_file_name);
        $stored = Storage::disk(QuestionPaper::DISK)->get($paper->original_file_path);

        $this->assertStringContainsString('प्रश्न पत्र', $stored);
        $this->assertStringContainsString('<h2 style="text-align: center;">', $stored);
        $this->assertStringContainsString('<strong>Explain</strong>', $stored);
        foreach (['<script', 'onclick', 'onerror', 'javascript:', '<iframe'] as $bad) {
            $this->assertStringNotContainsString($bad, $stored);
        }
    }

    public function test_blank_editor_paper_is_rejected(): void
    {
        $this->upload(['mode' => 'create', 'paper_html' => '<p>&nbsp;</p><script>x</script>'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('paper_html');

        $this->assertNull($this->paper());
    }

    public function test_non_faculty_cannot_open_or_upload_the_paper(): void
    {
        $other = $this->nonFaculty();

        $this->actingAs($other)
            ->get(route('admin.coe.faculty-question-paper.create', $this->map->id))
            ->assertForbidden();
        $this->actingAs($other)
            ->postJson(route('admin.coe.faculty-question-paper.upload', $this->map->id),
                ['mode' => 'upload', 'question_paper' => $this->docx()])
            ->assertForbidden();

        $this->assertNull($this->paper());
    }

    public function test_super_admin_passes_the_menu_gate_but_owns_no_papers(): void
    {
        $this->upload(['mode' => 'upload', 'question_paper' => $this->docx()])->assertOk();
        $admin = User::role('Super Admin')->get()->first(fn (User $u) => ! $u->hasRole('Faculty'))
            ?? $this->markTestSkipped('No Super Admin who is not also Faculty.');

        $this->actingAs($admin)
            ->get(route('admin.coe.faculty-question-paper.index'))
            ->assertOk()
            ->assertDontSee($this->code());
        $this->actingAs($admin)->get(route('admin.coe.faculty-question-paper.original', $this->map->id))->assertNotFound();
        $this->actingAs($admin)->postJson(route('admin.coe.faculty-question-paper.freeze-otp', $this->map->id))->assertNotFound();
        $this->assertNotNull($this->paper());
    }

    /** No faculty role and not Super Admin: the menu permission gate turns them away. */
    private function nonFaculty(): User
    {
        return User::whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['Faculty', 'Internal Faculty', 'Guest Faculty', 'CC', 'ACC', 'Super Admin']))
            ->where('user_category', '!=', 'F')
            ->first() ?? $this->markTestSkipped('No non-faculty user.');
    }

    /** freeze-otp | freeze, as the faculty. */
    private function action(string $name, array $data = [])
    {
        return $this->actingAs($this->faculty)
            ->postJson(route('admin.coe.faculty-question-paper.'.$name, $this->map->id), $data);
    }

    /** Sends the freeze OTP and reads it back from the log (no SMS template in testing). */
    private function sendOtp(): string
    {
        $otp = null;
        Log::listen(function (MessageLogged $e) use (&$otp) {
            if (str_starts_with($e->message, 'COE freeze OTP')) {
                $otp = $e->context['otp'];
            }
        });

        $this->action('freeze-otp')->assertOk()->assertJson(['message' => 'OTP sent.']);
        $this->assertMatchesRegularExpression('/^\d{5}$/', (string) $otp);

        return $otp;
    }

    private function upload(array $data)
    {
        return $this->actingAs($this->faculty)
            ->postJson(route('admin.coe.faculty-question-paper.upload', $this->map->id), $data);
    }

    private function paper(): ?QuestionPaper
    {
        return QuestionPaper::where('examination_drive_id', $this->map->examination_drive_id)
            ->where('subject_master_pk', $this->map->subject_master_pk)
            ->first();
    }

    private function code(): string
    {
        return sprintf('QP-%d-%03d', $this->map->examination_drive_id, $this->map->subject_master_pk);
    }

    private function docx(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('Question Paper.docx', $this->zipBytes([
            '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>',
            'word/document.xml' => '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"/>',
        ]));
    }

    /**
     * A real (stored, uncompressed) ZIP built by hand: the PHP CLI here has no
     * ZipArchive, and SafeUploadedDocument reads the actual bytes.
     *
     * @param  array<string, string>  $files
     */
    private function zipBytes(array $files = ['a.txt' => 'hello']): string
    {
        $local = '';
        $central = '';
        foreach ($files as $name => $data) {
            $crc = crc32($data);
            $size = strlen($data);
            $header = pack('vvvvvVVVvv', 20, 0, 0, 0, 0, $crc, $size, $size, strlen($name), 0);
            $central .= "PK\x01\x02".pack('v', 20).$header.pack('vvvVV', 0, 0, 0, 0, strlen($local)).$name;
            $local .= "PK\x03\x04".$header.$name.$data;
        }

        return $local.$central."PK\x05\x06".pack('vvvvVVv', 0, 0, count($files), count($files), strlen($central), strlen($local), 0);
    }
}
