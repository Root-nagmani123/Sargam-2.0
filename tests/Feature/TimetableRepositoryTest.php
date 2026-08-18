<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\TimetableRepositoryController;
use App\Models\CourseMaster;
use App\Models\TimetableRepositoryDocument;
use App\Models\User;
use App\Rules\SafeUploadedDocument;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cover for the Timetable Repository screen (Setup → Time Table): the access
 * gate, the listing tabs, the week list derived from the course, and the PDF
 * upload rules (PDF only, 5 MB ceiling).
 *
 * DatabaseTransactions — NOT RefreshDatabase: phpunit.xml leaves DB_CONNECTION
 * commented out, so the suite runs against the real database.
 */
class TimetableRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private function privilegedActor(): User
    {
        $user = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))->first();

        if (! $user) {
            $this->markTestSkipped('No Super Admin user to authenticate as.');
        }

        return $user;
    }

    private function unprivilegedActor(): User
    {
        $user = User::query()->whereDoesntHave('roles')->whereDoesntHave('permissions')->first();

        if (! $user) {
            $this->markTestSkipped('No role-less user to test the gate with.');
        }

        return $user;
    }

    private function course(): CourseMaster
    {
        $course = CourseMaster::query()->whereNotNull('start_year')->whereNotNull('end_date')->first();

        if (! $course) {
            $this->markTestSkipped('No course with start/end dates to file documents against.');
        }

        return $course;
    }

    private function firstWeekOf(CourseMaster $course): string
    {
        return Carbon::parse($course->start_year)->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    /**
     * A real file on disk — SafeUploadedDocument reads magic bytes, so
     * UploadedFile::fake()'s empty placeholder would be rejected as unreadable.
     */
    private function upload(string $name, string $bytes): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ttr');
        file_put_contents($tmp, $bytes);

        // test mode = true: skip is_uploaded_file(), keep the real error code path.
        return new UploadedFile($tmp, $name, null, null, true);
    }

    private function pdfBytes(int $padToBytes = 0): string
    {
        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";

        return $padToBytes > strlen($pdf)
            ? $pdf . str_repeat("\n", $padToBytes - strlen($pdf))
            : $pdf;
    }

    public function test_a_user_without_the_permission_cannot_open_the_screen(): void
    {
        $this->actingAs($this->unprivilegedActor())
            ->get(route('timetable-repository.index'))
            ->assertForbidden();
    }

    public function test_both_tabs_render(): void
    {
        $actor = $this->privilegedActor();

        $this->actingAs($actor)->get(route('timetable-repository.index'))->assertOk();
        $this->actingAs($actor)
            ->get(route('timetable-repository.index', ['status' => 'archive']))
            ->assertOk()
            ->assertSee('Timetable Repository');
    }

    public function test_the_upload_form_renders(): void
    {
        $this->actingAs($this->privilegedActor())
            ->get(route('timetable-repository.create'))
            ->assertOk()
            ->assertSee('PDF Upload')
            // The hint shows the effective ceiling, which php.ini can pull below 5 MB.
            ->assertSee('maximum size ' . SafeUploadedDocument::maxLabel(TimetableRepositoryController::MAX_FILE_KB));
    }

    public function test_the_ceiling_never_exceeds_five_mb(): void
    {
        $this->assertLessThanOrEqual(
            5120,
            SafeUploadedDocument::maxKilobytes(TimetableRepositoryController::MAX_FILE_KB),
            'The Timetable Repository must never accept a PDF larger than 5 MB.'
        );
    }

    public function test_weeks_are_numbered_from_the_course_start(): void
    {
        $course = $this->course();

        $response = $this->actingAs($this->privilegedActor())
            ->getJson(route('timetable-repository.weeks', ['course_master_pk' => $course->pk]))
            ->assertOk();

        $weeks = $response->json('weeks');

        $this->assertNotEmpty($weeks, 'A course with start/end dates must produce at least one week.');
        $this->assertSame(1, $weeks[0]['number']);
        $this->assertSame($this->firstWeekOf($course), $weeks[0]['value']);
        $this->assertStringStartsWith('Week 1 (', $weeks[0]['label']);
    }

    public function test_a_pdf_is_uploaded_and_listed_against_its_course_and_week(): void
    {
        Storage::fake('public');

        $course = $this->course();
        $name   = 'ZZ Test Document ' . uniqid();

        $actor = $this->privilegedActor();

        // Follow the redirect: it must land on the tab the course belongs to,
        // with the new row visible there.
        $this->actingAs($actor)
            ->post(route('timetable-repository.store'), [
                'document_name'    => $name,
                'course_master_pk' => $course->pk,
                'week_start'       => $this->firstWeekOf($course),
                'document_file'    => $this->upload('session-plan.pdf', $this->pdfBytes()),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($actor)
            ->get(route('timetable-repository.index', ['status' => $course->end_date >= now()->toDateString() ? 'active' : 'archive']))
            ->assertOk()
            ->assertSee($name)
            ->assertSee('Week 1');

        $document = TimetableRepositoryDocument::where('document_name', $name)->first();

        $this->assertNotNull($document);
        $this->assertSame((int) $course->pk, (int) $document->course_master_pk);
        $this->assertSame($this->firstWeekOf($course), $document->week_start->toDateString());
        $this->assertSame(1, $document->week_number);
        Storage::disk('public')->assertExists($document->file_path);
    }

    public function test_a_document_can_be_edited_downloaded_and_deleted(): void
    {
        Storage::fake('public');

        $course = $this->course();
        $actor  = $this->privilegedActor();
        $name   = 'ZZ Round Trip ' . uniqid();

        $this->actingAs($actor)->post(route('timetable-repository.store'), [
            'document_name'    => $name,
            'course_master_pk' => $course->pk,
            'week_start'       => $this->firstWeekOf($course),
            'document_file'    => $this->upload('session-plan.pdf', $this->pdfBytes()),
        ])->assertRedirect();

        $document = TimetableRepositoryDocument::where('document_name', $name)->firstOrFail();
        $oldPath  = $document->file_path;

        $this->actingAs($actor)->get(route('timetable-repository.edit', $document->pk))->assertOk();

        $this->actingAs($actor)->get(route('timetable-repository.download', $document->pk))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=session-plan.pdf');

        // Replacing the PDF must drop the previous file rather than orphan it.
        $this->actingAs($actor)->put(route('timetable-repository.update', $document->pk), [
            'document_name'    => $name . ' (updated)',
            'course_master_pk' => $course->pk,
            'week_start'       => $this->firstWeekOf($course),
            'document_file'    => $this->upload('revised-plan.pdf', $this->pdfBytes()),
        ])->assertRedirect();

        $document->refresh();
        $this->assertSame($name . ' (updated)', $document->document_name);
        $this->assertSame('revised-plan.pdf', $document->file_name);
        $this->assertNotSame($oldPath, $document->file_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($document->file_path);

        $newPath = $document->file_path;

        $this->actingAs($actor)->delete(route('timetable-repository.destroy', $document->pk))->assertRedirect();

        $this->assertNull(TimetableRepositoryDocument::find($document->pk));
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_a_pdf_over_the_size_limit_is_rejected(): void
    {
        Storage::fake('public');

        $course = $this->course();

        $this->actingAs($this->privilegedActor())
            ->post(route('timetable-repository.store'), [
                'document_name'    => 'ZZ Too Big ' . uniqid(),
                'course_master_pk' => $course->pk,
                'week_start'       => $this->firstWeekOf($course),
                // 5 MB + 1 KB — past the requirement's ceiling, and past any lower
                // one php.ini imposes.
                'document_file'    => $this->upload('big.pdf', $this->pdfBytes(5121 * 1024)),
            ])
            ->assertSessionHasErrors('document_file');
    }

    public function test_a_non_pdf_is_rejected(): void
    {
        Storage::fake('public');

        $course = $this->course();

        $this->actingAs($this->privilegedActor())
            ->post(route('timetable-repository.store'), [
                'document_name'    => 'ZZ Not A Pdf ' . uniqid(),
                'course_master_pk' => $course->pk,
                'week_start'       => $this->firstWeekOf($course),
                'document_file'    => $this->upload('notes.docx', 'not a pdf at all'),
            ])
            ->assertSessionHasErrors('document_file');
    }

    public function test_a_week_outside_the_course_is_rejected(): void
    {
        Storage::fake('public');

        $course = $this->course();

        $this->actingAs($this->privilegedActor())
            ->post(route('timetable-repository.store'), [
                'document_name'    => 'ZZ Wrong Week ' . uniqid(),
                'course_master_pk' => $course->pk,
                // Ten years before the course starts — never in its week list.
                'week_start'       => Carbon::parse($course->start_year)->subYears(10)->startOfWeek(Carbon::MONDAY)->toDateString(),
                'document_file'    => $this->upload('session-plan.pdf', $this->pdfBytes()),
            ])
            ->assertSessionHasErrors('week_start');
    }
}
