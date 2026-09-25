<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The unique-index migration's duplicate pre-check, executed.
 *
 * The pre-check decides whether up() skips the index or issues ALTER TABLE.
 * Anything it fails to count reaches the ALTER and aborts the deploy with a
 * 1062, which is the outcome the skip exists to prevent. A UNIQUE index exempts
 * NULL only, so the empty string has to be counted like any other value.
 *
 * Runs the migration's own duplicates() against an in-memory SQLite table, so
 * it needs no application database and never touches one.
 */
class FacultyExpertiseUniqueIndexMigrationTest extends TestCase
{
    private const MIGRATION = 'database/migrations/2026_08_19_000000_add_unique_index_to_faculty_expertise_master.php';

    private ?string $previousConnection = null;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.fem_probe' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]]);

        $this->previousConnection = DB::getDefaultConnection();
        DB::setDefaultConnection('fem_probe');

        Schema::create('faculty_expertise_master', function ($table) {
            $table->increments('pk');
            $table->string('expertise_name', 50)->nullable();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('fem_probe');
        DB::setDefaultConnection($this->previousConnection);

        parent::tearDown();
    }

    public function test_repeated_empty_strings_are_counted_as_duplicates(): void
    {
        $this->seedNames(['', '', 'Public Policy']);

        $this->assertSame(['' => 2], $this->duplicates());
    }

    public function test_repeated_nulls_are_not_duplicates(): void
    {
        $this->seedNames([null, null, 'Public Policy']);

        $this->assertSame([], $this->duplicates());
    }

    public function test_repeated_names_are_duplicates(): void
    {
        $this->seedNames(['Public Policy', 'Public Policy', 'Economics', null, null]);

        $this->assertSame(['Public Policy' => 2], $this->duplicates());
    }

    /** The pre-check must agree with the index it guards: whatever it passes, the index accepts. */
    public function test_a_clean_pre_check_means_the_unique_index_can_be_created(): void
    {
        $this->seedNames(['', 'Economics', null, null]);

        $this->assertSame([], $this->duplicates());

        DB::statement('CREATE UNIQUE INDEX fem_probe_unique ON faculty_expertise_master (expertise_name)');

        $this->assertTrue(true, 'the index was created without a constraint failure');
    }

    private function seedNames(array $names): void
    {
        foreach ($names as $name) {
            DB::table('faculty_expertise_master')->insert(['expertise_name' => $name]);
        }
    }

    /** @return array<string, int> value => occurrences, as up() sees them */
    private function duplicates(): array
    {
        $migration = require base_path(self::MIGRATION);

        $method = new \ReflectionMethod($migration, 'duplicates');
        $method->setAccessible(true);

        return $method->invoke($migration)
            ->mapWithKeys(fn ($row) => [(string) $row->value => (int) $row->occurrences])
            ->all();
    }
}
