<?php

namespace App\Services\COE;

use App\Models\ExaminationDrive;
use App\Models\ExaminationDriveComponentMap;
use App\Models\ExaminationDriveFacultyMap;
use App\Models\QuestionPaper;
use App\Support\COE\QuestionPaperStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * COE Examination - turning a drive's components into question papers.
 *
 * The Examination Drive module decides which subjects and components a drive
 * carries and which faculty member owns each subject. This reads that and
 * creates one paper row per component that is actually sat as a written exam.
 */
class QuestionPaperAssignmentService
{
    /**
     * Components of a drive that need a paper written.
     *
     * Not every component is a written exam: a subject can carry an assignment
     * and a Moodle assessment alongside its paper, and those are marked
     * elsewhere. config('coe.question_paper.paper_required_sources') is what
     * separates them.
     */
    public function paperRequiredComponents($driveId): Collection
    {
        return ExaminationDriveComponentMap::with(['subject', 'component'])
            ->where('examination_drive_id', $driveId)
            ->whereIn('source', config('coe.question_paper.paper_required_sources', ['Faculty']))
            ->get();
    }

    /**
     * Create the missing paper rows for a drive.
     *
     * Safe to run again: components that already have a paper are left exactly
     * as they are, so re-running after the drive gains a subject adds only the
     * new ones and never disturbs a paper already being written.
     *
     * A component whose subject has no faculty mapped is skipped rather than
     * assigned to nobody - it is reported back so the Examination Section can
     * fix the mapping on the drive side.
     */
    public function syncForDrive($driveId, ?string $deadline = null, ?int $userId = null): array
    {
        $components = $this->paperRequiredComponents($driveId);

        if ($components->isEmpty()) {
            return ['created' => 0, 'created_ids' => [], 'existing' => 0, 'skipped' => []];
        }

        // One lookup for the whole drive rather than a query per component.
        $facultyBySubject = ExaminationDriveFacultyMap::where('examination_drive_id', $driveId)
            ->pluck('faculty_master_pk', 'subject_master_pk');

        $existing = QuestionPaper::forDrive($driveId)
            ->pluck('id', 'drive_component_map_id');

        $created = 0;
        $skipped = [];
        $rows = [];

        foreach ($components as $component) {
            if ($existing->has($component->id)) {
                continue;
            }

            $facultyPk = $facultyBySubject->get($component->subject_master_pk);

            if (! $facultyPk) {
                $skipped[] = [
                    'component_map_id' => $component->id,
                    'subject' => optional($component->subject)->subject_name ?? 'Subject #' . $component->subject_master_pk,
                    'component' => optional($component->component)->component_name ?? '-',
                    'reason' => 'No faculty mapped to this subject in the examination drive.',
                ];
                continue;
            }

            $rows[] = [
                'examination_drive_id' => $driveId,
                'drive_component_map_id' => $component->id,
                'faculty_master_pk' => $facultyPk,
                'deadline' => $deadline,
                'status' => QuestionPaperStatus::DRAFT,
                'created_by' => $userId,
                'created_date' => now(),
                'modified_date' => now(),
            ];
            $created++;
        }

        $createdIds = [];

        if ($rows) {
            DB::transaction(function () use ($rows, $driveId, &$createdIds) {
                QuestionPaper::insert($rows);

                // Read back which rows are new, so the caller can notify only
                // those setters. A re-run must not re-notify a paper already in
                // someone's hands.
                $createdIds = QuestionPaper::forDrive($driveId)
                    ->whereIn('drive_component_map_id', array_column($rows, 'drive_component_map_id'))
                    ->pluck('id')
                    ->all();
            });
        }

        return [
            'created' => $created,
            'created_ids' => $createdIds,
            'existing' => $existing->count(),
            'skipped' => $skipped,
        ];
    }

    /**
     * Set a deadline across a drive.
     *
     * Only papers still being written are touched: a frozen or finalized paper
     * has already met whatever deadline applied to it, and moving its date
     * afterwards would misrepresent the record.
     */
    public function setDeadline($driveId, string $deadline, bool $onlyOpen = true): int
    {
        $query = QuestionPaper::forDrive($driveId);

        if ($onlyOpen) {
            $query->whereIn('status', [
                QuestionPaperStatus::DRAFT,
                QuestionPaperStatus::UPLOADED,
                QuestionPaperStatus::UNFROZEN,
            ]);
        }

        return $query->update([
            'deadline' => $deadline,
            'modified_date' => now(),
        ]);
    }

    /**
     * Re-point a paper at a different faculty member.
     *
     * Refused once the paper is frozen: the frozen file is that person's work
     * and the record of who froze it has to keep matching who owns it.
     */
    public function reassignFaculty(QuestionPaper $paper, int $facultyPk): bool
    {
        if (! $paper->isEditable()) {
            return false;
        }

        $paper->update([
            'faculty_master_pk' => $facultyPk,
            'modified_date' => now(),
        ]);

        return true;
    }

    /** Drives the paper screens can work on. A draft drive has nothing settled yet. */
    public function selectableDrives(): Collection
    {
        return ExaminationDrive::with(['course', 'term', 'examinationType'])
            ->whereIn('status', [ExaminationDrive::STATUS_PUBLISHED, ExaminationDrive::STATUS_CLOSED])
            ->orderByDesc('id')
            ->get();
    }

    /** "FC-101 - Mid-Term Examination (Phase-I, 2026)" for a drive dropdown. */
    public function driveLabel(ExaminationDrive $drive): string
    {
        return sprintf(
            '%s - %s %s (%s, %s)',
            optional($drive->course)->course_name ?? 'Course #' . $drive->course_master_pk,
            optional($drive->term)->term_name ?? '',
            optional($drive->examinationType)->exam_type_name ?? '',
            $drive->phase,
            $drive->academic_session
        );
    }
}
