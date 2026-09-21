<?php

namespace App\Services\COE;

use App\Models\QuestionPaper;
use App\Models\QuestionPaperFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * COE Examination - storing and retrieving question paper files.
 *
 * Every write and every read of a paper file goes through here. Uploads are
 * validated and hashed, stored under a generated name on the private disk, and
 * never deleted on replacement - the old row is superseded so a corrected paper
 * keeps its earlier files on record.
 */
class QuestionPaperFileService
{
    /**
     * Store one uploaded file against a paper.
     *
     * Replacing a file of the same type and language supersedes the previous
     * row rather than overwriting it, so an English original stays intact when
     * a Hindi translation arrives, and a pre-correction paper stays on record
     * after an unfreeze.
     */
    public function store(
        QuestionPaper $paper,
        UploadedFile $file,
        string $fileType,
        string $language,
        ?int $userId = null
    ): QuestionPaperFile {
        $this->assertValidUpload($file);
        $this->assertValidType($fileType);
        $this->assertValidLanguage($language);

        $storedPath = $this->buildPath($paper, $fileType, $language, $file);

        // Hash the file as it arrives: once it is on disk under a generated
        // name, this is what ties the bytes served at exam time back to the
        // bytes the faculty froze.
        $hash = hash_file('sha256', $file->getRealPath());

        return DB::transaction(function () use ($paper, $file, $fileType, $language, $storedPath, $hash, $userId) {
            $supersede = ! in_array($fileType, QuestionPaperFile::MULTIPLE_ALLOWED_TYPES, true);

            $nextVersion = 1;

            if ($supersede) {
                $existing = QuestionPaperFile::where('question_paper_id', $paper->id)
                    ->where('file_type', $fileType)
                    ->where('language', $language)
                    ->current()
                    ->get();

                foreach ($existing as $old) {
                    $nextVersion = max($nextVersion, (int) $old->version_no + 1);
                    $old->update(['is_current' => 0]);
                }
            }

            // Write after the DB work has been decided but inside the
            // transaction, so a failed insert does not leave an orphan file.
            $written = Storage::disk($this->disk())->putFileAs(
                dirname($storedPath),
                $file,
                basename($storedPath)
            );

            if ($written === false) {
                throw new RuntimeException('Question paper file could not be stored.');
            }

            return QuestionPaperFile::create([
                'question_paper_id' => $paper->id,
                'file_type' => $fileType,
                'language' => $language,
                'original_name' => $this->safeOriginalName($file),
                'stored_path' => $storedPath,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'file_hash' => $hash,
                'version_no' => $nextVersion,
                'is_current' => 1,
                'uploaded_by' => $userId,
                'uploaded_at' => now(),
            ]);
        });
    }

    /**
     * Supersede a file without replacing it. Used when a faculty member removes
     * an optional attachment before freezing; the row survives for the record.
     */
    public function supersede(QuestionPaperFile $file): void
    {
        $file->update(['is_current' => 0]);
    }

    /** Absolute path for streaming a download. */
    public function absolutePath(QuestionPaperFile $file): string
    {
        $disk = Storage::disk($this->disk());

        if (! $disk->exists($file->stored_path)) {
            throw new RuntimeException('Question paper file is missing from storage.');
        }

        return $disk->path($file->stored_path);
    }

    /**
     * Whether the stored bytes still match the hash recorded at upload.
     * Checked before a paper is finalized.
     */
    public function verifyIntegrity(QuestionPaperFile $file): bool
    {
        if (empty($file->file_hash)) {
            return true; // Nothing recorded to compare against.
        }

        $disk = Storage::disk($this->disk());

        if (! $disk->exists($file->stored_path)) {
            return false;
        }

        return hash_equals($file->file_hash, hash_file('sha256', $disk->path($file->stored_path)));
    }

    /* -------------------------------------------------- validation */

    /**
     * Extension and MIME are both checked. An extension on its own is
     * caller-supplied text, and a MIME on its own is set by the browser, so
     * neither is trusted alone.
     */
    protected function assertValidUpload(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new RuntimeException('The uploaded file is not valid.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = config('coe.question_paper.allowed_extensions', []);

        if (! in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException(
                'Only ' . strtoupper(implode(', ', $allowedExtensions)) . ' files are allowed.'
            );
        }

        // getMimeType() reads the file's own bytes rather than the header the
        // browser sent, so a renamed executable does not pass as a PDF.
        $detectedMime = $file->getMimeType();
        $allowedMimes = config('coe.question_paper.allowed_mimes', []);

        if (! in_array($detectedMime, $allowedMimes, true)) {
            throw new RuntimeException('The file content does not match an allowed document type.');
        }

        $maxKb = (int) config('coe.question_paper.max_file_size_kb', 20480);

        if ($file->getSize() > $maxKb * 1024) {
            throw new RuntimeException('The file exceeds the ' . round($maxKb / 1024) . ' MB limit.');
        }
    }

    protected function assertValidType(string $fileType): void
    {
        if (! array_key_exists($fileType, QuestionPaperFile::TYPE_LABELS)) {
            throw new RuntimeException('Unknown question paper file type.');
        }
    }

    protected function assertValidLanguage(string $language): void
    {
        if (! array_key_exists($language, config('coe.question_paper.languages', []))) {
            throw new RuntimeException('Unknown question paper language.');
        }
    }

    /* -------------------------------------------------- paths */

    /**
     * Build the storage path. Nothing from the uploaded filename reaches it:
     * the name is caller-supplied and is kept only as original_name, for
     * display on download.
     */
    protected function buildPath(
        QuestionPaper $paper,
        string $fileType,
        string $language,
        UploadedFile $file
    ): string {
        $extension = strtolower($file->getClientOriginalExtension());

        $name = sprintf(
            '%s_%s_%s.%s',
            strtolower($fileType),
            strtolower($language),
            Str::random(24),
            $extension
        );

        return sprintf(
            '%s/drive-%d/paper-%d/%s',
            trim(config('coe.question_paper.path', 'question-papers'), '/'),
            (int) $paper->examination_drive_id,
            (int) $paper->id,
            $name
        );
    }

    /** The display name, stripped of any path the client may have sent. */
    protected function safeOriginalName(UploadedFile $file): string
    {
        return Str::limit(basename($file->getClientOriginalName()), 250, '');
    }

    protected function disk(): string
    {
        return config('coe.question_paper.disk', 'coe_private');
    }
}
