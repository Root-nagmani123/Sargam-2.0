<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

/**
 * Rejects a "double / stacked extension" upload — a name carrying more than one
 * recognised file extension, e.g. "Document1.txt.pdf.pdf" or "scan.jpg.pdf".
 *
 * This complements App\Rules\SafeUploadedDocument (which blocks a *script*
 * extension anywhere in the name and verifies the bytes): a VAPT "double
 * extension" finding also covers benign stacks like .txt.pdf, which this rule
 * catches. Names with incidental dots in the base ("Dr. Smith CV.pdf",
 * "invoice.2024.pdf") have only one real extension and pass.
 */
class SingleFileExtension implements Rule
{
    /** Recognised file extensions used to detect a stacked extension. */
    private const KNOWN = [
        'pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'rtf', 'odt', 'ods',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'tif', 'tiff', 'heic',
        'zip', 'rar', '7z', 'gz', 'tar', 'bz2',
        'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar', 'exe', 'com', 'bat', 'cmd', 'sh',
        'bash', 'js', 'mjs', 'html', 'htm', 'xhtml', 'asp', 'aspx', 'jsp', 'pl', 'py', 'rb',
        'dll', 'msi', 'jar', 'vbs', 'ps1',
    ];

    public function passes($attribute, $value): bool
    {
        if (! $value instanceof UploadedFile) {
            return true; // not a file — leave it to the other rules
        }

        return ! $this->hasMultipleExtensions((string) $value->getClientOriginalName());
    }

    public function message(): string
    {
        return 'The file name has more than one extension (e.g. "name.txt.pdf"). '
            . 'Rename it to a single extension like "name.pdf" and upload again.';
    }

    private function hasMultipleExtensions(string $name): bool
    {
        $name = basename(str_replace('\\', '/', $name));
        $segments = explode('.', strtolower($name));

        // "file.ext" = 2 segments = a single extension; nothing stacked.
        if (count($segments) < 3) {
            return false;
        }

        array_shift($segments); // drop the base name; only trailing segments can be extensions

        $extCount = 0;
        foreach ($segments as $segment) {
            if (in_array(trim($segment), self::KNOWN, true)) {
                $extCount++;
            }
        }

        return $extCount >= 2;
    }
}
