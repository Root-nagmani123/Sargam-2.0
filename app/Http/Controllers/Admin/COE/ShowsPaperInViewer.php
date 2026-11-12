<?php

namespace App\Http\Controllers\Admin\COE;

use App\Services\COE\QuestionPaperStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * See Attachment / View … Paper on the COE question paper screens: the paper
 * goes to the read-only viewer (public/js/coe-paper-viewer.js), never as a file.
 * The viewer and the paper editor show the LBSNAA watermark over the paper
 * (coe-admin.css); who opened it is in the question paper log.
 */
trait ShowsPaperInViewer
{
    /**
     * The paper for the viewer. Only the viewer's script may ask (a browser opening the address gets a
     * 404), and it is kept out of every cache: the paper is confidential.
     */
    protected function paperViewer(Request $request, ?string $path, string $name): JsonResponse
    {
        abort_unless($request->expectsJson(), 404);

        return response()->json(app(QuestionPaperStorage::class)->forViewer($path, $name))->withHeaders([
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

}
