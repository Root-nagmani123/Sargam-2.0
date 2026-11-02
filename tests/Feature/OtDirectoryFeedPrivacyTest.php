<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DirectoryController;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * /directory/ot/data is open to every signed-in account. It must not return an
 * OT's mobile number, nor let a number be searched to find who owns it
 * (PR #334 F-005, product decision 2026-10-06). The gated export keeps it.
 */
class OtDirectoryFeedPrivacyTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    /** @return array{0: string, 1: int, 2: \Illuminate\Support\Collection} status, course pk, that course's OT rows */
    private function courseWithStudents(): array
    {
        $controller = app(DirectoryController::class);
        $courses = new \ReflectionMethod($controller, 'otCourses');
        $courses->setAccessible(true);
        $students = new \ReflectionMethod($controller, 'otStudentsQuery');
        $students->setAccessible(true);

        foreach (['active', 'archive'] as $status) {
            foreach ($courses->invoke($controller, $status) as $course) {
                $rows = $students->invoke($controller, (int) $course->pk, '')->get();
                if ($rows->contains(fn ($r) => trim((string) $r->contact_no) !== '')) {
                    return [$status, (int) $course->pk, $rows];
                }
            }
        }

        $this->markTestSkipped('no OT course whose students carry a mobile number');
    }

    public function test_the_feed_carries_no_mobile_number(): void
    {
        [$status, $course, $rows] = $this->courseWithStudents();

        $response = $this->as($this->officerTrainee(), ['Student-OT'])
            ->getJson('/directory/ot/data?' . http_build_query(['status' => $status, 'course_id' => $course, 'draw' => 1, 'start' => 0, 'length' => 100]))
            ->assertOk();

        $data = $response->json('data');
        $this->assertNotEmpty($data, 'the feed should list the course');

        foreach ($data as $row) {
            $this->assertArrayNotHasKey('mobile', $row);
        }

        $body = (string) $response->getContent();
        foreach ($rows->pluck('contact_no')->filter(fn ($n) => strlen(trim((string) $n)) >= 6) as $number) {
            $this->assertStringNotContainsString(trim((string) $number), $body, 'no mobile number may appear in the feed');
        }
    }

    public function test_a_mobile_number_cannot_be_searched(): void
    {
        [$status, $course, $rows] = $this->courseWithStudents();

        // A number that appears in no searchable field of ANY row in the course
        // (name, OT code, email, cadre), so a hit could only come from
        // contact_no itself.
        $searchable = $rows->map(fn ($r) => implode(' ', [
            (string) $r->display_name, (string) $r->generated_OT_code, (string) $r->email, (string) ($r->cadre_name ?? ''),
        ]))->implode("\n");

        $target = $rows->first(function ($r) use ($searchable) {
            $n = trim((string) $r->contact_no);

            return strlen($n) >= 6 && ! str_contains($searchable, $n);
        });

        if (! $target) {
            $this->markTestSkipped('no mobile number distinct from the searchable fields');
        }

        $this->as($this->officerTrainee(), ['Student-OT'])
            // DataTables sends the term as search[value]; the feed rewrites it onto ?q.
            ->getJson('/directory/ot/data?' . http_build_query(['status' => $status, 'course_id' => $course, 'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => trim((string) $target->contact_no)]]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 0);
    }
}
