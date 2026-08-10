<?php

namespace Tests\Unit;

use App\Http\Controllers\FC\DescriptiveDataReportController;
use App\Services\FC\FcDescriptiveDataFieldResolver;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The database-free core of the Descriptive Data report.
 *
 * Two things are pinned here, both chosen because a defect in each already reached a built
 * artifact during development and was only caught by inspecting the output by hand:
 *
 *   1. selection() — how the `cols` query parameter narrows the exported column set. The
 *      subtle part is that "which fields" and "does Username survive" are two answers that
 *      MUST agree; deciding them separately produced a shape the screen can never emit
 *      (every field, but no Username) for unrecognised input.
 *
 *   2. definition() — the report's column contract. A silent rename or a filter type added
 *      without a matching options branch is exactly how the Service dropdown shipped empty.
 *
 * No database and no container: selection() is pure, and definition() is static. That is what
 * makes these runnable today — phpunit.xml still has no test connection (both DB_CONNECTION
 * lines are commented out), so the SQL half of this feature remains uncoverable here and the
 * query-level checks in the PR-282 review still have to be run by hand.
 */
class FcDescriptiveDataSelectionTest extends TestCase
{
    /** selection() is private; it is the unit under test, so reach it directly. */
    private function selection(array $fields, array $query): array
    {
        $method = new ReflectionMethod(DescriptiveDataReportController::class, 'selection');
        $method->setAccessible(true);

        return $method->invoke(
            new DescriptiveDataReportController(),
            $fields,
            new Request($query)
        );
    }

    /** A minimal stand-in for a resolved field set, in definition order. */
    private function fields(): array
    {
        return [
            'first_name' => ['label' => 'First Name', 'type' => 'text'],
            'gender' => ['label' => 'Gender', 'type' => 'text'],
            'photo_path' => ['label' => 'Photo', 'type' => 'file'],
        ];
    }

    public function test_absent_cols_returns_every_field_and_keeps_username(): void
    {
        $result = $this->selection($this->fields(), []);

        $this->assertSame(['first_name', 'gender', 'photo_path'], array_keys($result['fields']));
        $this->assertTrue($result['username']);
    }

    public function test_empty_cols_is_treated_as_absent(): void
    {
        $result = $this->selection($this->fields(), ['cols' => '   ']);

        $this->assertCount(3, $result['fields']);
        $this->assertTrue($result['username']);
    }

    public function test_cols_narrows_to_the_requested_fields(): void
    {
        $result = $this->selection($this->fields(), ['cols' => 'first_name,photo_path,login_username']);

        $this->assertSame(['first_name', 'photo_path'], array_keys($result['fields']));
        $this->assertTrue($result['username']);
    }

    public function test_username_is_dropped_when_not_requested(): void
    {
        $result = $this->selection($this->fields(), ['cols' => 'first_name']);

        $this->assertSame(['first_name'], array_keys($result['fields']));
        $this->assertFalse($result['username'], 'Username must be toggleable like any other column');
    }

    public function test_username_only_selection_keeps_username_and_no_fields(): void
    {
        $result = $this->selection($this->fields(), ['cols' => 'login_username']);

        $this->assertSame([], $result['fields']);
        $this->assertTrue($result['username']);
    }

    /**
     * Definition order wins over the order the browser happened to send, so an export always
     * reads in the report's own sequence however the columns were reordered on screen.
     */
    public function test_column_order_follows_the_definition_not_the_request(): void
    {
        $result = $this->selection($this->fields(), ['cols' => 'photo_path,gender,first_name']);

        $this->assertSame(['first_name', 'gender', 'photo_path'], array_keys($result['fields']));
    }

    /**
     * The regression this test exists for: deciding "which fields" and "keep Username?"
     * separately made unrecognised input fall back to every field but still drop Username —
     * a combination the screen cannot produce.
     */
    public function test_unrecognised_cols_falls_back_to_the_whole_report_including_username(): void
    {
        $result = $this->selection($this->fields(), ['cols' => 'nope,,DROP TABLE']);

        $this->assertCount(3, $result['fields'], 'Unknown keys must not yield an S.No.-only sheet');
        $this->assertTrue($result['username'], 'The fallback must be whole, not partial');
    }

    public function test_unknown_keys_are_dropped_rather_than_trusted(): void
    {
        $result = $this->selection($this->fields(), ['cols' => 'first_name,../../etc/passwd,`gender`']);

        $this->assertSame(['first_name'], array_keys($result['fields']));
    }

    // ── The column contract ────────────────────────────────────────────────

    public function test_every_definition_entry_has_a_label_and_a_type(): void
    {
        foreach (FcDescriptiveDataFieldResolver::definition() as $key => $def) {
            $this->assertArrayHasKey('label', $def, "{$key} needs a label");
            $this->assertNotSame('', trim((string) $def['label']), "{$key} label must not be blank");
            $this->assertArrayHasKey('type', $def, "{$key} needs a type");
        }
    }

    /**
     * Filter types are enumerated in two places — the definition and the options builder.
     * The Service filter shipped with an empty dropdown because a new type was added here and
     * the builder still listed only the types it knew. Adding a type to this list without
     * handling it downstream should fail here first.
     */
    public function test_only_known_filter_types_are_declared(): void
    {
        $known = ['select', 'lookup', 'date_range', 'service'];

        foreach (FcDescriptiveDataFieldResolver::definition() as $key => $def) {
            if (! isset($def['filter'])) {
                continue;
            }
            $this->assertContains(
                $def['filter'],
                $known,
                "{$key} declares filter type '{$def['filter']}' — add it to buildFilterOptions() and to this list"
            );
        }
    }

    /** A non-derived field must name the physical columns it reads, or it cannot be resolved. */
    public function test_non_derived_fields_declare_source_and_columns(): void
    {
        // Read from the resolver's own SOURCE_TABLES rather than a hardcoded list, so adding a
        // source table stays a one-line change there.
        $sourceAliases = array_keys(
            (new \ReflectionClass(FcDescriptiveDataFieldResolver::class))->getConstant('SOURCE_TABLES')
        );

        foreach (FcDescriptiveDataFieldResolver::definition() as $key => $def) {
            if (($def['type'] ?? '') === 'derived') {
                $this->assertArrayHasKey('derived', $def, "{$key} is derived and must name its builder");
                continue;
            }

            // A repeating section is batch-fetched by FcDescriptiveDataChildLoader, not joined,
            // so it names a child table + column instead of a source alias.
            if (($def['type'] ?? '') === 'child') {
                $this->assertNotEmpty($def['child']['table'] ?? null, "{$key} needs a child table");
                $this->assertNotEmpty($def['child']['column'] ?? null, "{$key} needs a child column");
                continue;
            }

            $this->assertArrayHasKey('source', $def, "{$key} needs a source alias");
            $this->assertContains($def['source'], $sourceAliases, "{$key} has an unknown source alias");
            $this->assertNotEmpty($def['columns'] ?? [], "{$key} needs at least one column");
        }
    }

    /** Definition keys become SQL aliases and JSON keys, so they must stay identifier-safe. */
    public function test_definition_keys_are_identifier_safe(): void
    {
        foreach (array_keys(FcDescriptiveDataFieldResolver::definition()) as $key) {
            $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $key, "{$key} is not a safe alias");
        }
    }
}
