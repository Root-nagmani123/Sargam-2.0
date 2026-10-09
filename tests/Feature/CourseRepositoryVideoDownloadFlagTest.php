<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * The "allow video download" switch is shown only on the Course and Other forms.
 * The server read an absent flag as "off", so editing a document from a form
 * without the switch (Institutional) silently turned download off (PR #334
 * F-017). Forms with the switch now always post 1 or 0, and an absent flag keeps
 * the stored choice.
 */
class CourseRepositoryVideoDownloadFlagTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** @return array{0: int, 1: int} a document pk and its detail pk, with the flag set to $flag */
    private function documentWithFlag(int $flag): array
    {
        $row = DB::table('course_repository_documents as doc')
            ->join('course_repository_details as dt', 'dt.pk', '=', 'doc.course_repository_details_pk')
            ->where('doc.del_type', 1)
            ->orderBy('doc.pk')
            ->first(['doc.pk as doc_pk', 'dt.pk as detail_pk']);

        if (! $row) {
            $this->markTestSkipped('no document with a detail row');
        }

        DB::table('course_repository_details')->where('pk', $row->detail_pk)->update(['video_download_enabled' => $flag]);

        return [(int) $row->doc_pk, (int) $row->detail_pk];
    }

    private function update(int $docPk, array $fields)
    {
        // A staff Super Admin: repository writes refuse trainee-category logins
        // whatever they hold (PR #334 F-041), and on the development copy the
        // lowest-pk Super Admin is one.
        return $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->post(route('course-repository.document.update', $docPk), $fields);
    }

    private function flag(int $detailPk): int
    {
        return (int) DB::table('course_repository_details')->where('pk', $detailPk)->value('video_download_enabled');
    }

    public function test_an_edit_without_the_switch_keeps_download_on(): void
    {
        [$doc, $detail] = $this->documentWithFlag(1);

        $this->update($doc, ['category' => 'Institutional']);

        $this->assertSame(1, $this->flag($detail));
    }

    public function test_an_edit_posting_zero_turns_download_off(): void
    {
        [$doc, $detail] = $this->documentWithFlag(1);

        $this->update($doc, ['category' => 'Course', 'video_download_enabled' => '0']);

        $this->assertSame(0, $this->flag($detail));
    }

    public function test_an_edit_posting_one_turns_download_on(): void
    {
        [$doc, $detail] = $this->documentWithFlag(0);

        $this->update($doc, ['category' => 'Course', 'video_download_enabled' => '1']);

        $this->assertSame(1, $this->flag($detail));
    }
}
