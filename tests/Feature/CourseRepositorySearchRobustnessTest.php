<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CourseRepositoryController;
use App\Support\CourseRepositorySearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

class CourseRepositorySearchRobustnessTest extends TestCase
{
    use RollsBackAgainstAppDatabase {
        setUp as rollbackSetUp;
    }

    protected function setUp(): void
    {
        $this->rollbackSetUp();

        foreach (['folderTree', 'folderChildren', 'hiddenFolderPks'] as $property) {
            $cache = new \ReflectionProperty(CourseRepositorySearch::class, $property);
            $cache->setAccessible(true);
            $cache->setValue(null, null);
        }
    }

    public function test_database_fixtures_run_inside_the_rollback_transaction(): void
    {
        $this->assertGreaterThan(0, DB::transactionLevel());
    }

    private function document(): int
    {
        $folder = DB::table('course_repository_master')->where('del_folder_status', 1)->value('pk');
        if (! $folder) {
            $this->markTestSkipped('no live repository folder');
        }

        $pk = (int) DB::table('course_repository_details')->insertGetId([
            'course_repository_master_pk' => $folder,
            'course_master_pk' => 'Zq robustness course',
            'subject_pk' => 'Zq robustness subject',
            'topic_pk' => 'Zq robustness topic',
            'author_name' => 'Zq robustness author',
            'keyword' => 'Zq robustness course,Zq robustness subject,Zq robustness topic,13-10-2026,Zq robustness author',
            'session_date' => '2026-10-13',
            'status' => 1,
            'created_date' => now(),
        ]);
        DB::table('course_repository_documents')->insert([
            'course_repository_details_pk' => $pk,
            'upload_document' => 'zq-robustness.pdf',
            'file_title' => 'Zq robustness document',
            'del_type' => 1,
        ]);

        return $pk;
    }

    public static function emojiSearches(): array
    {
        $emoji = "\u{1F600}";

        return [
            'emoji keyword' => [['q' => $emoji]],
            'emoji phrase and relevance' => [['q' => 'Zq '.$emoji]],
            'emoji course' => [['course' => $emoji]],
            'emoji subject' => [['subject' => $emoji]],
            'emoji author' => [['author' => $emoji]],
            'emoji categories' => [['q' => $emoji, 'type' => 'categories']],
        ];
    }

    /** @dataProvider emojiSearches */
    public function test_emoji_search_returns_a_search_view_not_an_unavailable_redirect(array $params): void
    {
        $pk = $this->document();
        $request = Request::create('/course-repository-user/search', 'GET', $params);
        $this->app->instance('request', $request);
        Log::shouldReceive('error')->never();

        // Execute the ranked SELECT even when pagination's count would be zero.
        $rows = CourseRepositorySearch::documentQuery(CourseRepositorySearch::criteria($request))
            ->where('doc.course_repository_details_pk', $pk)
            ->get();
        $this->assertCount(0, $rows);

        $response = app(CourseRepositoryController::class)->userSearch($request);
        $this->assertInstanceOf(View::class, $response);
        $this->assertSame('admin.course-repository.user.search', $response->name());
        $this->assertTrue($response->getData()['hasQuery']);
    }

    public function test_course_and_subject_controls_are_removed_without_changing_label_spacing(): void
    {
        $pk = $this->document();
        $request = Request::create('/', 'GET', [
            'course' => "\0Zq robustness\r\n course\x7F",
            'subject' => "Zq robustness\u{0085}\u{2028}\u{2029} subject",
        ]);
        $criteria = CourseRepositorySearch::criteria($request);

        $this->assertSame('Zq robustness course', $criteria['course']);
        $this->assertSame('Zq robustness subject', $criteria['subject']);
        $this->assertSame([$pk], CourseRepositorySearch::documentQuery($criteria)
            ->where('doc.course_repository_details_pk', $pk)
            ->pluck('course_repository_details_pk')->map(fn ($value) => (int) $value)->all());
    }

    public function test_emoji_suggestions_complete_without_logging_a_database_error(): void
    {
        Log::shouldReceive('error')->never();
        $response = app(CourseRepositoryController::class)->userSearchSuggest(
            Request::create('/', 'GET', ['q' => "\u{1F600}\u{1F600}"])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['suggestions' => []], $response->getData(true));
    }

    public function test_ordinary_keyword_search_still_finds_and_ranks_a_document(): void
    {
        $pk = $this->document();
        $criteria = CourseRepositorySearch::criteria(Request::create('/', 'GET', ['q' => 'Zq robustness']));
        $rows = CourseRepositorySearch::documentQuery($criteria)
            ->where('doc.course_repository_details_pk', $pk)->get();

        $this->assertCount(1, $rows);
        $this->assertGreaterThan(0, $rows[0]->cru_relevance);
    }
}
