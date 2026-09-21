<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * COE Examination - one uploaded file belonging to a question paper.
 *
 * A paper is several files (the paper itself, an answer key, instructions,
 * supporting material) in one or more languages. The English original and its
 * Hindi translation are separate rows, which is what keeps the requirement that
 * a translation never overwrites the original.
 *
 * Replacing a file marks the old row is_current = 0 rather than deleting it, so
 * a paper that was unfrozen and corrected keeps its earlier files on record.
 */
class QuestionPaperFile extends Model
{
    protected $table = 'question_paper_files';
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'is_current' => 'boolean',
        'uploaded_at' => 'datetime',
    ];

    public const TYPE_QUESTION_PAPER = 'QUESTION_PAPER';
    public const TYPE_ANSWER_KEY = 'ANSWER_KEY';
    public const TYPE_INSTRUCTIONS = 'INSTRUCTIONS';
    public const TYPE_SUPPORTING = 'SUPPORTING';

    public const TYPE_LABELS = [
        self::TYPE_QUESTION_PAPER => 'Question Paper',
        self::TYPE_ANSWER_KEY => 'Answer Key',
        self::TYPE_INSTRUCTIONS => 'Instructions',
        self::TYPE_SUPPORTING => 'Supporting File',
    ];

    /**
     * Only the question paper itself is required. The rest depend on the
     * subject - an essay paper has no answer key, most papers need no
     * supporting material.
     */
    public const REQUIRED_TYPES = [self::TYPE_QUESTION_PAPER];

    /**
     * A paper carries one current file of each type per language, except
     * supporting material, where several may be attached at once.
     */
    public const MULTIPLE_ALLOWED_TYPES = [self::TYPE_SUPPORTING];

    public function questionPaper()
    {
        return $this->belongsTo(QuestionPaper::class, 'question_paper_id', 'id');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', 1);
    }

    public function scopeOfLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /** The files the faculty uploaded, as opposed to a translation of them. */
    public function scopeOriginal($query)
    {
        return $query->where('language', config('coe.question_paper.original_language', 'EN'));
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('file_type', $type);
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->file_type] ?? $this->file_type;
    }

    public function languageLabel(): string
    {
        return config('coe.question_paper.languages')[$this->language] ?? $this->language;
    }

    public function isOriginal(): bool
    {
        return $this->language === config('coe.question_paper.original_language', 'EN');
    }

    /** Human-readable size for listings; the raw byte count stays in file_size. */
    public function readableSize(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes <= 0) {
            return '-';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / (1024 * 1024), 1) . ' MB';
    }

    /**
     * What a version snapshot stores about this file. Deliberately excludes the
     * stored path: a snapshot is a record of what was frozen, not a second
     * route to the bytes.
     */
    public function toSnapshot(): array
    {
        return [
            'id' => $this->id,
            'file_type' => $this->file_type,
            'language' => $this->language,
            'original_name' => $this->original_name,
            'file_hash' => $this->file_hash,
            'file_size' => $this->file_size,
            'version_no' => $this->version_no,
            'uploaded_by' => $this->uploaded_by,
            'uploaded_at' => optional($this->uploaded_at)->toDateTimeString(),
        ];
    }
}
