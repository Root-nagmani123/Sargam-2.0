<?php

namespace App\Services\COE;

use App\Models\FacultyMaster;
use App\Models\QuestionPaper;
use App\Models\QuestionPaperFile;
use App\Support\COE\QuestionPaperStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * COE Examination - who may see and act on a question paper.
 *
 * Kept out of the controllers because the same questions are asked from the
 * faculty screens, the examination section screens, the translation screens and
 * the download route, and a paper leaking early is the failure this module
 * exists to prevent: the rules are worth having in one place where they can be
 * read end to end.
 */
class QuestionPaperAccess
{
    /**
     * Role names come from config('coe.question_paper.roles') rather than being
     * fixed here: the roles this module needs are Milestone 1's to create, and
     * the institute may name them differently - translation, for instance, is
     * done by the Raj Bhasha section. A rename is then a config edit.
     */
    public static function examSectionRoles(): array
    {
        return config('coe.question_paper.roles.exam_section', ['Super Admin']);
    }

    public static function translationRoles(): array
    {
        return config('coe.question_paper.roles.translation_section', ['Super Admin']);
    }

    public static function approverRoles(): array
    {
        return config('coe.question_paper.roles.approver', ['Super Admin']);
    }

    /* -------------------------------------------------- identity */

    /** The faculty_master.pk of the signed-in user, if they are a faculty member. */
    public static function currentFacultyPk(): ?int
    {
        $userId = Auth::user()->user_id ?? null;

        if (! $userId) {
            return null;
        }

        $pk = FacultyMaster::where('employee_master_pk', $userId)->value('pk');

        return $pk ? (int) $pk : null;
    }

    public static function isExamSection(): bool
    {
        return self::hasAnyRole(self::examSectionRoles());
    }

    public static function isTranslationSection(): bool
    {
        return self::hasAnyRole(self::translationRoles());
    }

    public static function isApprover(): bool
    {
        return self::hasAnyRole(self::approverRoles());
    }

    /** The paper's own setter, as opposed to any other faculty member. */
    public static function isAssignedFaculty(QuestionPaper $paper): bool
    {
        $facultyPk = self::currentFacultyPk();

        return $facultyPk !== null && (int) $paper->faculty_master_pk === $facultyPk;
    }

    /* -------------------------------------------------- viewing */

    /**
     * Whether the user may open this paper's screens at all.
     *
     * A faculty member reaches only their own papers; the section roles reach
     * every paper because overseeing them is their job.
     */
    public static function canView(QuestionPaper $paper): bool
    {
        return self::isExamSection()
            || self::isApprover()
            || self::isTranslationSection()
            || self::isAssignedFaculty($paper);
    }

    /**
     * Whether the user may download one particular file.
     *
     * Narrower than canView on purpose. The answer key is the part of a paper
     * that must not travel further than it has to, so the Translation Section -
     * who translate the questions - are not given it.
     */
    public static function canDownloadFile(QuestionPaperFile $file, QuestionPaper $paper): bool
    {
        if (self::isExamSection() || self::isApprover()) {
            return true;
        }

        if (self::isAssignedFaculty($paper)) {
            return true;
        }

        if (self::isTranslationSection()) {
            // Only once the paper has actually been sent for translation, and
            // never the answer key.
            $inTranslation = in_array($paper->status, [
                QuestionPaperStatus::TRANSLATION_PENDING,
                QuestionPaperStatus::TRANSLATED,
                QuestionPaperStatus::FINALIZED,
            ], true);

            return $inTranslation && $file->file_type !== QuestionPaperFile::TYPE_ANSWER_KEY;
        }

        return false;
    }

    /* -------------------------------------------------- acting */

    /** Uploading and replacing files is the assigned faculty member's alone. */
    public static function canUpload(QuestionPaper $paper): bool
    {
        return self::isAssignedFaculty($paper) && $paper->isEditable();
    }

    public static function canFreeze(QuestionPaper $paper): bool
    {
        return self::isAssignedFaculty($paper) && $paper->canFreeze();
    }

    public static function canRequestUnfreeze(QuestionPaper $paper): bool
    {
        return self::isAssignedFaculty($paper) && $paper->canRequestUnfreeze();
    }

    /**
     * Approving an unfreeze is an examination section decision. The faculty
     * member who asked for it cannot also grant it, even if they hold both
     * roles - that would make the approval step meaningless.
     */
    public static function canApproveUnfreeze(QuestionPaper $paper): bool
    {
        return self::isExamSection() && ! self::isAssignedFaculty($paper);
    }

    public static function canMarkForTranslation(QuestionPaper $paper): bool
    {
        return self::isExamSection() && $paper->canMarkForTranslation();
    }

    public static function canUploadTranslation(QuestionPaper $paper): bool
    {
        return self::isTranslationSection()
            && QuestionPaperStatus::canUploadTranslation($paper->status);
    }

    public static function canFinalize(QuestionPaper $paper): bool
    {
        return self::isApprover() && $paper->canFinalize();
    }

    /* -------------------------------------------------- recipients */

    /** Logins to notify when a paper needs the Examination Section's attention. */
    public static function examSectionUserIds(): array
    {
        return self::userIdsWithRoles(self::examSectionRoles());
    }

    public static function translationSectionUserIds(): array
    {
        return self::userIdsWithRoles(self::translationRoles());
    }

    public static function approverUserIds(): array
    {
        return self::userIdsWithRoles(self::approverRoles());
    }

    /**
     * The user_ids behind a set of Spatie roles.
     *
     * Notifications address a login's user_id, while model_has_roles keys on
     * the user_credentials primary key, so the two are joined here. Failures
     * return an empty list rather than throwing: a missing notification must
     * not break the action that triggered it.
     */
    protected static function userIdsWithRoles(array $roles): array
    {
        try {
            return DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->join('user_credentials', 'user_credentials.pk', '=', 'model_has_roles.model_id')
                ->whereIn('roles.name', $roles)
                ->whereNotNull('user_credentials.user_id')
                ->distinct()
                ->pluck('user_credentials.user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } catch (Throwable $e) {
            Log::error('COE question paper role lookup failed', [
                'roles' => $roles,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /* -------------------------------------------------- internals */

    protected static function hasAnyRole(array $roles): bool
    {
        if (! Auth::check()) {
            return false;
        }

        foreach ($roles as $role) {
            if (hasRole($role)) {
                return true;
            }
        }

        return false;
    }
}
