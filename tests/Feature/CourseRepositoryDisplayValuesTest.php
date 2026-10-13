<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CourseRepositoryController;
use App\Support\CourseRepositorySearch;
use App\Support\DataTableSearchHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

class CourseRepositoryDisplayValuesTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function document(array $detail): int
    {
        $folder = DB::table('course_repository_master')->where('del_folder_status', 1)->value('pk');
        if (! $folder) {
            $this->markTestSkipped('no live repository folder');
        }
        $pk = (int) DB::table('course_repository_details')->insertGetId($detail + [
            'course_repository_master_pk' => $folder, 'status' => 1, 'created_date' => now(),
        ]);
        DB::table('course_repository_documents')->insert([
            'course_repository_details_pk' => $pk, 'upload_document' => 'zq-display-'.$pk.'.pdf',
            'file_title' => 'Zq display '.$pk, 'del_type' => 1,
        ]);

        return $pk;
    }

    private function rows(array $params, array $pks)
    {
        foreach (['folderTree', 'folderChildren', 'hiddenFolderPks'] as $property) {
            $cache = new \ReflectionProperty(CourseRepositorySearch::class, $property);
            $cache->setAccessible(true);
            $cache->setValue(null, null);
        }
        $rows = CourseRepositorySearch::documentQuery(CourseRepositorySearch::criteria(Request::create('/', 'GET', $params)))
            ->whereIn('doc.course_repository_details_pk', $pks)->get();
        CourseRepositorySearch::decorate($rows);

        return $rows->keyBy('course_repository_details_pk');
    }

    private function faculty(string $code, string $name): int
    {
        return (int) DB::table('faculty_master')->insertGetId([
            'faculty_code' => $code, 'first_name' => 'Zq', 'full_name' => $name, 'faculty_type' => 1,
            'city_master_pk' => 0, 'country_master_pk' => 0, 'state_master_pk' => 0, 'state_district_mapping_pk' => 0,
        ]);
    }

    public function test_truncated_course_and_author_show_full_keyword_values_and_filter_by_them(): void
    {
        $course = 'Zq_% Phase-II-2024 '.random_int(100000, 999999);
        $author = 'Dr Zq Complete Faculty';
        $coursePrefix = 'Zq_% Phase-II-202';
        $doc = $this->document([
            'course_master_pk' => $coursePrefix, 'author_name' => 'Dr Zq Complete',
            'keyword' => "{$course},Law,Topic, with comma,13-10-2026,{$author}",
        ]);
        $rows = $this->rows([], [$doc]);
        $this->assertSame($course, $rows[$doc]->display_course);
        $this->assertSame($author, $rows[$doc]->display_author);
        $this->assertTrue($this->rows(['course' => $course, 'author' => $author], [$doc])->has($doc));
        $this->assertFalse($this->rows(['course' => $coursePrefix], [$doc])->has($doc));
        $facets = CourseRepositorySearch::facets();
        $this->assertContains($course, $facets['courses']);
        $this->assertNotContains($coursePrefix, $facets['courses']);
        $this->assertContains($course, CourseRepositorySearch::displayLabelSuggestions(
            'course_master_pk', DataTableSearchHelper::likePattern($course), 5
        )->all());
    }

    public function test_php_and_sql_prefix_rules_agree_without_case_folding_or_wildcard_matching(): void
    {
        foreach ([
            ['Law', 'law and policy', 'Law'],
            ['Zq_%', 'Zq_Complete', 'Zq_%'],
            ["\u{0939}\u{093F}", "\u{0939}\u{093F}\u{0902}\u{0926}\u{0940}", "\u{0939}\u{093F}\u{0902}\u{0926}\u{0940}"],
            ['Current course', 'Stale course', 'Current course'],
        ] as [$stored, $keyword, $expected]) {
            $doc = $this->document(['course_master_pk' => $stored, 'keyword' => "{$keyword},Law,Topic,13-10-2026,Author"]);
            $rows = $this->rows(['course' => $expected], [$doc]);
            $this->assertTrue($rows->has($doc), 'SQL filter matches the PHP label');
            $this->assertSame($expected, $rows[$doc]->display_course);
        }
    }

    public function test_a_faculty_code_resolves_to_a_name_in_display_and_author_search_without_duplicate_rows(): void
    {
        $code = 'GUE-Zq'.random_int(100000, 999999);
        $name = 'Dr Zq Resolved Faculty';
        $this->faculty($code, $name);
        $doc = $this->document([
            'author_name' => $code, 'keyword' => 'Course,Law,Topic,13-10-2026,Stale Faculty',
        ]);
        $rows = $this->rows(['author' => $name], [$doc]);
        $this->assertCount(1, $rows);
        $this->assertSame($name, $rows[$doc]->display_author);
        $this->assertCount(0, $this->rows(['author' => 'Stale Faculty'], [$doc]));
        $this->assertCount(1, $this->rows(['q' => 'Resolved Faculty'], [$doc]));
        $this->assertContains($name, CourseRepositorySearch::displayLabelSuggestions(
            'author_name', DataTableSearchHelper::likePattern($name), 5
        )->all());
        $suggestions = app(CourseRepositoryController::class)->userSearchSuggest(
            Request::create('/', 'GET', ['q' => $name])
        )->getData(true)['suggestions'];
        $this->assertContains(['label' => $name, 'value' => $name, 'type' => 'Author'], $suggestions);

        // A duplicated code cannot identify an actor reliably or multiply results.
        $this->faculty($code, 'Another Zq Faculty');
        $rows = $this->rows([], [$doc]);
        $this->assertCount(1, $rows);
        $this->assertSame($code, $rows[$doc]->display_author);
        $this->assertCount(0, $this->rows(['author' => $name], [$doc]));
    }
}
