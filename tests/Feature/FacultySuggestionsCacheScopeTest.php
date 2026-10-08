<?php

namespace Tests\Feature;

use App\Support\FeedbackReportCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * /faculty_view/suggestions narrows a faculty viewer to their own name, but the
 * typeahead cache key did not include that scope: whichever viewer filled a key
 * first decided what every other viewer got for the TTL (PR #334 F-039).
 *
 * Two real faculty with submitted feedback are renamed inside the test's
 * transaction (rolled back) so one search term matches both. The cache is pinned
 * to the in-memory array store so nothing reaches the shared cache.
 */
class FacultySuggestionsCacheScopeTest extends TestCase
{
    // Aliased: this class defines its own setUp()/tearDown(), which would
    // otherwise replace the trait's and never open or roll back the transaction.
    use RollsBackAgainstAppDatabase {
        setUp as openRollbackTransaction;
        tearDown as rollBackTransaction;
    }

    private const TERM = 'Zqxsuggest';

    private int $facultyA;

    private int $facultyB;

    protected function setUp(): void
    {
        $this->openRollbackTransaction();

        $prop = new \ReflectionProperty(FeedbackReportCache::class, 'resolvedStore');
        $prop->setAccessible(true);
        $prop->setValue(null, 'array');
        Cache::store('array')->flush();

        $pks = DB::table('topic_feedback as tf')
            ->join('timetable as tt', 'tf.timetable_pk', '=', 'tt.pk')
            ->join('faculty_master as fm', 'tf.faculty_pk', '=', 'fm.pk')
            ->where('tf.is_submitted', 1)
            ->whereIn('tt.faculty_type', ['1', '2'])
            ->distinct()
            ->orderBy('fm.pk')
            ->limit(2)
            ->pluck('fm.pk')
            ->map(fn ($v) => (int) $v)
            ->all();
        if (count($pks) < 2) {
            $this->markTestSkipped('needs two faculty with submitted feedback');
        }
        [$this->facultyA, $this->facultyB] = $pks;

        DB::table('faculty_master')->where('pk', $this->facultyA)->update(['full_name' => self::TERM.' Alpha']);
        DB::table('faculty_master')->where('pk', $this->facultyB)->update(['full_name' => self::TERM.' Beta']);
    }

    protected function tearDown(): void
    {
        $prop = new \ReflectionProperty(FeedbackReportCache::class, 'resolvedStore');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->rollBackTransaction();
    }

    private function names($response): array
    {
        $response->assertOk();

        return collect($response->json('faculties'))->pluck('full_name')->unique()->sort()->values()->all();
    }

    private function asAdmin()
    {
        return $this->as($this->userWithRole('Super Admin'), ['Super Admin']);
    }

    /** A faculty-portal login that resolves to faculty A (not persisted). */
    private function asFacultyA()
    {
        $user = $this->officerTrainee()->replicate();
        $employeePk = DB::table('faculty_master')->where('pk', $this->facultyA)->value('employee_master_pk');
        $user->user_id = $employeePk ?: $this->facultyA;
        $user->user_category = 'F';

        $this->actingAs($user)->withSession(['user_roles' => ['Internal Faculty']]);
        if (get_auth_faculty_master_pk() !== $this->facultyA) {
            $this->markTestSkipped('could not resolve a login to the test faculty');
        }

        return $this;
    }

    private function search()
    {
        return $this->getJson(route('feedback.faculty_suggestions', ['faculty_name' => self::TERM]));
    }

    public function test_a_faculty_after_an_admin_gets_only_their_own_name(): void
    {
        $this->assertSame([self::TERM.' Alpha', self::TERM.' Beta'], $this->names($this->asAdmin()->search()));

        $this->assertSame([self::TERM.' Alpha'], $this->names($this->asFacultyA()->search()));
    }

    public function test_an_admin_after_a_faculty_still_gets_every_name(): void
    {
        $this->assertSame([self::TERM.' Alpha'], $this->names($this->asFacultyA()->search()));

        $this->assertSame([self::TERM.' Alpha', self::TERM.' Beta'], $this->names($this->asAdmin()->search()));
    }

    public function test_an_array_valued_name_does_not_500(): void
    {
        $this->asAdmin()
            ->getJson(route('feedback.faculty_suggestions', ['faculty_name' => ['x']]))
            ->assertOk();
    }
}
