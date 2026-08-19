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
    /** Legacy roots that hold documents, as they appear on the public disk. */
    private const DOCUMENT_ROOTS = ['course-repository/attachments', 'course_repository', 'Course Repository'];

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
        try {
            Artisan::call('course-repository:secure-documents');
            Log::info('Course repository documents migrated off the public disk.', [
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            // A failed move must not abort the deploy and leave the schema half-applied —
            // but it must be loud, because the exposure is still open when it happens.
            Log::error('Course repository document migration FAILED — documents remain on the public disk.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally empty. See the class docblock.
    }

    private function denyDirectAccess(string $disk): void
    {
        $body = "# Course Repository documents are served through the application's\n"
              . "# authenticated routes, never directly. See config/course_repository.php.\n"
              . "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
              . "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";

        foreach (self::DOCUMENT_ROOTS as $root) {
            try {
                if (Storage::disk($disk)->exists($root)) {
                    Storage::disk($disk)->put($root . '/.htaccess', $body);
                }
            } catch (\Throwable $e) {
                Log::warning('Could not write deny rule for legacy document root.', [
                    'root' => $root,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
};
