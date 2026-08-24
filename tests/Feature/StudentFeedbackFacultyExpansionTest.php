<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

/**
 * Guards the faculty-expansion rewrite in CalendarController::studentFeedback().
 *
 * That page decides which feedback a trainee still owes. The original queries cross-joined
 * every faculty_master row and filtered with JSON_CONTAINS; they now join a derived table
 * that expands the timetable JSON into (timetable_pk, faculty_pk) pairs. These tests assert
 * the two produce byte-for-byte the same pairs, including two quirks of the original
 * predicate that were preserved on purpose.
 *
 * Read-only: the suite runs against the database .env points at (see phpunit.xml).
 */
class StudentFeedbackFacultyExpansionTest extends TestCase
{
    private function constant(string $name): string
    {
        return (new ReflectionClass(\App\Http\Controllers\Admin\CalendarController::class))
            ->getConstant($name);
    }

    private function skipUnlessTimetable(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('timetable')
            || DB::table('timetable')->limit(1)->count() === 0) {
            $this->markTestSkipped('No timetable rows to exercise.');
        }
    }

    /** Legacy sessions: faculty ids live in timetable.faculty_master. */
    public function test_legacy_faculty_expansion_matches_the_original_json_contains_predicate(): void
    {
        $this->skipUnlessTimetable();

        // What the code used to do: every faculty x every session, filtered by JSON_CONTAINS.
        $original = DB::select("
            SELECT t.pk AS timetable_pk, f.pk AS faculty_pk
            FROM timetable t
            JOIN faculty_master f ON (
                (JSON_VALID(t.faculty_master) AND JSON_CONTAINS(t.faculty_master, JSON_QUOTE(CAST(f.pk AS CHAR))))
                OR (NOT JSON_VALID(t.faculty_master) AND CAST(t.faculty_master AS CHAR) = CAST(f.pk AS CHAR))
            )
        ");

        // What it does now.
        $rewritten = DB::select("
            SELECT t.pk AS timetable_pk, f.pk AS faculty_pk
            FROM timetable t
            JOIN " . $this->constant('OLD_FACULTY_JSON_TABLE') . " ON fj.timetable_pk = t.pk
            JOIN faculty_master f ON f.pk = fj.faculty_pk
        ");

        $this->assertSame(
            $this->normalisePairs($original),
            $this->normalisePairs($rewritten),
            'The derived-table expansion no longer selects the same (session, faculty) pairs '
            . 'as the original JSON_CONTAINS predicate.'
        );
    }

    /** Newer sessions: faculty live in timetable.faculty_details with a role. */
    public function test_teaching_faculty_expansion_matches_the_original_json_contains_predicate(): void
    {
        $this->skipUnlessTimetable();

        $original = DB::select("
            SELECT t.pk AS timetable_pk, f.pk AS faculty_pk
            FROM timetable t
            JOIN faculty_master f ON (
                JSON_VALID(t.faculty_details) = 1
                AND JSON_CONTAINS(t.faculty_details, JSON_OBJECT('faculty_pk', f.pk, 'role', 'Teaching')) = 1
            )
        ");

        $rewritten = DB::select("
            SELECT t.pk AS timetable_pk, f.pk AS faculty_pk
            FROM timetable t
            JOIN " . $this->constant('TEACHING_FACULTY_JSON_TABLE') . " ON fd.timetable_pk = t.pk
            JOIN faculty_master f ON f.pk = fd.faculty_pk
        ");

        $this->assertSame(
            $this->normalisePairs($original),
            $this->normalisePairs($rewritten)
        );
    }

    /**
     * Quirk 1, preserved deliberately: sessions storing faculty_master as a bare JSON number
     * (e.g. `3`) match no faculty, because JSON_CONTAINS(3, '"3"') compares a JSON number to a
     * JSON string. Those sessions never ask the trainee for feedback.
     *
     * This is a latent data/logic bug, reported separately. It is asserted here so that if
     * anyone "fixes" the expansion without deciding what to do about those sessions, the
     * behaviour change is caught rather than silently shipped.
     */
    public function test_scalar_json_faculty_master_still_matches_no_faculty(): void
    {
        $this->skipUnlessTimetable();

        $scalarRows = DB::table('timetable')
            ->whereRaw('JSON_VALID(faculty_master)')
            ->whereRaw("JSON_TYPE(faculty_master) <> 'ARRAY'")
            ->count();

        if ($scalarRows === 0) {
            $this->markTestSkipped('No scalar-JSON faculty_master rows in this dataset.');
        }

        $matched = DB::select("
            SELECT COUNT(*) AS n
            FROM timetable t
            JOIN " . $this->constant('OLD_FACULTY_JSON_TABLE') . " ON fj.timetable_pk = t.pk
            WHERE JSON_VALID(t.faculty_master) AND JSON_TYPE(t.faculty_master) <> 'ARRAY'
        ");

        $this->assertSame(
            0,
            (int) $matched[0]->n,
            "Sessions with a scalar-JSON faculty_master started matching faculty. That changes "
            . "which sessions trainees are asked to give feedback for — intended or not, it "
            . "must be a deliberate decision, not a side effect."
        );
    }

    /**
     * Quirk 2, preserved deliberately: the original compared faculty ids as strings, so a
     * non-canonical value such as "012" would not have matched pk 12.
     */
    public function test_faculty_ids_are_matched_textually_not_just_numerically(): void
    {
        $this->skipUnlessTimetable();

        $rows = DB::select("
            SELECT COUNT(*) AS n
            FROM timetable t
            CROSS JOIN JSON_TABLE(
                CASE WHEN JSON_VALID(t.faculty_master) THEN t.faculty_master
                     ELSE JSON_ARRAY(CAST(t.faculty_master AS CHAR)) END,
                '$[*]' COLUMNS (faculty_txt VARCHAR(64) PATH '$')
            ) jt
            WHERE jt.faculty_txt <> CAST(CAST(jt.faculty_txt AS UNSIGNED) AS CHAR)
        ");

        $this->assertSame(
            0,
            (int) $rows[0]->n,
            'Non-canonical numeric faculty ids appeared in the data. The expansion filters them '
            . 'out to match the original string comparison — confirm that is still what you want.'
        );
    }

    /**
     * Equivalence on JSON shapes the live data does not currently contain.
     *
     * The production dataset only holds arrays of numeric strings plus a few bare JSON
     * numbers, so the guards inside the expansion are not exercised by the tests above —
     * removing them does not change any result today. This drives the same two expressions
     * over synthetic values instead, so the guards are actually verified, and a future import
     * that introduces (say) arrays of JSON numbers cannot quietly change who gets asked for
     * feedback. Nothing is written to the database: the values are SQL literals.
     *
     * @dataProvider facultyMasterShapes
     */
    public function test_expansion_matches_original_predicate_for_json_shapes(string $literal, string $why): void
    {
        $this->skipUnlessTimetable();

        $pk = (int) DB::table('faculty_master')->value('pk');
        if (! $pk) {
            $this->markTestSkipped('No faculty_master rows.');
        }

        // Substitute the real pk so the literal is self-consistent, then compare the two
        // expressions on identical input.
        $value = str_replace('{PK}', (string) $pk, $literal);
        $quoted = "'" . str_replace("'", "''", $value) . "'";

        $originalMatches = (bool) DB::selectOne("
            SELECT (
                (JSON_VALID($quoted) AND JSON_CONTAINS($quoted, JSON_QUOTE(CAST($pk AS CHAR))))
                OR (NOT JSON_VALID($quoted) AND CAST($quoted AS CHAR) = CAST($pk AS CHAR))
            ) AS matched
        ")->matched;

        // The expansion body, with the timetable column replaced by the literal.
        $expansion = str_replace(
            ['tt.faculty_master', 'tt.pk AS timetable_pk', 'FROM timetable tt'],
            [$quoted, '1 AS timetable_pk', 'FROM (SELECT 1) AS tt'],
            $this->constant('OLD_FACULTY_JSON_TABLE')
        );

        $rewrittenMatches = (int) DB::selectOne(
            "SELECT COUNT(*) AS n FROM " . $expansion . " WHERE fj.faculty_pk = $pk"
        )->n > 0;

        $this->assertSame(
            $originalMatches,
            $rewrittenMatches,
            "Expansion diverged from the original predicate for $value ($why)."
        );
    }

    public static function facultyMasterShapes(): array
    {
        return [
            'array of numeric strings (the normal case)' => ['["{PK}"]', 'must match'],
            'array with several entries' => ['["999999","{PK}"]', 'must match'],
            'array of JSON numbers' => ['[{PK}]', 'JSON_QUOTE compared against a string, so a number never matched'],
            'bare JSON number' => ['{PK}', 'JSON_VALID is true, so the non-JSON fallback never ran'],
            'non-canonical numeric string' => ['["0{PK}"]', 'string comparison, so leading zeros did not match'],
            'unrelated id' => ['["999999"]', 'must not match'],
            'empty array' => ['[]', 'must not match'],
            'plain non-JSON scalar' => ['{PK}x', 'invalid JSON, falls through to the CHAR comparison'],
        ];
    }

    /** @param array<int, object> $rows */
    private function normalisePairs(array $rows): array
    {
        $pairs = array_map(
            fn ($r) => (int) $r->timetable_pk . ':' . (int) $r->faculty_pk,
            $rows
        );
        sort($pairs);

        return array_values(array_unique($pairs));
    }
}
