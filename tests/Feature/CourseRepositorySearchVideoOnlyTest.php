<?php

namespace Tests\Feature;

use App\Support\CourseRepositorySearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Universal search was built on course_repository_documents, but the upload form
 * also accepts a session with only a video link (attachments are optional), which
 * has no document row — so searching its topic found nothing, Videos tab
 * included (PR #334 F-049).
 */
class CourseRepositorySearchVideoOnlyTest extends TestCase
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

    /** @return array{0:int,1:string} [detail pk, its unique topic] */
    private function videoOnlySession(): array
    {
        $folder = DB::table('course_repository_master')->where('del_folder_status', 1)->orderBy('pk')->value('pk');
        if (! $folder) {
            $this->markTestSkipped('no live repository folder');
        }

        $topic = 'Zqvideoonly' . random_int(100000, 999999);
        $pk = (int) DB::table('course_repository_details')->insertGetId([
            'course_repository_master_pk' => $folder,
            'topic_pk' => $topic,
            'videolink' => 'https://example.invalid/video/' . $topic,
            'video_download_enabled' => 1,
            'status' => 1,
            'created_date' => now(),
        ]);

        return [$pk, $topic];
    }

    private function found(int $detailPk, string $q, ?string $type = null): bool
    {
        $this->forgetFolderCaches();
        $criteria = CourseRepositorySearch::criteria(Request::create('/', 'GET', array_filter(['q' => $q, 'type' => $type])));

        return CourseRepositorySearch::documentQuery($criteria)
            ->where('doc.course_repository_details_pk', $detailPk)
            ->exists();
    }

    public function test_a_video_only_session_is_found_by_its_topic(): void
    {
        [$pk, $topic] = $this->videoOnlySession();

        $this->assertTrue($this->found($pk, $topic), 'found under All');
        $this->assertTrue($this->found($pk, $topic, CourseRepositorySearch::TYPE_VIDEOS), 'found on the Videos tab');
        $this->assertFalse($this->found($pk, $topic, CourseRepositorySearch::TYPE_DOCUMENTS), 'not a document');
    }

    public function test_the_result_is_titled_by_its_topic_and_has_no_file(): void
    {
        [$pk, $topic] = $this->videoOnlySession();
        $this->forgetFolderCaches();

        $rows = CourseRepositorySearch::documentQuery(CourseRepositorySearch::criteria(Request::create('/', 'GET', ['q' => $topic])))
            ->where('doc.course_repository_details_pk', $pk)
            ->get();
        CourseRepositorySearch::decorate($rows);

        $this->assertCount(1, $rows);
        $this->assertSame($topic, $rows[0]->display_title);
        $this->assertNull($rows[0]->pk);
        $this->assertNotNull($rows[0]->display_video);
    }

    public function test_a_session_with_a_document_is_not_listed_twice(): void
    {
        [$pk, $topic] = $this->videoOnlySession();
        DB::table('course_repository_documents')->insert([
            'course_repository_details_pk' => $pk,
            'upload_document' => 'zq.pdf',
            'file_title' => $topic,
            'del_type' => 1,
        ]);
        $this->forgetFolderCaches();

        $count = CourseRepositorySearch::documentQuery(CourseRepositorySearch::criteria(Request::create('/', 'GET', ['q' => $topic])))
            ->where('doc.course_repository_details_pk', $pk)
            ->count();

        $this->assertSame(1, $count);
    }
}
