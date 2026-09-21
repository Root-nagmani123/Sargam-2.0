<?php

namespace App\Support\COE;

/**
 * The question paper state machine.
 *
 * Every screen in the module asks the same two questions - "what can this user
 * do now?" and "where can this paper go next?" - so both answers live here
 * rather than being re-derived as scattered if-checks in controllers and blades.
 *
 * Stored as a tinyint: the labels are display text and may be reworded, the
 * numbers are data and must not be renumbered once rows exist.
 */
class QuestionPaperStatus
{
    /** Assigned to a faculty member; nothing uploaded yet. */
    public const DRAFT = 0;

    /** Files uploaded and still editable by the faculty. */
    public const UPLOADED = 1;

    /** Locked by the faculty. No edit, no replace, no delete. */
    public const FROZEN = 2;

    /** Sent to the Translation Section. */
    public const TRANSLATION_PENDING = 3;

    /** Translation uploaded and locked. */
    public const TRANSLATED = 4;

    /** Approved. Terminal state - nothing edits a finalized paper. */
    public const FINALIZED = 5;

    /** An approved unfreeze request reopened a frozen paper for correction. */
    public const UNFROZEN = 6;

    public const LABELS = [
        self::DRAFT => 'Draft',
        self::UPLOADED => 'Uploaded',
        self::FROZEN => 'Frozen',
        self::TRANSLATION_PENDING => 'Translation Pending',
        self::TRANSLATED => 'Translated',
        self::FINALIZED => 'Finalized',
        self::UNFROZEN => 'Unfrozen',
    ];

    /** Bootstrap badge class per status, so listings colour-code consistently. */
    public const BADGES = [
        self::DRAFT => 'secondary',
        self::UPLOADED => 'info',
        self::FROZEN => 'primary',
        self::TRANSLATION_PENDING => 'warning',
        self::TRANSLATED => 'info',
        self::FINALIZED => 'success',
        self::UNFROZEN => 'danger',
    ];

    public static function label(?int $status): string
    {
        return self::LABELS[$status] ?? 'Unknown';
    }

    public static function badge(?int $status): string
    {
        return self::BADGES[$status] ?? 'secondary';
    }

    /**
     * Statuses in which the faculty may add, replace or remove files.
     * UNFROZEN is editable because that is the whole point of unfreezing.
     */
    public static function editableStatuses(): array
    {
        return [self::DRAFT, self::UPLOADED, self::UNFROZEN];
    }

    public static function isEditable(?int $status): bool
    {
        return in_array($status, self::editableStatuses(), true);
    }

    /** A paper can be frozen only once it actually has files on it. */
    public static function canFreeze(?int $status): bool
    {
        return in_array($status, [self::UPLOADED, self::UNFROZEN], true);
    }

    /**
     * Unfreeze is for correcting a paper the faculty has locked. Once it has
     * gone to translation the correction has to go through that side instead,
     * so only a plain FROZEN paper qualifies.
     */
    public static function canRequestUnfreeze(?int $status): bool
    {
        return $status === self::FROZEN;
    }

    public static function canMarkForTranslation(?int $status): bool
    {
        return $status === self::FROZEN;
    }

    public static function canUploadTranslation(?int $status): bool
    {
        return $status === self::TRANSLATION_PENDING;
    }

    public static function canFinalize(?int $status): bool
    {
        return $status === self::TRANSLATED;
    }

    /** Finalized is terminal: no status change, no file change, ever. */
    public static function isLocked(?int $status): bool
    {
        return $status === self::FINALIZED;
    }
}
