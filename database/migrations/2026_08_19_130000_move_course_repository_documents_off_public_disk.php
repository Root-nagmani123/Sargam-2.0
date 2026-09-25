<?php

use App\Http\Controllers\Admin\CourseRepositoryController;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Move Course Repository documents off the public disk as part of the deploy.
 *
 * The application now writes new documents to a private disk and serves every read
 * through an authenticated controller action. That alone does nothing for the files
 * already on the public disk: those sit under public/storage, which the web server
 * resolves before Laravel loads, so they stay readable by anyone who can reach the host.
 *
 * The move was originally only a console command, which meant the exposure closed only
 * if somebody remembered to run it. Running it here ties it to `php artisan migrate`,
 * which deployment already does — so the fix cannot be silently skipped.
 *
 * Deliberately NOT reversible in the file sense: down() restores the schema-level
 * nothing this migration changed, but it does not copy documents back onto a public
 * disk. Undoing a security fix by rollback would be a surprising thing for a migration
 * to do, and the application reads from the private disk first either way.
 */
return new class extends Migration
{
    /**
     * Legacy roots that hold documents, as they appear on the public disk.
     *
     * Documents do NOT all live under an "attachments" folder — the bulk sit directly
     * under the repository name, e.g.
     * "course-repository/Central Course Repository of LBSNAA/MCTP/…/file.pdf". Denying
     * only course-repository/attachments would therefore have missed almost all of them.
     */
    private const DOCUMENT_ROOTS = ['course-repository', 'course_repository', 'Course Repository'];

    /**
     * Re-permitted inside the denied roots: category thumbnails are decorative images
     * rendered with asset() on the listing pages and are meant to stay public. Denying
     * their parent without this would blank every repository card.
     */
    private const PUBLIC_EXCEPTIONS = ['course-repository/categories'];

    public function up(): void
    {
        $legacy = CourseRepositoryController::legacyDocumentDisk();
        $target = CourseRepositoryController::documentDisk();

        if ($legacy === $target) {
            // Nothing to move, and no exposure to close.
            return;
        }

        // Belt-and-braces first, because it is cheap and instant: deny direct web access
        // to the legacy document roots. Only effective on Apache with AllowOverride on —
        // it is a second line, not the fix. The move below is what actually works
        // regardless of web server.
        $this->denyDirectAccess($legacy);

        // The command owns the copy/verify/delete logic; calling it keeps one
        // implementation rather than a second copy that can drift.
        //
        // A failed move FAILS the migration (review finding F-024). Swallowing it let
        // Laravel record this migration as run - Migrator::runUp() logs it once up()
        // returns - so `php artisan migrate` never tried again and the exposure stayed
        // open behind a green deploy. The command also reports per-file failures by
        // exit code rather than by exception, and that code used to be ignored too.
        // Failing is safe here: this migration changes no schema, the deny rule above
        // is rewritten idempotently, and the command skips documents already on the
        // private disk, so the next `migrate` simply resumes the move.
        try {
            $exitCode = Artisan::call('course-repository:secure-documents');
        } catch (Throwable $e) {
            Log::error('Course repository document migration FAILED — documents remain on the public disk.', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            Log::error('Course repository document migration FAILED — documents remain on the public disk.', [
                'exit_code' => $exitCode,
                'output' => $output,
            ]);

            throw new RuntimeException(
                'course-repository:secure-documents exited with code '.$exitCode
                .' - some documents are still on the public disk. Fix the cause and re-run'
                .' `php artisan migrate`; the move resumes where it stopped.'
            );
        }

        Log::info('Course repository documents migrated off the public disk.', [
            'output' => $output,
        ]);
    }

    public function down(): void
    {
        // Intentionally empty. See the class docblock.
    }

    private function denyDirectAccess(string $disk): void
    {
        $body = "# Course Repository documents are served through the application's\n"
              ."# authenticated routes, never directly. See config/course_repository.php.\n"
              ."<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
              ."<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";

        $allow = "# Category thumbnails are public by design — see the migration that wrote this.\n"
               ."<IfModule mod_authz_core.c>\n    Require all granted\n</IfModule>\n"
               ."<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Allow from all\n</IfModule>\n";

        foreach (self::DOCUMENT_ROOTS as $root) {
            try {
                if (Storage::disk($disk)->exists($root)) {
                    Storage::disk($disk)->put($root.'/.htaccess', $body);
                }
            } catch (Throwable $e) {
                Log::warning('Could not write deny rule for legacy document root.', [
                    'root' => $root,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Written after the denies, so a nested allow always wins for the thumbnails.
        foreach (self::PUBLIC_EXCEPTIONS as $root) {
            try {
                if (Storage::disk($disk)->exists($root)) {
                    Storage::disk($disk)->put($root.'/.htaccess', $allow);
                }
            } catch (Throwable $e) {
                Log::warning('Could not re-permit public exception folder.', [
                    'root' => $root,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
};
