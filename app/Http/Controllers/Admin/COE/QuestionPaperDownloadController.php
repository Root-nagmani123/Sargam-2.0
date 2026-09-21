<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Models\QuestionPaperFile;
use App\Services\COE\QuestionPaperAccess;
use App\Services\COE\QuestionPaperAuditService;
use App\Services\COE\QuestionPaperFileService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * COE Examination - the only route by which a question paper file is served.
 *
 * The files sit on a private disk with no public URL, so every read comes
 * through here and is checked against the user's role and assignment first.
 * Serving them as static files would mean anyone holding a path could read a
 * paper before the exam.
 */
class QuestionPaperDownloadController extends Controller
{
    public function __construct(
        protected QuestionPaperFileService $files,
        protected QuestionPaperAuditService $audit
    ) {
    }

    /** Download a file as an attachment. */
    public function download(Request $request, int $fileId): BinaryFileResponse
    {
        $file = $this->authorizedFile($fileId);

        // Who read a confidential paper, and when, leaves no other trace.
        $this->audit->logFileAccess($file, QuestionPaperAuditService::ACTION_FILE_DOWNLOADED);

        return response()->download(
            $this->files->absolutePath($file),
            $file->original_name,
            $this->noStoreHeaders()
        );
    }

    /**
     * Stream a file inline, for the preview pane. Same authorisation as a
     * download - inline display is not a weaker form of access.
     */
    public function preview(Request $request, int $fileId): BinaryFileResponse
    {
        $file = $this->authorizedFile($fileId);

        $this->audit->logFileAccess($file, QuestionPaperAuditService::ACTION_FILE_PREVIEWED);

        // The filename is quoted into the header, so a name carrying a quote or
        // a newline would break out of it. addslashes covers the quote and the
        // name is stripped of anything that could terminate the header line.
        $safeName = str_replace(["\r", "\n", '"'], '', $file->original_name);

        return response()->file(
            $this->files->absolutePath($file),
            $this->noStoreHeaders() + ['Content-Disposition' => 'inline; filename="' . $safeName . '"']
        );
    }

    /**
     * Resolve the file and abort unless the signed-in user may read it.
     *
     * 404 rather than 403 for a file the user may not read: a 403 would confirm
     * that a paper exists for that subject, which is itself worth withholding
     * before an exam.
     */
    protected function authorizedFile(int $fileId): QuestionPaperFile
    {
        $file = QuestionPaperFile::with('questionPaper')->find($fileId);

        if (! $file || ! $file->questionPaper) {
            abort(404);
        }

        if (! QuestionPaperAccess::canDownloadFile($file, $file->questionPaper)) {
            abort(404);
        }

        return $file;
    }

    /**
     * Keep papers out of shared caches and browser history. A cached copy of a
     * question paper on a shared machine outlives the session that fetched it.
     */
    protected function noStoreHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
