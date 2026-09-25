<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The document-move migration must FAIL when the move fails.
 *
 * It used to catch the failure, log it and return - and Laravel records a migration
 * as run as soon as up() returns, so `php artisan migrate` never retried and every
 * document stayed world-readable behind a green deploy. The command also reports
 * per-file failures through its exit code, which the migration ignored.
 *
 * The command is mocked: what is under test is how the migration reacts to it, not
 * the copy itself. Both disks are faked, so no file and no database is touched.
 */
class CourseRepositoryDocumentMoveMigrationTest extends TestCase
{
    private const MIGRATION = 'database/migrations/2026_08_19_130000_move_course_repository_documents_off_public_disk.php';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'course_repository.legacy_disk' => 'cr_legacy_probe',
            'course_repository.disk' => 'cr_private_probe',
        ]);
        Storage::fake('cr_legacy_probe');
        Storage::fake('cr_private_probe');
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION);
    }

    public function test_a_command_that_exits_non_zero_fails_the_migration(): void
    {
        Artisan::shouldReceive('call')->once()->with('course-repository:secure-documents')->andReturn(1);
        Artisan::shouldReceive('output')->andReturn('3 file(s) could not be moved');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exited with code 1');

        $this->migration()->up();
    }

    public function test_a_command_that_throws_fails_the_migration(): void
    {
        Artisan::shouldReceive('call')->once()->andThrow(new \RuntimeException('private disk not writable'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('private disk not writable');

        $this->migration()->up();
    }

    /** Control: a clean move must still let the migration complete. */
    public function test_a_clean_move_completes(): void
    {
        Artisan::shouldReceive('call')->once()->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('Moved 3');

        $this->migration()->up();

        $this->addToAssertionCount(1);
    }
}
