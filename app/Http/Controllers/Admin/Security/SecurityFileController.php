<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use App\Support\SecurityFiles;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Serves security-module files (ID card photos, Aadhaar/FIR/receipts, vehicle documents)
 * from the private disk. The `f` parameter is an encrypted ref minted by
 * SecurityFiles::url() while rendering a page the viewer was allowed to see, so it cannot
 * be forged or enumerated; SecurityFiles::parseRef() confines it to the security disk and
 * the pre-layout public-disk prefixes.
 */
class SecurityFileController extends Controller
{
    private const INLINE_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
    ];

    public function show(Request $request)
    {
        try {
            $ref = Crypt::decryptString((string) $request->query('f', ''));
        } catch (DecryptException) {
            abort(404);
        }

        $absolute = SecurityFiles::refToAbsolute($ref);
        if ($absolute === null || ! is_file($absolute)) {
            abort(404);
        }

        $name = basename(str_replace('\\', '/', $absolute));
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ];

        // Images and PDFs render inline, typed by extension rather than sniffed content, so an
        // HTML file renamed .jpg is never served as text/html in our origin. Anything else downloads.
        if (isset(self::INLINE_TYPES[$ext]) && in_array($ext, config('security_files.inline_extensions', []), true)) {
            return response()->file($absolute, $headers + ['Content-Type' => self::INLINE_TYPES[$ext]]);
        }

        return response()->download($absolute, $name, $headers + ['Content-Type' => 'application/octet-stream']);
    }
}
