<?php

namespace Tests\Feature;

use App\Support\CourseRepositorySearch;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * One upload can carry several attachments, each its own document row under one
 * session (detail). A search hit is one document, but its link was keyed by the
 * session, and the viewer then showed the session's first document - deleted or
 * not (PR #334 F-053).
 */
class CourseRepositorySearchOpensListedDocumentTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function forgetFolderCaches(): void
    {
        foreach (['folderTree', 'folderChildren', 'hiddenFolderPks'] as $property) {
            if (property_exists(CourseRepositorySearch::class, $property)) {
                $p = new \ReflectionProperty(CourseRepositorySearch::class, $property);
                $p->setAccessible(true);
                $p->setValue(null, null);
            }
        }
    }

    /** @return array{detail:int, alpha:int, bravo:int, token:string} */
    private function sessionWithTwoAttachments(): array
    {
        $folder = DB::table('course_repository_master')->where('del_folder_status', 1)->orderBy('pk')->value('pk');
        if (! $folder) {
            $this->markTestSkipped('no live repository folder');
        }

        $token = 'Zqattach'.random_int(100000, 999999);
        $detail = (int) DB::table('course_repository_details')->insertGetId([
            'course_repository_master_pk' => $folder,
            'topic_pk' => $token,
            'status' => 1,
            'created_date' => now(),
        ]);
        $doc = fn (string $name) => (int) DB::table('course_repository_documents')->insertGetId([
            'course_repository_details_pk' => $detail,
            'upload_document' => $name.'.pdf',
            'file_title' => $name.' '.$token,
            'del_type' => 1,
        ]);

        return ['detail' => $detail, 'alpha' => $doc('alpha'), 'bravo' => $doc('bravo'), 'token' => $token];
    }

    /** The view link of the search hit whose title contains $needle. */
    private function searchLinkFor(string $q, string $needle): string
    {
        $this->forgetFolderCaches();
        $html = $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->get(route('admin.course-repository.user.search', ['q' => $q]))
            ->assertOk()
            ->getContent();

        preg_match_all('/<a href="([^"]+)" class="cru-sr-title-link">(.*?)<\/a>/s', $html, $m, PREG_SET_ORDER);
        foreach ($m as [, $href, $title]) {
            if (str_contains(strip_tags($title), $needle)) {
                return html_entity_decode($href);
            }
        }
        $this->fail("no search hit titled '{$needle}'");
    }

    private function shownDocumentPk(string $url): ?int
    {
        $response = $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])->get($url);
        if ($response->isRedirect()) {
            return null;
        }
        $response->assertOk();

        return (int) $response->viewData('pdfDocument')->pk;
    }

    public function test_each_search_hit_opens_the_document_it_lists(): void
    {
        $s = $this->sessionWithTwoAttachments();

        $this->assertSame($s['bravo'], $this->shownDocumentPk($this->searchLinkFor($s['token'], 'bravo')), 'the bravo hit opens bravo');
        $this->assertSame($s['alpha'], $this->shownDocumentPk($this->searchLinkFor($s['token'], 'alpha')), 'the alpha hit opens alpha');
    }

    public function test_a_deleted_attachment_is_never_shown(): void
    {
        $s = $this->sessionWithTwoAttachments();
        DB::table('course_repository_documents')->where('pk', $s['alpha'])->update(['del_type' => 0]);

        $sessionUrl = route('admin.course-repository.user.document-view', $s['detail']);
        $this->assertSame($s['bravo'], $this->shownDocumentPk($sessionUrl), 'the session view skips the deleted first attachment');
        $this->assertNull($this->shownDocumentPk($sessionUrl.'?doc='.$s['alpha']), 'the deleted attachment cannot be asked for');
    }

    public function test_the_folder_page_links_each_attachment_row_to_that_attachment(): void
    {
        $s = $this->sessionWithTwoAttachments();
        $folder = DB::table('course_repository_details')->where('pk', $s['detail'])->value('course_repository_master_pk');

        $html = $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->get(route('admin.course-repository.user.show', $folder))
            ->assertOk()
            ->getContent();

        foreach (['alpha', 'bravo'] as $name) {
            $this->assertStringContainsString(
                e(route('admin.course-repository.user.document-view', ['documentId' => $s['detail'], 'doc' => $s[$name]])),
                $html,
                "{$name}'s row links to {$name}"
            );
        }
    }

    public function test_a_document_of_another_session_cannot_be_shown_through_this_one(): void
    {
        $a = $this->sessionWithTwoAttachments();
        $b = $this->sessionWithTwoAttachments();

        $this->assertNull($this->shownDocumentPk(route('admin.course-repository.user.document-view', $a['detail']).'?doc='.$b['bravo']));
    }
}
