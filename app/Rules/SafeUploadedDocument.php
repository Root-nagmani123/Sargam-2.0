<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

/**
 * Content-level validation for admin/candidate document uploads (CWE-434).
 *
 * Laravel's `mimes:` rule already maps the *guessed* MIME back to an extension,
 * which stops a plain renamed web shell. This rule closes the rest of the gap
 * that a VAPT retest looks for:
 *
 *  1. MAGIC BYTES — the first bytes of the file must actually be the signature
 *     of an allowed type. A file whose content-type is guessed as application/pdf
 *     purely from a `%PDF` fragment further in the body is rejected.
 *  2. STRICT MIME ALLOWLIST — the detected MIME must be one this type is allowed
 *     to have, so `mimes:` and the signature have to agree with each other.
 *  3. DOUBLE / DANGEROUS EXTENSION — the submitted filename is rejected if ANY
 *     of its dot-segments is executable (`report.php.pdf`, `a.phtml.jpg`). The
 *     stored name is server-derived, so this is defence in depth for the
 *     original name we keep for display.
 *  4. SCRIPT-TAG POLYGLOT — an opening PHP tag inside the file header window is
 *     rejected, catching files crafted to be valid-image-and-valid-PHP at once.
 *  5. INI-SIZE FAILURE — when PHP itself drops the upload (file larger than
 *     upload_max_filesize) the user gets a real message instead of the generic
 *     "must be a file" that made the earlier retest look like a broken form.
 *
 * Execution is separately blocked on disk by storage/app/public/.htaccess; this
 * rule is the code-level half so the protection does not depend on web-server
 * configuration.
 */
