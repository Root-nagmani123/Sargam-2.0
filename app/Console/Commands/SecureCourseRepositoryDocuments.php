<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\CourseRepositoryController;
use App\Models\CourseRepositoryDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Move Course Repository documents off the public disk.
 *
 * Files written before the private-disk change still sit under public/storage, where
 * the web server serves them without a session — so switching the disk for NEW uploads
 * closes the hole going forward but leaves the whole existing backlog readable. This
 * command clears that backlog.
 *
 * Copy-then-verify-then-delete, one file at a time: a half-finished run must never leave
 * a document with no readable copy. The DB rows are not rewritten, because
 * CourseRepositoryController::resolveDocumentLocation() already checks the private disk
 * first and falls back to the legacy one — so a file is found either way, before and
 * after this runs.
 */
class SecureCourseRepositoryDocuments extends Command
{
    protected $signature = 'course-repository:secure-documents
                            {--dry-run : List what would move without touching anything}
                            {--chunk=200 : Rows loaded per batch}';

    protected $description = 'Move Course Repository documents from the public disk to the private one';

    public function handle(): int
    {
        $from = CourseRepositoryController::legacyDocumentDisk();
        $to   = CourseRepositoryController::documentDisk();
        $dry  = (bool) $this->option('dry-run');

        if ($from === $to) {
            $this->error("Legacy and target disks are both '{$from}'. Set COURSE_REPOSITORY_DISK to a private disk first.");

            return self::FAILURE;
        }

        $this->info(($dry ? '[DRY RUN] ' : '') . "Moving documents: '{$from}' -> '{$to}'");

        $moved = $alreadyPrivate = $missing = $failed = 0;

        CourseRepositoryDocument::query()
            ->orderBy('pk')
            ->chunkById((int) $this->option('chunk'), function ($documents) use (
                $from, $to, $dry, &$moved, &$alreadyPrivate, &$missing, &$failed
            ) {
                foreach ($documents as $document) {
                    $path = $this->relativePath($document, $to);
                    if ($path !== null) {
                        $alreadyPrivate++;
                        continue;
                    }

                    $path = $this->relativePath($document, $from);
                    if ($path === null) {
                        $missing++;
                        continue;
                    }

                    if ($dry) {
                        $this->line("  would move: {$path}");
                        $moved++;
                        continue;
                    }

                    try {
                        $stream = Storage::disk($from)->readStream($path);
                        if ($stream === false || $stream === null) {
                            $failed++;
                            $this->warn("  unreadable, left in place: {$path}");
                            continue;
                        }

                        Storage::disk($to)->writeStream($path, $stream);
                        if (is_resource($stream)) {
                            fclose($stream);
                        }

                        // Verify the copy landed and is the same size before removing the
                        // only other copy. Size is a weak check, but it catches a
                        // truncated or zero-byte write, which is the realistic failure.
                        $srcSize = Storage::disk($from)->size($path);
                        $dstSize = Storage::disk($to)->exists($path) ? Storage::disk($to)->size($path) : -1;

                        if ($dstSize !== $srcSize) {
                            $failed++;
                            $this->warn("  copy mismatch ({$srcSize} vs {$dstSize}), left in place: {$path}");
                            continue;
                        }

                        Storage::disk($from)->delete($path);
                        $moved++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->warn("  failed, left in place: {$path} — " . $e->getMessage());
                    }
                }
            });

        $this->newLine();
        $this->table(
            ['Moved', 'Already private', 'File missing', 'Failed'],
            [[$moved, $alreadyPrivate, $missing, $failed]]
        );

        if ($missing > 0) {
            $this->warn("{$missing} document row(s) have no file on either disk. Pre-existing data gaps, not caused by this run.");
        }

        if ($failed > 0) {
            $this->error("{$failed} file(s) could not be moved and are STILL on the public disk. Re-run after investigating.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** Reuse the controller's resolution rules so this cannot drift from how reads work. */
    private function relativePath(CourseRepositoryDocument $document, string $disk): ?string
    {
        $method = new \ReflectionMethod(CourseRepositoryController::class, 'resolveDocumentRelativePath');
        $method->setAccessible(true);

        return $method->invoke(app(CourseRepositoryController::class), $document, $disk);
    }
}
