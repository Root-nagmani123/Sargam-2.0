<?php

namespace App\Http\Controllers\Admin\COE;

use App\Services\COE\QuestionPaperStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * See Attachment / View … Paper on the COE question paper screens: the paper
 * goes to the read-only viewer (public/js/coe-paper-viewer.js), never as a file.
 * The paper editor pages stamp the same watermark (paperStamp()).
 */
trait ShowsPaperInViewer
{
    /**
     * The paper plus who is looking at it - the viewer stamps that across the
     * page, so a screenshot or a phone photo still shows whose copy it was.
     * Only the viewer's script may ask (a browser opening the address gets a
     * 404), and it is kept out of every cache: the paper is confidential.
     */
    protected function paperViewer(Request $request, ?string $path, string $name): JsonResponse
    {
        abort_unless($request->expectsJson(), 404);

        return response()->json(app(QuestionPaperStorage::class)->forViewer($path, $name) + [
            'watermark' => $this->paperStamp($request),
        ])->withHeaders([
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** "Name · ID 12 · 29-10-2026 10:15" — stamped over the viewer and the paper editor. */
    protected function paperStamp(Request $request): string
    {
        $user = $request->user();
        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->name ?? 'User');

        return $name.' · ID '.$user->getAuthIdentifier().' · '.now()->format('d-m-Y H:i');
    }
}
