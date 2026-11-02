<?php

namespace Tests\Feature;

use App\Support\CourseRepositorySearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * The upload form stores local course_master / subject_master / faculty_master pks in
 * the *_pk columns, but search read every digits-only value as a dead legacy id: the
 * card showed the keyword instead of the record, the course never reached the Course
 * dropdown, and an ISO date in the keyword shifted the author (PR #334 F-084).
 */
class CourseRepositorySearchLocalPkTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function forgetFolderCaches(): void
    {
        foreach (['folderTree', 'folderChildren', 'hiddenFolderPks'] as $property) {
            $p = new \ReflectionProperty(CourseRepositorySearch::class, $property);
            $p->setAccessible(true);
            $p->setValue(null, null);
        }
    }

    /** A live document whose detail row carries the given columns; returns the detail pk. */
    private function document(array $detail): int
    {
        $folder = DB::table('course_repository_master')->where('del_folder_status', 1)->orderBy('pk')->value('pk');
        if (! $folder) {
            $this->markTestSkipped('no live repository folder');
        }

        $pk = (int) DB::table('course_repository_details')->insertGetId($detail + [
            'course_repository_master_pk' => $folder,
            'status' => 1,
            'created_date' => now(),
        ]);
        DB::table('course_repository_documents')->insert([
            'course_repository_details_pk' => $pk,
            'upload_document' => 'zq-'.$pk.'.pdf',
            'file_title' => 'Zq local pk '.$pk,
            'del_type' => 1,
        ]);

        return $pk;
    }

    private function rows(array $params, array $pks)
    {
        $this->forgetFolderCaches();
        $rows = CourseRepositorySearch::documentQuery(CourseRepositorySearch::criteria(Request::create('/', 'GET', $params)))
            ->whereIn('doc.course_repository_details_pk', $pks)
            ->get();
        CourseRepositorySearch::decorate($rows);

        return $rows->keyBy('course_repository_details_pk');
    }

    private function facets(): array
    {
        $this->forgetFolderCaches();

        return CourseRepositorySearch::facets();
    }

    private function faculty(string $fullName): int
    {
        return (int) DB::table('faculty_master')->insertGetId([
            'first_name' => 'Zq', 'full_name' => $fullName, 'faculty_type' => 1,
            'city_master_pk' => 0, 'country_master_pk' => 0, 'state_master_pk' => 0, 'state_district_mapping_pk' => 0,
        ]);
    }

    public function test_a_local_course_pk_is_labelled_offered_and_filtered_by_its_course_name(): void
    {
        $name = 'Zqcourse '.random_int(100000, 999999);
        $course = (int) DB::table('course_master')->insertGetId(['course_name' => $name, 'course_year' => 2026]);
        // The keyword's course segment differs, so only resolving the pk can produce $name.
        $doc = $this->document(['course_master_pk' => (string) $course, 'keyword' => 'Stale label, Law, Topic, 2026-08-06, Zq Author']);

        $all = $this->rows([], [$doc]);
        $this->assertSame($name, $all[$doc]->display_course, 'card shows the course record');
        $this->assertContains($name, $this->facets()['courses'], 'course is offered in the dropdown');
        $this->assertTrue($this->rows(['course' => $name], [$doc])->has($doc), 'Course = its name returns it');
        $this->assertFalse($this->rows(['course' => 'Stale label'], [$doc])->has($doc), 'the keyword label no longer matches');
    }

    public function test_a_local_subject_pk_is_labelled_offered_and_filtered_by_its_subject_name(): void
    {
        $name = 'Zqsubject '.random_int(100000, 999999);
        $subject = (int) DB::table('subject_master')->insertGetId(['subject_name' => $name, 'active_inactive' => 1]);
        $doc = $this->document(['subject_pk' => (string) $subject, 'keyword' => 'Phase-II-2024, Stale subject, Topic, 2026-08-06, Zq Author']);

        $this->assertSame($name, $this->rows([], [$doc])[$doc]->display_subject);
        $this->assertContains($name, $this->facets()['subjects']);
        $this->assertTrue($this->rows(['subject' => $name], [$doc])->has($doc));
    }

    public function test_a_local_faculty_pk_is_labelled_and_found_by_the_author_filter(): void
    {
        $name = 'Mr Zqfaculty '.random_int(100000, 999999);
        $faculty = $this->faculty($name);
        $doc = $this->document(['author_name' => (string) $faculty, 'keyword' => 'Phase-II-2024, Law, Topic, 2026-08-06, Stale Author']);

        $this->assertSame($name, $this->rows([], [$doc])[$doc]->display_author);
        $this->assertTrue($this->rows(['author' => substr($name, 3)], [$doc])->has($doc), 'Author filter finds the faculty name');
    }

    public function test_an_iso_dated_form_keyword_with_a_comma_in_the_topic_yields_the_right_author(): void
    {
        $parsed = CourseRepositorySearch::parseKeyword('Phase-II-2024, Law, Balancing Development, Forest Rights, 2026-08-06, Mr Vishnu V');
        $this->assertSame('Balancing Development, Forest Rights', $parsed['topic']);
        $this->assertSame('Mr Vishnu V', $parsed['author']);

        // The legacy dd-mm-yyyy anchor still works.
        $this->assertSame('Zq Author', CourseRepositorySearch::parseKeyword('Phase-I 2024,Law,A, B,01-02-2024,Zq Author')['author']);

        // On a card: an author pk that resolves to nothing falls back to that keyword author.
        $doc = $this->document(['author_name' => '987654321987', 'keyword' => 'Phase-II-2024, Law, Balancing Development, Forest Rights, 2026-08-06, Mr Vishnu V']);
        $this->assertSame('Mr Vishnu V', $this->rows([], [$doc])[$doc]->display_author);
    }

    public function test_an_unresolvable_legacy_numeric_pk_still_falls_back_to_the_keyword(): void
    {
        $label = 'Zqlegacy '.random_int(100000, 999999);
        $missing = (string) ((int) DB::table('course_master')->max('pk') + 100000);
        $doc = $this->document(['course_master_pk' => $missing, 'subject_pk' => '987654321', 'keyword' => "{$label},{$label} subj,Topic,01-02-2024,Zq Author"]);

        $row = $this->rows([], [$doc])[$doc];
        $this->assertSame($label, $row->display_course);
        $this->assertSame("{$label} subj", $row->display_subject);
        $this->assertTrue($this->rows(['course' => $label], [$doc])->has($doc));
        $this->assertContains($label, $this->facets()['courses'], 'the label the card shows is offered');
    }

    public function test_a_selected_course_or_subject_that_is_not_an_option_stays_selected(): void
    {
        $this->forgetFolderCaches();
        $html = $this->as($this->staffWithRole('Super Admin'), ['Super Admin'])
            ->get(route('admin.course-repository.user.search', ['course' => 'Zq no such course', 'subject' => 'Zq no such subject']))
            ->assertOk()
            ->getContent();

        // preg_match, not assertMatchesRegularExpression: a failure would print the whole page.
        $this->assertSame(1, preg_match('/<option value="Zq no such course"\s+selected/', $html), 'selected course is rendered and selected');
        $this->assertSame(1, preg_match('/<option value="Zq no such subject"\s+selected/', $html), 'selected subject is rendered and selected');
    }
}
