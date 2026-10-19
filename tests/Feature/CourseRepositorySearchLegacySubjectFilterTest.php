<?php

namespace Tests\Feature;

use App\Support\CourseRepositorySearch;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Results show a legacy row's subject/course from the keyword when the column holds a
 * bare imported id, but the Subject and Course filters compared the raw column, so a
 * document shown as "Law" was missing from Subject = Law (PR #334 F-078).
 */
class CourseRepositorySearchLegacySubjectFilterTest extends TestCase
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
            'file_title' => 'Zq legacy filter '.$pk,
            'del_type' => 1,
        ]);

        return $pk;
    }

    /** @return Collection decorated rows among $pks that the filter returns */
    private function filtered(array $params, array $pks)
    {
        $this->forgetFolderCaches();
        $rows = CourseRepositorySearch::documentQuery(CourseRepositorySearch::criteria(Request::create('/', 'GET', $params)))
            ->whereIn('doc.course_repository_details_pk', $pks)
            ->get();
        CourseRepositorySearch::decorate($rows);

        return $rows->keyBy('course_repository_details_pk');
    }

    public function test_subject_filter_matches_the_subject_a_legacy_row_displays(): void
    {
        $x = 'Zqsubj'.random_int(100000, 999999);
        $y = $x.'Other';

        $legacy = $this->document(['subject_pk' => '169438950', 'keyword' => "Phase-I 2024,{$x},Topic, with comma,01-02-2024,Zq Author"]);
        $textual = $this->document(['subject_pk' => $x, 'keyword' => "Phase-I 2024,{$x},Topic,01-02-2024,Zq Author"]);
        $legacyOther = $this->document(['subject_pk' => '169438951', 'keyword' => "Phase-I 2024,{$y},Topic,01-02-2024,Zq Author"]);
        // The column wins over the keyword whenever it is real text, as on screen.
        $textualOther = $this->document(['subject_pk' => $y, 'keyword' => "Phase-I 2024,{$x},Topic,01-02-2024,Zq Author"]);

        $rows = $this->filtered(['subject' => $x], [$legacy, $textual, $legacyOther, $textualOther]);

        $this->assertTrue($rows->has($legacy), 'legacy numeric subject_pk displayed as X is returned');
        $this->assertSame($x, $rows[$legacy]->display_subject);
        $this->assertTrue($rows->has($textual), 'textual subject_pk X is still returned');
        $this->assertFalse($rows->has($legacyOther), 'legacy row displayed as another subject is not returned');
        $this->assertFalse($rows->has($textualOther), 'row displayed as another subject is not returned');
    }

    public function test_course_filter_matches_the_course_a_legacy_row_displays(): void
    {
        $x = 'Zqcourse'.random_int(100000, 999999);

        $legacy = $this->document(['course_master_pk' => '4711', 'keyword' => "{$x},Law,Topic,01-02-2024,Zq Author"]);
        $blank = $this->document(['course_master_pk' => null, 'keyword' => "{$x},Law,Topic,01-02-2024,Zq Author"]);
        $other = $this->document(['course_master_pk' => '4712', 'keyword' => "{$x}Other,Law,Topic,01-02-2024,Zq Author"]);

        $rows = $this->filtered(['course' => $x], [$legacy, $blank, $other]);

        $this->assertTrue($rows->has($legacy), 'legacy numeric course_master_pk displayed as X is returned');
        $this->assertSame($x, $rows[$legacy]->display_course);
        $this->assertTrue($rows->has($blank), 'blank course_master_pk displayed as X is returned');
        $this->assertFalse($rows->has($other), 'row displayed as another course is not returned');
    }
}
