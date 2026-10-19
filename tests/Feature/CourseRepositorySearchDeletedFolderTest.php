<?php

namespace Tests\Feature;

use App\Support\CourseRepositorySearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Browsing never reaches a soft-deleted folder (del_folder_status <> 1) or
 * anything beneath one; universal search, its counts, facets and suggestions
 * must not either (PR #334 F-013).
 */
class CourseRepositorySearchDeletedFolderTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** The search class memoises the folder tree per request; tests share a process. */
    private function forgetFolderCaches(): void
    {
        foreach (['folderTree', 'folderChildren', 'hiddenFolderPks'] as $property) {
            if (! property_exists(CourseRepositorySearch::class, $property)) {
                continue;
            }
            $p = new \ReflectionProperty(CourseRepositorySearch::class, $property);
            $p->setAccessible(true);
            $p->setValue(null, null);
        }
    }

    /** @return object{doc: int, folder: int, title: string, parent: ?int} */
    private function liveDocument(bool $nested = false): object
    {
        $row = DB::table('course_repository_documents as doc')
            ->when($nested, fn ($q) => $q->where('m.parent_type', '>', 0))
            ->leftJoin('course_repository_details as dt', 'dt.pk', '=', 'doc.course_repository_details_pk')
            ->join('course_repository_master as m', 'm.pk', '=', DB::raw('coalesce(dt.course_repository_master_pk, doc.course_repository_master_pk)'))
            ->where('doc.del_type', 1)
            ->where('m.del_folder_status', 1)
            ->whereNotNull('doc.file_title')
            ->whereRaw("CHAR_LENGTH(TRIM(doc.file_title)) >= 4")
            ->orderByDesc('doc.pk')
            ->first(['doc.pk as doc', 'm.pk as folder', 'doc.file_title as title', 'm.parent_type as parent']);

        if (! $row) {
            $this->markTestSkipped('no live document with a title');
        }

        return $row;
    }

    private function found(int $docPk, string $title): bool
    {
        $this->forgetFolderCaches();
        $criteria = CourseRepositorySearch::criteria(Request::create('/', 'GET', ['q' => $title]));

        return CourseRepositorySearch::documentQuery($criteria)->where('doc.pk', $docPk)->exists();
    }

    public function test_a_document_disappears_from_search_when_its_folder_is_deleted(): void
    {
        $d = $this->liveDocument();
        $this->assertTrue($this->found($d->doc, $d->title), 'sanity: the live document is found');

        DB::table('course_repository_master')->where('pk', $d->folder)->update(['del_folder_status' => 0]);

        $this->assertFalse($this->found($d->doc, $d->title), 'a document in a deleted folder must not be found');
    }

    public function test_a_document_disappears_when_an_ancestor_folder_is_deleted(): void
    {
        $d = $this->liveDocument(true);
        $this->assertTrue($this->found($d->doc, $d->title), 'sanity: the live document is found');

        DB::table('course_repository_master')->where('pk', $d->parent)->update(['del_folder_status' => 0]);

        $this->assertFalse($this->found($d->doc, $d->title), 'a document beneath a deleted folder must not be found');
    }

    public function test_suggestions_do_not_offer_documents_in_a_deleted_folder(): void
    {
        $d = $this->liveDocument();
        DB::table('course_repository_master')->where('pk', $d->folder)->update(['del_folder_status' => 0]);
        $this->forgetFolderCaches();

        $suggestions = $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->getJson('/course-repository-user/search/suggest?q=' . urlencode(trim($d->title)))
            ->assertOk()
            ->json('suggestions');

        $offered = collect($suggestions)->where('type', 'Document')->pluck('value')->map(fn ($v) => trim($v));
        // Another live document may legitimately share the title; only fail if
        // every document with this title is hidden and it is still offered.
        $liveTwin = DB::table('course_repository_documents')->where('del_type', 1)
            ->where('file_title', $d->title)->where('pk', '!=', $d->doc)->exists();

        if (! $liveTwin) {
            $this->assertNotContains(trim($d->title), $offered->all());
        } else {
            $this->addToAssertionCount(1);
        }
    }
}
