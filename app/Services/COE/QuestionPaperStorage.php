<?php

namespace App\Services\COE;

use App\Models\QuestionPaper;
use App\Support\COE\PaperHtmlSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Question paper files (original and translation) on the private disk. They
 * are only ever handed out through forViewer() or download(), never by URL.
 */
class QuestionPaperStorage
{
    public function __construct(private PaperHtmlSanitizer $sanitizer)
    {
    }

    /**
     * Writes under a name unique on disk, so a second submit racing this one can
     * never overwrite (and then, on its failure, delete) this file.
     *
     * @param  string  $baseName  e.g. "QP-3-025_v2" or "QP-3-025_hindi"
     * @return string the stored path
     */
    public function store(int $driveId, string $baseName, string $ext, ?UploadedFile $file, ?string $contents = null): string
    {
        $dir = 'coe/question-papers/'.$driveId;
        $name = $baseName.'_'.Str::random(8).'.'.$ext;
        $disk = Storage::disk(QuestionPaper::DISK);

        $stored = $file ? $disk->putFileAs($dir, $file, $name) : $disk->put($dir.'/'.$name, (string) $contents);
        if (! $stored) {
            throw new \RuntimeException("Could not write question paper file {$baseName} to the '".QuestionPaper::DISK."' disk.");
        }

        return $dir.'/'.$name;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(QuestionPaper::DISK)->delete($path);
        }
    }

    /** The stored file's contents, or null when it is missing. */
    public function contents(?string $path): ?string
    {
        $disk = Storage::disk(QuestionPaper::DISK);

        return $path && $disk->exists($path) ? $disk->get($path) : null;
    }

    /**
     * A stored paper for the read-only viewer, never as a file: an editor paper
     * as its cleaned HTML, a Word file (an older upload or translation) as base64
     * for the browser to lay out.
     *
     * @return array{format:string, content:string}
     */
    public function forViewer(?string $path, string $name): array
    {
        $contents = $this->contents($path);
        abort_if($contents === null, 404);

        return str_ends_with(strtolower($name), '.html')
            ? ['format' => 'html', 'content' => $this->sanitizer->fromDocument($contents)]
            : ['format' => 'docx', 'content' => base64_encode($contents)];
    }

    /**
     * Always an attachment, never rendered inline: an editor paper is HTML and
     * must not run on this origin.
     */
    public function download(?string $path, string $name): StreamedResponse
    {
        $disk = Storage::disk(QuestionPaper::DISK);
        abort_unless($path && $disk->exists($path), 404);

        return $disk->download($path, $name, ['X-Content-Type-Options' => 'nosniff']);
    }
}
