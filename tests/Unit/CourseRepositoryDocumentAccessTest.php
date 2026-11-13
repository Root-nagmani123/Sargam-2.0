<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\CourseRepositoryController;
use App\Models\CourseRepositoryDocument;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Covers the Course Repository document access rules.
 *
 * Deliberately unit-level and database-free. The full suite in this repository cannot
 * complete — a dev dependency in vendor/ requires a newer PHP than the runtime, so the
 * run dies before printing a summary — and the application's own tables are legacy and
 * have no factories. Tests that needed a seeded database therefore could not be made to
 * pass here, and a test that cannot run is worth less than no test at all.
 *
 * What these assert is the part that is verifiable without one: the storage disk is
 * private, the read routes carry the middleware that bounds them, and the URL the
 * application hands to browsers points at the authenticated route rather than /storage.
 *
 * Run with:  php vendor/bin/phpunit --filter=CourseRepositoryDocumentAccess
 */
class CourseRepositoryDocumentAccessTest extends TestCase
{
    /** Documents must never be written to a web-served disk. */
    public function test_documents_are_stored_on_a_private_disk(): void
    {
        $disk = CourseRepositoryController::documentDisk();

        $this->assertNotSame('public', $disk, 'Course Repository documents must not use the public disk.');

        $visibility = config("filesystems.disks.{$disk}.visibility");
        $this->assertNotSame(
            'public',
            $visibility,
            "Disk '{$disk}' is configured with public visibility, so its files are web-reachable."
        );

        $root = config("filesystems.disks.{$disk}.root");
        $this->assertStringNotContainsString(
            'app' . DIRECTORY_SEPARATOR . 'public',
            str_replace('/', DIRECTORY_SEPARATOR, (string) $root),
            "Disk '{$disk}' is rooted inside storage/app/public, which is exposed via the storage symlink."
        );
    }

    /** The legacy fallback must stay distinct from the target, or the move is a no-op. */
    public function test_legacy_disk_is_not_the_same_as_the_private_disk(): void
    {
        $this->assertNotSame(
            CourseRepositoryController::documentDisk(),
            CourseRepositoryController::legacyDocumentDisk(),
            'Legacy and target disks are identical; the migration would move nothing.'
        );
    }

    /**
     * Both read actions take a sequential primary key, so the rate limit is what stops
     * the corpus being walked. If someone removes it, this fails.
     */
    public function test_document_read_routes_are_authenticated_and_throttled(): void
    {
        foreach (['course-repository.document.stream', 'course-repository.document.download'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} is not registered.");

            $middleware = $route->gatherMiddleware();

            $this->assertContains('auth', $middleware, "Route {$name} is not behind auth.");

            $throttled = collect($middleware)->contains(
                fn ($m) => is_string($m) && str_starts_with($m, 'throttle:')
            );
            $this->assertTrue($throttled, "Route {$name} has no rate limit, so the id range can be walked.");
        }
    }

    /** The URL handed to browsers must be the controller route, never a /storage path. */
    public function test_public_file_url_points_at_the_authenticated_route(): void
    {
        $document = new CourseRepositoryDocument();
        $document->pk = 4242;
        $document->full_path = 'course-repository/attachments/example_1755594000.pdf';

        $url = $document->public_file_url;

        $this->assertNotNull($url, 'A document with a stored path should still yield a URL.');
        $this->assertStringNotContainsString('/storage/', $url, 'The URL bypasses the application.');
        $this->assertStringContainsString('/course-repository/document/4242/stream', $url);
    }

    /** A document with no stored path has nothing to link to. */
    public function test_public_file_url_is_null_without_a_stored_path(): void
    {
        $document = new CourseRepositoryDocument();
        $document->pk = 7;

        $this->assertNull($document->public_file_url);
    }

    /**
     * findReadableDocument() must filter soft-deleted rows. Asserted on the query the
     * method builds, so it needs no database: deleteDocument() only sets del_type = 0
     * and never removes the file, so without this clause a "deleted" document stays
     * downloadable.
     */
    public function test_read_lookup_excludes_soft_deleted_documents(): void
    {
        $method = new ReflectionMethod(CourseRepositoryController::class, 'findReadableDocument');
        $method->setAccessible(true);

        $source = file_get_contents((new \ReflectionClass(CourseRepositoryController::class))->getFileName());
        $start = $method->getStartLine();
        $body = implode("\n", array_slice(explode("\n", $source), $start - 1, $method->getEndLine() - $start + 1));

        $this->assertStringContainsString(
            "'del_type', 1",
            $body,
            'findReadableDocument() must exclude soft-deleted documents.'
        );
    }

    /**
     * The relocation must report failure, not throw, when the source has vanished.
     *
     * This is the half of the concurrency case that IS reachable from a unit test. When
     * two readers hit the same not-yet-relocated document, the winner deletes the public
     * copy while the loser is still mid-move; the loser's verification then runs against
     * a file that is gone. Flysystem's size() throws rather than returning null, so
     * without the exists() guards this surfaced as an exception on a read path.
     *
     * The other half — that the caller then prefers the private copy rather than falling
     * back to a legacy path that no longer exists — is NOT asserted here. Reaching that
     * branch needs the two disks to change state between the resolver's first check and
     * its relocation attempt, which a single-threaded test cannot stage without mocking
     * the Storage facade. It is covered by the exists() checks in resolveDocumentLocation()
     * and by reading, not by execution. Recorded rather than papered over.
     */
    public function test_relocation_reports_failure_instead_of_throwing_when_the_source_is_gone(): void
    {
        $method = new ReflectionMethod(CourseRepositoryController::class, 'relocateLegacyDocument');
        $method->setAccessible(true);

        $result = $method->invoke(
            app(CourseRepositoryController::class),
            CourseRepositoryController::legacyDocumentDisk(),
            CourseRepositoryController::documentDisk(),
            'course-repository/attachments/definitely_not_present_'.__LINE__.'.pdf'
        );

        $this->assertFalse($result, 'Relocation of a missing source must return false rather than throw.');
    }

    /** A document whose file is on neither disk resolves to nothing, not to a dead path. */
    public function test_resolver_returns_null_when_the_file_is_on_neither_disk(): void
    {
        $document = new CourseRepositoryDocument();
        $document->pk = 9912;
        $document->full_path = 'course-repository/attachments/definitely_not_present_9912.pdf';

        $method = new ReflectionMethod(CourseRepositoryController::class, 'resolveDocumentLocation');
        $method->setAccessible(true);

        $this->assertNull(
            $method->invoke(app(CourseRepositoryController::class), $document),
            'Resolver handed back a location for a file that exists nowhere.'
        );
    }
}