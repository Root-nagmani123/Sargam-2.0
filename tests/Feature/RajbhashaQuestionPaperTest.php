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
use App\Services\COE\RajbhashaPaperService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * COE → My Question Paper (Rajbhasha) and the faculty's verification:
 * an uploaded paper is translated, sent for approval, and the faculty freezes
 * it (OTP) to publish. Hits the real routes.
 */
class RajbhashaQuestionPaperTest extends TestCase
{
    use DatabaseTransactions;

    private User $faculty;

    private User $rajbhasha;

    private User $outsider;

    private ExaminationDriveFacultyMap $map;

    private QuestionPaper $paper;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(QuestionPaper::DISK);

        $this->faculty = User::role('Faculty')->get()
            ->first(fn (User $u) => FacultyMaster::where('employee_master_pk', $u->user_id)->exists())
            ?? $this->markTestSkipped('No Faculty-role user linked to faculty_master.');

        $nonFaculty = User::whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['Faculty', 'Internal Faculty', 'Guest Faculty', 'CC', 'ACC', 'Super Admin']))
            ->where('user_category', '!=', 'F')
            ->limit(2)
            ->get();
        if ($nonFaculty->count() < 2) {
            $this->markTestSkipped('Need two non-faculty users.');
        }
        // Rajbhasha is whoever holds the permission; given directly here, rolled back with the test.
        [$this->rajbhasha, $this->outsider] = [$nonFaculty[0], $nonFaculty[1]];
        $this->rajbhasha->givePermissionTo(RajbhashaPaperService::PERMISSION);
        if ($this->outsider->can(RajbhashaPaperService::PERMISSION)) {
            $this->markTestSkipped('The second non-faculty user already has the Rajbhasha permission.');
        }

        $typePk = ExaminationTypeMaster::value('pk');
        $termPk = TermMaster::value('pk');
        $coursePk = CourseMaster::value('pk');
        $subjectPk = SubjectMaster::where('active_inactive', 1)->value('pk');
        if (! $typePk || ! $termPk || ! $coursePk || ! $subjectPk) {
            $this->markTestSkipped('Exam type / term / course / subject master rows are missing.');
        }

        $drive = ExaminationDrive::create([
            'drive_name' => 'Test Rajbhasha Drive',
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
        FacultyMaster::where('pk', $facultyPk)->update(['mobile_no' => '9876543210']);

        $this->map = ExaminationDriveFacultyMap::create([
            'examination_drive_id' => $drive->id,
            'subject_master_pk' => $subjectPk,
            'faculty_master_pk' => $facultyPk,
            'created_date' => now(),
        ]);

        // A paper the faculty has uploaded: it reaches Rajbhasha straight away.
        $code = sprintf('QP-%d-%03d', $drive->id, $subjectPk);
        Storage::disk(QuestionPaper::DISK)->put('coe/question-papers/'.$drive->id.'/orig.docx', 'original paper');
        $this->paper = QuestionPaper::create([
            'examination_drive_id' => $drive->id,
            'subject_master_pk' => $subjectPk,
            'faculty_master_pk' => $facultyPk,
            'paper_code' => $code,
            'paper_name' => 'Test Subject',
            'deadline' => '2026-12-01',
            'original_file_path' => 'coe/question-papers/'.$drive->id.'/orig.docx',
            'original_file_name' => $code.'_v1.docx',
            'version' => 1,
            'status' => QuestionPaper::STATUS_PENDING_RAJBHASHA,
        ]);
    }

    public function test_only_rajbhasha_gets_in(): void
    {
        $this->actingAs($this->outsider)->get(route('admin.coe.my-question-paper.index'))->assertForbidden();
        $this->actingAs($this->faculty)->get(route('admin.coe.my-question-paper.index'))->assertForbidden();
        $this->actingAs($this->outsider)
            ->postJson(route('admin.coe.my-question-paper.translation.store', $this->paper->id), ['translated_paper' => $this->docx()])
            ->assertForbidden();
        $this->actingAs($this->outsider)->get(route('admin.coe.my-question-paper.original', $this->paper->id))->assertForbidden();

        $this->actingAs($this->rajbhasha)
            ->get(route('admin.coe.my-question-paper.index'))
            ->assertOk()
            ->assertSee($this->paper->paper_code)
            ->assertSee('data-coe-state="pending"', false);
    }

    public function test_rajbhasha_downloads_the_original(): void
    {
        $response = $this->actingAs($this->rajbhasha)
            ->get(route('admin.coe.my-question-paper.original', $this->paper->id))
            ->assertOk();

        $this->assertStringContainsString($this->paper->original_file_name, $response->headers->get('Content-Disposition'));
        $this->assertSame('original paper', $response->streamedContent());
    }

    public function test_upload_then_delete_the_translation(): void
    {
        $this->rajbhashaAction('translation.store', ['translated_paper' => $this->docx()])
            ->assertOk()
            ->assertJson(['message' => 'Translated question paper uploaded.']);

        $paper = $this->paper->fresh();
        $this->assertSame(QuestionPaper::STATUS_TRANSLATED, $paper->status);
        $this->assertSame($paper->paper_code.'_hindi.docx', $paper->translated_file_name);
        $this->assertSame((int) $this->rajbhasha->pk, (int) $paper->translated_by);
        Storage::disk(QuestionPaper::DISK)->assertExists($paper->translated_file_path);

        // Not sent yet: the faculty still sees Pending at Rajbhasha, without the file.
        $this->actingAs($this->faculty)
            ->get(route('admin.coe.faculty-question-paper.index'))
            ->assertSee('data-coe-state="pending-rajbhasha"', false);
        $this->actingAs($this->faculty)
            ->get(route('admin.coe.faculty-question-paper.translated', $this->map->id))
            ->assertNotFound();

        $this->rajbhashaAction('translation.store', ['translated_paper' => $this->docx()])->assertStatus(422);

        $this->rajbhashaAction('translation.destroy')->assertOk();
        $paper = $this->paper->fresh();
        $this->assertSame(QuestionPaper::STATUS_PENDING_RAJBHASHA, $paper->status);
        $this->assertNull($paper->translated_file_path);
        $this->assertNull($paper->translated_by);
        $this->assertCount(1, Storage::disk(QuestionPaper::DISK)->allFiles());   // only the original is left
    }

    public function test_translation_must_be_a_docx(): void
    {
        $this->rajbhashaAction('translation.store', ['translated_paper' => UploadedFile::fake()->createWithContent('h.pdf', "%PDF-1.4\n")])
            ->assertStatus(422)
            ->assertJsonValidationErrors('translated_paper');

        $this->assertSame(QuestionPaper::STATUS_PENDING_RAJBHASHA, $this->paper->fresh()->status);
    }

    public function test_send_needs_a_translation(): void
    {
        $this->rajbhashaAction('send-for-approval')->assertStatus(422);
        $this->assertSame(QuestionPaper::STATUS_PENDING_RAJBHASHA, $this->paper->fresh()->status);
    }

    public function test_sent_translation_is_verified_and_published_by_the_faculty(): void
    {
        $this->rajbhashaAction('translation.store', ['translated_paper' => $this->docx()])->assertOk();
        $this->rajbhashaAction('send-for-approval')
            ->assertOk()
            ->assertJson(['message' => 'Translated question paper sent for approval.']);

        $paper = $this->paper->fresh();
        $this->assertSame(QuestionPaper::STATUS_SENT_FOR_APPROVAL, $paper->status);
        $this->assertNotNull($paper->sent_for_approval_at);

        // Sent: Rajbhasha can no longer change it.
        $this->rajbhashaAction('translation.destroy')->assertStatus(422);
        $this->rajbhashaAction('send-for-approval')->assertStatus(422);
        $this->actingAs($this->rajbhasha)->get(route('admin.coe.my-question-paper.index'))
            ->assertSee('data-coe-state="sent"', false);

        // The faculty sees it to verify, with the translation.
        $this->actingAs($this->faculty)
            ->get(route('admin.coe.faculty-question-paper.index'))
            ->assertSee('data-coe-state="translated"', false)
            ->assertSee('Pending Verification');
        $this->actingAs($this->faculty)
            ->get(route('admin.coe.faculty-question-paper.translated', $this->map->id))
            ->assertOk();

        $otp = $this->sendFacultyOtp();
        $this->actingAs($this->faculty)
            ->postJson(route('admin.coe.faculty-question-paper.freeze', $this->map->id), ['otp' => $otp])
            ->assertOk()
            ->assertJson(['message' => 'Question paper verified and published.']);

        $this->assertSame(QuestionPaper::STATUS_PUBLISHED, $this->paper->fresh()->status);
        $this->actingAs($this->faculty)->get(route('admin.coe.faculty-question-paper.index'))
            ->assertSee('data-coe-state="published"', false);
        $this->actingAs($this->rajbhasha)->get(route('admin.coe.my-question-paper.index'))
            ->assertSee('data-coe-state="published"', false);

        // Published is final.
        $this->actingAs($this->faculty)
            ->postJson(route('admin.coe.faculty-question-paper.freeze-otp', $this->map->id))
            ->assertStatus(422);
    }

    private function rajbhashaAction(string $name, array $data = [])
    {
        $url = route('admin.coe.my-question-paper.'.$name, $this->paper->id);

        return $name === 'translation.destroy'
            ? $this->actingAs($this->rajbhasha)->deleteJson($url)
            : $this->actingAs($this->rajbhasha)->postJson($url, $data);
    }

    /** Sends the faculty's freeze OTP and reads it back from the log (no SMS template in testing). */
    private function sendFacultyOtp(): string
    {
        $otp = null;
        Log::listen(function (MessageLogged $e) use (&$otp) {
            if (str_starts_with($e->message, 'COE freeze OTP')) {
                $otp = $e->context['otp'];
            }
        });

        $this->actingAs($this->faculty)
            ->postJson(route('admin.coe.faculty-question-paper.freeze-otp', $this->map->id))
            ->assertOk();

        return (string) $otp;
    }

    private function docx(): UploadedFile
    {
        $files = [
            '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>',
            'word/document.xml' => '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"/>',
        ];
        $local = '';
        $central = '';
        foreach ($files as $name => $data) {
            $crc = crc32($data);
            $size = strlen($data);
            $header = pack('vvvvvVVVvv', 20, 0, 0, 0, 0, $crc, $size, $size, strlen($name), 0);
            $central .= "PK\x01\x02".pack('v', 20).$header.pack('vvvVV', 0, 0, 0, 0, strlen($local)).$name;
            $local .= "PK\x03\x04".$header.$name.$data;
        }

        return UploadedFile::fake()->createWithContent('Hindi Paper.docx',
            $local.$central."PK\x05\x06".pack('vvvvVVv', 0, 0, count($files), count($files), strlen($central), strlen($local), 0));
    }
}
