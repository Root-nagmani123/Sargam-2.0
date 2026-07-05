<?php

namespace App\Services\FC;

use App\Models\FC\FcForm;
use App\Models\FC\StudentMaster as FcStudentMaster;
use App\Models\StudentMasterCourseMap;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Enrols an FC trainee into course_master via student_master_course__map after registration completes.
 */
class FcCourseEnrollmentService
{
    public function __construct(
        protected FcProgrammeContextService $programmeContext
    ) {}

    /**
     * @return array{enrolled: bool, course_master_pk: ?int, message: ?string}
     */
    public function enrollTrainee(int $userId, FcForm|int|null $form = null): array
    {
        $form = $this->resolveForm($userId, $form);
        if (! $form) {
            return ['enrolled' => false, 'course_master_pk' => null, 'message' => 'No registration form linked.'];
        }

        $coursePk = $this->programmeContext->coursePkForForm($form);
        if (! $coursePk) {
            return ['enrolled' => false, 'course_master_pk' => null, 'message' => 'Form is not linked to a Course Master programme.'];
        }

        $studentMasterPk = $this->resolveStudentMasterPk($userId);
        if (! $studentMasterPk) {
            return [
                'enrolled' => false,
                'course_master_pk' => $coursePk,
                'message' => 'Student record not found in Course Master (user_credentials / student_master).',
            ];
        }

        try {
            DB::transaction(function () use ($userId, $form, $coursePk, $studentMasterPk) {
                $this->syncFcTrackerFormId($userId, $form);

                StudentMasterCourseMap::where('student_master_pk', $studentMasterPk)
                    ->where('course_master_pk', '!=', $coursePk)
                    ->update(['active_inactive' => 0]);

                $now = now();
                $enrollment = StudentMasterCourseMap::updateOrCreate(
                    [
                        'student_master_pk' => $studentMasterPk,
                        'course_master_pk' => $coursePk,
                    ],
                    [
                        'active_inactive' => 1,
                        'modified_date' => $now,
                    ]
                );

                if ($enrollment->wasRecentlyCreated && empty($enrollment->created_date)) {
                    $enrollment->created_date = $now;
                    $enrollment->save();
                }
            });

            return ['enrolled' => true, 'course_master_pk' => $coursePk, 'message' => null];
        } catch (\Throwable $e) {
            Log::error('FC course enrollment failed', [
                'user_id' => $userId,
                'form_id' => $form->id,
                'course_master_pk' => $coursePk,
                'error' => $e->getMessage(),
            ]);

            return ['enrolled' => false, 'course_master_pk' => $coursePk, 'message' => 'Enrollment could not be saved.'];
        }
    }

    protected function resolveForm(int $userId, FcForm|int|null $form): ?FcForm
    {
        if ($form instanceof FcForm) {
            return $form;
        }

        if (is_int($form) && $form > 0) {
            return FcForm::query()->find($form);
        }

        $formId = null;
        if (Schema::hasColumn('student_masters', 'form_id')) {
            $formId = FcStudentMaster::query()->where(fc_user_col('student_masters'), fc_user_val('student_masters', $userId))->value('form_id');
        }

        if (! $formId) {
            $formId = session(FcRegistrationIntentService::SESSION_FORM_ID);
        }

        if ($formId) {
            return FcForm::query()->find($formId);
        }

        return FcForm::activeRegistrationDynamicForm();
    }

    protected function resolveStudentMasterPk(int $userId): ?int
    {
        $user = Auth::user();
        if ($user instanceof User && ($user->user_category ?? null) === 'S' && ! empty($user->user_id)) {
            return (int) $user->user_id;
        }

        $pk = User::query()
            ->where('pk', $userId)
            ->where('user_category', 'S')
            ->value('pk');

        return $pk ? (int) $pk : null;
    }

    protected function syncFcTrackerFormId(int $userId, FcForm $form): void
    {
        if (! Schema::hasColumn('student_masters', 'form_id')) {
            return;
        }

        $uCol = fc_user_col('student_masters');
        $keys = [$uCol => fc_user_val('student_masters', $userId)];
        if (Schema::hasColumn('student_masters', 'form_id')) {
            $keys['form_id'] = $form->id;
        }

        FcStudentMaster::query()->updateOrCreate($keys, []);
    }
}
