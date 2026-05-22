<?php

namespace App\Services\FC;

use App\Models\CourseMaster;
use App\Models\FC\FcForm;
use Illuminate\Support\Facades\Session;

/**
 * Resolves programme (course_master) ↔ registration form (fc_forms).
 */
class FcProgrammeContextService
{
    public const SESSION_COURSE_PK = 'fc_reg_intended_course_master_pk';

    public function coursePkForForm(FcForm|int $form): ?int
    {
        $model = $form instanceof FcForm ? $form : FcForm::query()->find($form);

        return $model?->course_master_pk ? (int) $model->course_master_pk : null;
    }

    public function activeFormForCourse(int $courseMasterPk): ?FcForm
    {
        return FcForm::query()
            ->where('course_master_pk', $courseMasterPk)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();
    }

    public function rememberCourseForForm(FcForm $form): void
    {
        $pk = $this->coursePkForForm($form);
        if ($pk) {
            Session::put(self::SESSION_COURSE_PK, $pk);
        }
    }

    public function coursePkFromSession(): ?int
    {
        $pk = Session::get(self::SESSION_COURSE_PK);

        return $pk ? (int) $pk : null;
    }

    public function forgetCourseIntent(): void
    {
        Session::forget(self::SESSION_COURSE_PK);
    }

    public function courseForForm(FcForm|int $form): ?CourseMaster
    {
        $pk = $this->coursePkForForm($form);

        return $pk ? CourseMaster::query()->find($pk) : null;
    }
}