class SafeUploadedDocument implements Rule
{
    /**
     * Allowed types: canonical extension => [magic-byte prefixes, accepted MIME types].
     *
     * Signatures are byte prefixes at offset 0. `doc` is the OLE2 compound-file
     * header and `docx` is a ZIP container (both are what Word actually writes).
     */
    private const TYPES = [
        'pdf' => [
            'magic' => ["%PDF-"],
            'mimes' => ['application/pdf'],
        ],
        'jpg' => [
            'magic' => ["\xFF\xD8\xFF"],
            'mimes' => ['image/jpeg'],
        ],
        'png' => [
            'magic' => ["\x89PNG\r\n\x1A\n"],
            'mimes' => ['image/png'],
        ],
        'doc' => [
            'magic' => ["\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"],
            'mimes' => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2'],
        ],
        'docx' => [
            'magic' => ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"],
            'mimes' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
            ],
        ],
        'gif' => [
            'magic' => ['GIF87a', 'GIF89a'],
            'mimes' => ['image/gif'],
        ],
        'webp' => [
            // RIFF container; the MIME check below is what separates WebP from
            // the other RIFF formats (WAV/AVI), which is why both must agree.
            'magic' => ['RIFF'],
            'mimes' => ['image/webp'],
        ],
    ];

    /**
     * Extensions that must never appear anywhere in the submitted filename.
     * Mirrors the FilesMatch list in storage/app/public/.htaccess.
     */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'php2', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8',
        'phtml', 'phps', 'pht', 'phar', 'inc',
        'cgi', 'pl', 'py', 'rb', 'jsp', 'jspx', 'asp', 'aspx', 'ashx', 'asmx',
        'sh', 'bash', 'exe', 'com', 'bat', 'cmd', 'msi', 'dll', 'so',
        'htaccess', 'htpasswd', 'jar', 'war', 'svg', 'html', 'htm', 'xhtml', 'js',
    ];

    /**
     * Bytes of the file header scanned for an opening PHP tag. Deliberately
     * small: a polyglot has to put its payload at the front to be executed,
     * while legitimate PDFs/Office files hold only binary structure here, so
     * this window catches the attack without false-positiving on real documents.
     */
    private const HEADER_SCAN_BYTES = 512;

    /**
     * Byte markers that make an otherwise-genuine document dangerous: a real PDF
     * that runs JavaScript / launches a program / embeds a file on open, or a
     * .docx whose ZIP carries a macro project. A file like report.pdf that fires
     * app.alert() or /Launch is a CWE-434 finding even though it passes every
     * signature and MIME check — this is what a VAPT "malicious PDF upload" test
     * plants (the tester's xss1.pdf carried /JavaScript + /JS app.alert()).
     *
     * Keyed by canonical extension so only the relevant format is scanned.
     *
     * @var array<string, list<string>>
     */
    private const ACTIVE_CONTENT_MARKERS = [
        'pdf' => ['/JavaScript', '/JS', '/Launch', '/EmbeddedFile', '/RichMedia', '/XFA'],
        // A genuine .docx never contains a VBA project; its presence means the file
        // is really a macro-enabled document renamed/relabelled as .docx.
        'docx' => ['vbaProject', 'vbaData', 'macroEnabled'],
    ];

    /** Cap on bytes read for the active-content scan (uploads are already size-limited). */
    private const ACTIVE_SCAN_MAX_BYTES = 26214400; // 25 MB

    /** Cap on FlateDecode streams inflated per PDF, so a crafted file can't stall the scan. */
    private const ACTIVE_SCAN_MAX_STREAMS = 300;

    /** @var list<string> canonical extensions this instance accepts and can verify */
    private array $allowed;

    /**
     * Allowed extensions we hold no signature for (an admin-configured type like
     * `txt` or `xls`). Their content cannot be verified, so they are exempt from
     * the signature check — the filename and script-tag checks still apply, and
     * the stored extension is still server-derived. Without this a form field
     * configured for an unlisted type would reject every upload.
     *
     * @var list<string>
     */
    private array $unverifiable;

    private string $message = 'The uploaded file is not a valid document.';

    /**
     * @param list<string> $allowed canonical extensions, e.g. ['pdf','jpg','png']
     */
    public function __construct(array $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'])
    {
        // 'jpeg' is an alias of 'jpg' — normalise so callers can pass either.
        $normalised = array_values(array_unique(array_map(
            fn ($ext) => strtolower(trim($ext)) === 'jpeg' ? 'jpg' : strtolower(trim($ext)),
            $allowed
        )));

        $this->allowed      = array_values(array_filter($normalised, fn ($e) => isset(self::TYPES[$e])));
        $this->unverifiable = array_values(array_filter($normalised, fn ($e) => ! isset(self::TYPES[$e])));

        if ($this->allowed === [] && $this->unverifiable === []) {
            $this->allowed = ['pdf', 'jpg', 'png', 'doc', 'docx'];
        }
    }

    /**
     * Largest upload PHP will actually accept, in kilobytes, never above the
     * caller's own ceiling.
     *
     * The `max:` rule must not promise more than php.ini allows: a file between
     * upload_max_filesize and the rule's limit is discarded by PHP before
     * Laravel sees it, which surfaces as a confusing failure (or, past
     * post_max_size, an empty request body) rather than a size error.
     */
    public static function maxKilobytes(int $desiredKb): int
    {
        $limits = array_filter([
            self::iniBytes('upload_max_filesize'),
            self::iniBytes('post_max_size'),
        ]);

        if ($limits === []) {
            return $desiredKb;
        }

        // Leave headroom for the other multipart fields inside post_max_size.
        $phpKb = (int) floor((min($limits) * 0.95) / 1024);

        return max(1, min($desiredKb, $phpKb));
    }

    /**
     * Human-readable form of maxKilobytes() for labels/help text.
     */
    public static function maxLabel(int $desiredKb): string
    {
        $kb = self::maxKilobytes($desiredKb);

        return $kb >= 1024
            ? round($kb / 1024, 1) . ' MB'
            : $kb . ' KB';
    }

    /**
     * The canonical extension implied by the file's own CONTENT, or null when the
     * content matches no allowed type.
     *
     * Callers use this to name the stored file, so the extension on disk is
     * decided by the signature we verified rather than by anything the client
     * sent — `store()`'s own guess comes from the MIME guesser and can widen to
     * a container type (a .docx guessing as .zip, for instance).
     *
     * @param list<string> $allowed
     */
    public static function canonicalExtension(UploadedFile $file, array $allowed = ['pdf', 'jpg', 'png', 'doc', 'docx']): ?string
    {
        $rule = new self($allowed);

        return $rule->matchSignature($rule->readHeader($file));
    }

    /**
     * The client's filename reduced to something safe to STORE and DISPLAY.
     * It never reaches the filesystem (the stored name is server-generated); it
     * is kept so admins recognise the document, and sanitised here so it cannot
     * carry a path, a control character, or a script extension into a page or a
     * Content-Disposition header.
     */
    public static function safeDisplayName(string $name, string $fallback = 'document'): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]+/', '', $name) ?? '';
        $name = preg_replace('/[^A-Za-z0-9 ._()\[\]-]/', '_', $name) ?? '';

        // Defuse any executable dot-segment that survived (".php." -> "_php_"),
        // so the label is inert even if a caller ever echoes it into a
        // Content-Disposition filename rather than into escaped HTML.
        $segments = explode('.', $name);
        foreach ($segments as $i => $segment) {
            if (in_array(strtolower(trim($segment)), self::DANGEROUS_EXTENSIONS, true)) {
                $segments[$i] = '_' . $segment . '_';
            }
        }
        $name = implode('.', $segments);
        $name = str_replace('._', '_', $name);

        $name = trim(preg_replace('/_{2,}/', '_', $name) ?? '', ' _.');

        if ($name === '') {
            return $fallback;
        }

        return mb_substr($name, 0, 150);
    }

    /**
     * @param  string  $attribute
     * @param  mixed   $value
     */
    public function passes($attribute, $value): bool
    {
        if (! $value instanceof UploadedFile) {
            $this->message = 'No file was received. Please choose a file and try again.';

            return false;
        }

        // PHP rejected the upload itself (usually larger than upload_max_filesize).
        if (! $value->isValid()) {
            $this->message = $this->uploadErrorMessage($value->getError());

            return false;
        }

        if (! $this->filenameIsSafe($value->getClientOriginalName())) {
            $this->message = 'That filename is not allowed. Rename the file so it does not contain a script extension (e.g. .php, .html, .exe).';

            return false;
        }

        $header = $this->readHeader($value);

        if ($header === '') {
            $this->message = 'The uploaded file is empty or could not be read.';

            return false;
        }

        // The script-tag check runs for EVERY type, verifiable or not — it is the
        // one test that does not depend on knowing the format's signature.
        if ($this->containsPhpTag($header)) {
            $this->message = 'The file contains embedded script code and was rejected.';

            return false;
        }

        $matched = $this->matchSignature($header);

        if ($matched === null) {
            // No signature matched. Only acceptable when this field also allows a
            // type we cannot verify; otherwise the content is simply not what it
            // claims to be.
            if ($this->unverifiable !== []) {
                return true;
            }

            $this->message = 'The file contents are not a valid ' . $this->allowedLabel() . ' file, whatever the file name says.';

            return false;
        }

        // Detected MIME must agree with the signature we matched.
        $mime = (string) $value->getMimeType();

        if (! in_array($mime, self::TYPES[$matched]['mimes'], true)) {
            $this->message = 'The file type could not be verified. Please upload an unmodified ' . $this->allowedLabel() . ' file.';

            return false;
        }

        // The bytes are a genuine PDF/DOCX — now reject one carrying embedded
        // JavaScript, auto-run actions, a launch action, or a macro project.
        if ($this->hasActiveContent($value, $matched)) {
            $this->message = 'This ' . strtoupper($matched)
                . ' contains embedded scripts, macros, or auto-run actions and was rejected. '
                . 'Please upload a plain document — if it was a fillable form, print or "Save as" a flat '
                . strtoupper($matched) . ' and try again.';

            return false;
        }

        return true;
    }

    public function message(): string
    {
        return $this->message;
    }

    /**
     * Every dot-segment of the submitted name must be free of executable
     * extensions — not just the last one, which is what makes
     * `payload.php.pdf` fail here.
     */
    private function filenameIsSafe(string $name): bool
    {
        // basename() first: a name like "../../x.pdf" must not be trusted either.
        $name = basename(str_replace('\\', '/', $name));

        if ($name === '' || preg_match('/[\x00-\x1F\x7F]/', $name)) {
            return false;
        }

        foreach (explode('.', strtolower($name)) as $segment) {
            if (in_array(trim($segment), self::DANGEROUS_EXTENSIONS, true)) {
                return false;
            }
        }

        return true;
    }

    private function readHeader(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_readable($path)) {
            return '';
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        $header = (string) fread($handle, self::HEADER_SCAN_BYTES);
        fclose($handle);

        return $header;
    }

    /**
     * True when a verified PDF/DOCX carries embedded active content (JavaScript,
     * launch/auto actions, embedded files, or a macro project). Scans the raw
     * bytes first, then — for PDFs — inflates FlateDecode streams so a payload
     * hidden inside a compressed object stream is still caught.
     */
    private function hasActiveContent(UploadedFile $file, string $ext): bool
    {
        $markers = self::ACTIVE_CONTENT_MARKERS[$ext] ?? null;
        if ($markers === null) {
            return false;
        }

        $bytes = $this->readAll($file);
        if ($bytes === '') {
            return false;
        }

        if ($this->bytesContainAny($bytes, $markers)) {
            return true;
        }

        if ($ext === 'pdf') {
            foreach ($this->inflatedPdfStreams($bytes) as $chunk) {
                if ($this->bytesContainAny($chunk, $markers)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param list<string> $markers
     */
    private function bytesContainAny(string $haystack, array $markers): bool
    {
        foreach ($markers as $marker) {
            if (stripos($haystack, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    private function readAll(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_readable($path)) {
            return '';
        }

        return (string) @file_get_contents($path, false, null, 0, self::ACTIVE_SCAN_MAX_BYTES);
    }

    /**
     * Yield the inflated body of each `stream … endstream` block that is
     * zlib/raw-deflate compressed. Non-compressed or undecodable streams are
     * skipped. Bounded by ACTIVE_SCAN_MAX_STREAMS so a file packed with tiny
     * streams cannot turn the scan into a CPU sink.
     *
     * @return \Generator<int, string>
     */
    private function inflatedPdfStreams(string $bytes): \Generator
    {
        if (! preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $bytes, $matches)) {
            return;
        }

        foreach (array_slice($matches[1], 0, self::ACTIVE_SCAN_MAX_STREAMS) as $raw) {
            $out = @gzuncompress($raw);
            if ($out === false) {
                $out = @gzinflate($raw);
            }
            if (is_string($out) && $out !== '') {
                yield $out;
            }
        }
    }

    /**
     * @return string|null canonical extension whose signature matched, if allowed
     */
    private function matchSignature(string $header): ?string
    {
        foreach ($this->allowed as $ext) {
            foreach (self::TYPES[$ext]['magic'] ?? [] as $magic) {
                if (strncmp($header, $magic, strlen($magic)) === 0) {
                    return $ext;
                }
            }
        }

        return null;
    }

    private function containsPhpTag(string $header): bool
    {
        return stripos($header, '<?php') !== false
            || stripos($header, '<?=') !== false
            || stripos($header, '<script language="php"') !== false;
    }

    private function allowedLabel(): string
    {
        return strtoupper(implode('/', array_merge($this->allowed, $this->unverifiable)));
    }

    private function uploadErrorMessage(int $error): string
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'The file is larger than the server allows (maximum '
                    . self::maxLabel(PHP_INT_MAX) . '). Please upload a smaller file.';
            case UPLOAD_ERR_PARTIAL:
                return 'The upload was interrupted. Please try again.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was received. Please choose a file and try again.';
            default:
                return 'The file could not be uploaded. Please try again.';
        }
    }

    /**
     * php.ini shorthand ("2M", "512K", "1G") to bytes. 0/-1 means "no limit".
     */
    private static function iniBytes(string $key): ?int
    {
        $raw = trim((string) ini_get($key));

        if ($raw === '' || $raw === '0' || $raw === '-1') {
            return null;
        }

        $unit  = strtolower(substr($raw, -1));
        $value = (int) $raw;

        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return $value;
        }
    }
}
