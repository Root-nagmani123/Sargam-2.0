<?php

namespace Tests\Feature;

use App\Support\CourseRepositorySearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * PR #334 F-086: the Author filter matched the whole keyword ("batch,subject,topic,
 * date,author"), so Author = Singh also returned documents whose course, subject or
 * topic said Singh. It now matches the author the card shows: the stored text, the
 * faculty record a pk resolves to, else the keyword's author segment.
 */
class CourseRepositorySearchAuthorFilterTest extends TestCase
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
            'file_title' => 'Zq author '.$pk,
            'del_type' => 1,
        ]);

        return $pk;
    }

    /** @return array<int,string> detail pk => display_author, for the author filter over $pks */
    private function byAuthor(string $author, array $pks): array
    {
        $this->forgetFolderCaches();
        $rows = CourseRepositorySearch::documentQuery(CourseRepositorySearch::criteria(Request::create('/', 'GET', ['author' => $author])))
            ->whereIn('doc.course_repository_details_pk', $pks)
            ->get();
        CourseRepositorySearch::decorate($rows);

        $found = $rows->pluck('display_author', 'course_repository_details_pk')->all();
        ksort($found);

        return $found;
    }

    public function test_author_matches_only_the_author_never_the_course_subject_or_topic(): void
    {
        $n = 'Zq'.random_int(100000, 999999).'singh';
        $faculty = fn (string $name) => (int) DB::table('faculty_master')->insertGetId([
            'first_name' => 'Zq', 'full_name' => $name, 'faculty_type' => 1,
            'city_master_pk' => 0, 'country_master_pk' => 0, 'state_master_pk' => 0, 'state_district_mapping_pk' => 0,
        ]);
        $other = $faculty('Prof Other Faculty');
        $faculty = $faculty("Dr {$n} Faculty");

        $match = [
            'legacy keyword author' => $this->document(['keyword' => "Phase-I 2024, Law, Topic, part 2, 18-03-2025, Dr. {$n}"]),
            'stored author text' => $this->document(['author_name' => "Prof {$n}", 'keyword' => 'C, S, T, 18-03-2025, Someone Else']),
            'faculty pk' => $this->document(['author_name' => (string) $faculty, 'keyword' => 'C, S, T, 2026-08-06, Someone Else']),
            'no date: fifth part' => $this->document(['keyword' => "C, S, T, X, {$n} Fallback"]),
            'author with a comma' => $this->document(['keyword' => "C, S, T, 18-03-2025,{$n},R"]),
        ];
        $noMatch = [
            'course segment' => $this->document(['keyword' => "{$n} Batch, Law, Topic, 18-03-2025, Prof Other"]),
            'subject segment' => $this->document(['keyword' => "Phase-I, {$n} Law, Topic, 18-03-2025, Prof Other"]),
            'topic with commas' => $this->document(['keyword' => "Phase-I, Law, Rights, {$n} topic, 18-03-2025, Prof Other"]),
            'no date: topic' => $this->document(['keyword' => "C, S, {$n} topic, X, Prof Other"]),
            'stored text wins over keyword' => $this->document(['author_name' => 'Prof Other', 'keyword' => "C, S, T, 18-03-2025, {$n}"]),
            'faculty wins over keyword' => $this->document(['author_name' => (string) $other, 'keyword' => "C, S, T, 18-03-2025, {$n}"]),
        ];
        $all = array_merge(array_values($match), array_values($noMatch));

        $found = $this->byAuthor($n, $all);
        $expected = array_flip($match);
        ksort($expected);
        $this->assertSame(array_keys($expected), array_keys($found), 'only the documents whose author says '.$n);
        foreach ($found as $author) {
            $this->assertStringContainsStringIgnoringCase($n, $author, 'every hit shows the searched author');
        }

        $this->assertSame([$match['author with a comma'] => "{$n}, R"], $this->byAuthor("{$n}, R", $all), 'a multi-part author matches as shown');
        $this->assertSame([], $this->byAuthor("{$n} Law", $all), 'a subject is not an author');
    }
}
