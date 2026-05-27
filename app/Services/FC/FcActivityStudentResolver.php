<?php

namespace App\Services\FC;

use App\Models\FC\FcOtDetail;
use App\Models\FC\FcPreHistory;
use App\Models\StudentMaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Resolves FC post-arrival trainees from student_master (+ user_credentials),
 * with fc_registration_master fallback when OT exists on roster but not on student_master.
 * Syncs a slim fc_ot_details row for the medical module (unchanged behaviour).
 */
class FcActivityStudentResolver
{
    /**
     * @return object{
     *     student: StudentMaster,
     *     credentials_pk: int,
     *     user_id: int,
     *     otcode: string,
     *     otname: string,
     *     house: string,
     *     housen: string,
     *     mobileno: string,
     *     service: string
     * }|null
     */
    public function findByOtCode(string $otCode, ?int $courseMasterPk = null): ?object
    {
        $otCode = trim($otCode);
        if ($otCode === '') {
            return null;
        }

        $query = StudentMaster::query()
            ->whereRaw('UPPER(TRIM(generated_OT_code)) = ?', [strtoupper($otCode)]);

        if ($courseMasterPk) {
            $query->where(function ($q) use ($courseMasterPk) {
                $q->where('course_master_pk', $courseMasterPk)
                    ->orWhereExists(function ($sub) use ($courseMasterPk) {
                        $sub->select(DB::raw(1))
                            ->from('student_master_course__map as smcm')
                            ->whereColumn('smcm.student_master_pk', 'student_master.pk')
                            ->where('smcm.course_master_pk', $courseMasterPk)
                            ->where('smcm.active_inactive', 1);
                    });
            });
        }

        $student = $query->orderByDesc('pk')->first();
        if ($student) {
            return $this->snapshot($student);
        }

        $roster = $this->findRosterByOtCode($otCode, $courseMasterPk);
        if (! $roster && $courseMasterPk) {
            $roster = $this->findRosterByOtCode($otCode, null);
        }
        if (! $roster) {
            return null;
        }

        $rosterOt = trim((string) ($roster->generated_OT_code ?? $otCode));
        $student = $this->resolveStudentForRoster($roster);
        if ($student) {
            return $this->snapshot($student, $rosterOt);
        }

        return $this->snapshotFromRoster($roster, $rosterOt);
    }

    /**
     * fc_otactivity_details.user_id must be user_credentials.pk (project-wide convention).
     */
    public function activityUserIdForStudent(StudentMaster $student): ?int
    {
        return $this->credentialsPkForStudent($student);
    }

    public function credentialsPkForStudent(StudentMaster $student): ?int
    {
        $pk = DB::table('user_credentials')
            ->where('user_id', $student->pk)
            ->value('pk');

        if ($pk !== null) {
            return (int) $pk;
        }

        $login = trim((string) ($student->user_id ?? ''));
        if ($login === '') {
            return null;
        }

        $pk = DB::table('user_credentials')
            ->where('user_name', $login)
            ->value('pk');

        return $pk !== null ? (int) $pk : null;
    }

    public function hasPreHistoryForTrainee(object $trainee, ?string $course = null): bool
    {
        $credentialsPk = (int) ($trainee->credentials_pk ?? 0);

        return $credentialsPk > 0 && $this->hasPreHistory($credentialsPk, $course);
    }

    public function displayName(StudentMaster $student): string
    {
        $display = trim((string) ($student->display_name ?? ''));
        if ($display !== '') {
            return $display;
        }

        return trim(implode(' ', array_filter([
            $student->first_name ?? '',
            $student->middle_name ?? '',
            $student->last_name ?? '',
        ])));
    }

    public function serviceLabel(StudentMaster $student): string
    {
        if (! $student->service_master_pk) {
            return '';
        }

        if (! Schema::hasTable('service_master')) {
            return '';
        }

        $nameCol = Schema::hasColumn('service_master', 'service_name')
            ? 'service_name'
            : (Schema::hasColumn('service_master', 'service_short_name') ? 'service_short_name' : null);

        if ($nameCol === null) {
            return '';
        }

        return trim((string) (DB::table('service_master')
            ->where('pk', $student->service_master_pk)
            ->value($nameCol) ?? ''));
    }

    public function hasPreHistory(int $credentialsPk, ?string $course = null): bool
    {
        if (! Schema::hasTable('fc_pre_history')) {
            return false;
        }

        $col = fc_user_col('fc_pre_history');
        $q = FcPreHistory::query()->where($col, fc_user_val('fc_pre_history', $credentialsPk));

        if ($course !== null && trim($course) !== '') {
            $course = trim($course);
            $q->where(function ($w) use ($course) {
                $w->where('course', $course)
                    ->orWhereRaw('TRIM(course) = ?', [$course]);
            });
        }

        return $q->exists();
    }

    /**
     * Keep medical / consultation screens working on fc_ot_details.
     */
    public function syncMedicalOtDetail(object $trainee, ?string $courseName = null): void
    {
        if (! Schema::hasTable('fc_ot_details')) {
            return;
        }

        $course = $courseName !== null && trim($courseName) !== ''
            ? Str::limit(trim($courseName), 120, '')
            : ($trainee->student ? $this->courseNameForStudent($trainee->student) : null);

        $userCol = fc_user_col('fc_ot_details');
        $credentialsPk = (int) ($trainee->credentials_pk ?? 0);
        if ($credentialsPk < 1) {
            return;
        }

        $userVal = fc_user_val('fc_ot_details', $credentialsPk);

        $attrs = [
            $userCol => $userVal,
            'otname' => $trainee->otname,
            'otcode' => $trainee->otcode,
            'course' => $course,
            'mobileno' => $trainee->mobileno,
            'service' => Str::limit($trainee->service, 40, ''),
            'house' => null,
            'housen' => Str::limit($trainee->housen, 50, ''),
            'status' => 1,
        ];

        $existing = FcOtDetail::query()->where('otcode', $trainee->otcode)->first();

        if ($existing) {
            $existing->update($attrs);

            return;
        }

        FcOtDetail::query()->create($attrs);
    }

    /**
     * Trainees for status grids / not-joined reports (credentials pk as user_id).
     *
     * @return Collection<int, object{user_id: int, otname: string, otcode: string, mobileno: string, service: string}>
     */
    public function listForActivityGrids(): Collection
    {
        $query = StudentMaster::query()
            ->whereNotNull('generated_OT_code')
            ->where('generated_OT_code', '!=', '')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('user_credentials as uc')
                    ->whereColumn('uc.user_id', 'student_master.pk');
            });

        if (Schema::hasColumn('student_master', 'status')) {
            $query->where('status', 1);
        }

        return $query
            ->orderBy('generated_OT_code')
            ->get()
            ->map(function (StudentMaster $student) {
                $snap = $this->snapshot($student);
                if ($snap->credentials_pk === null) {
                    return null;
                }

                return (object) [
                    'user_id' => $snap->credentials_pk,
                    'otname' => $snap->otname,
                    'otcode' => $snap->otcode,
                    'mobileno' => $snap->mobileno,
                    'service' => $snap->service,
                    'house' => $snap->house,
                    'housen' => $snap->housen,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return object{
     *     student: StudentMaster,
     *     credentials_pk: int|null,
     *     user_id: int,
     *     otcode: string,
     *     otname: string,
     *     house: string,
     *     housen: string,
     *     mobileno: string,
     *     service: string
     * }
     */
    private function snapshot(StudentMaster $student, ?string $otCodeOverride = null): object
    {
        $credentialsPk = $this->credentialsPkForStudent($student);
        $otcode = $otCodeOverride ?? trim((string) ($student->generated_OT_code ?? ''));

        return (object) [
            'student' => $student,
            'credentials_pk' => $credentialsPk,
            'user_id' => $credentialsPk,
            'otcode' => $otcode,
            'otname' => $this->displayName($student),
            'house' => '',
            'housen' => trim((string) ($student->rank ?? '')),
            'mobileno' => trim((string) ($student->contact_no ?? '')),
            'service' => $this->serviceLabel($student),
        ];
    }

    /**
     * Roster-only trainee (not yet in student_master) — display allowed, save needs migration.
     */
    private function snapshotFromRoster(object $roster, string $otCode): object
    {
        return (object) [
            'student' => null,
            'credentials_pk' => null,
            'user_id' => null,
            'otcode' => $otCode,
            'otname' => $this->displayNameFromRoster($roster),
            'house' => '',
            'housen' => trim((string) ($roster->rank ?? '')),
            'mobileno' => trim((string) ($roster->contact_no ?? '')),
            'service' => $this->serviceLabelFromPk($roster->service_master_pk ?? null),
        ];
    }

    private function findRosterByOtCode(string $otCode, ?int $courseMasterPk = null): ?object
    {
        if (! Schema::hasTable('fc_registration_master')) {
            return null;
        }

        $query = DB::table('fc_registration_master')
            ->whereRaw('UPPER(TRIM(generated_OT_code)) = ?', [strtoupper($otCode)]);

        if ($courseMasterPk) {
            $query->where('course_master_pk', $courseMasterPk);
        }

        if (Schema::hasColumn('fc_registration_master', 'active_inactive')) {
            $query->where('active_inactive', 1);
        }

        return $query->orderByDesc('pk')->first();
    }

    private function resolveStudentForRoster(object $roster): ?StudentMaster
    {
        $login = trim((string) ($roster->user_id ?? ''));
        if ($login !== '') {
            $studentPk = DB::table('user_credentials')
                ->where('user_name', $login)
                ->value('user_id');

            if ($studentPk) {
                $student = StudentMaster::query()->find((int) $studentPk);
                if ($student) {
                    return $student;
                }
            }

            $student = StudentMaster::query()
                ->where('user_id', $login)
                ->orderByDesc('pk')
                ->first();
            if ($student) {
                return $student;
            }
        }

        $email = trim((string) ($roster->email ?? ''));
        if ($email !== '' && Schema::hasColumn('student_master', 'email')) {
            return StudentMaster::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower($email)])
                ->orderByDesc('pk')
                ->first();
        }

        return null;
    }

    private function displayNameFromRoster(object $roster): string
    {
        $display = trim((string) ($roster->display_name ?? ''));
        if ($display !== '') {
            return $display;
        }

        return trim(implode(' ', array_filter([
            $roster->first_name ?? '',
            $roster->middle_name ?? '',
            $roster->last_name ?? '',
        ])));
    }

    private function serviceLabelFromPk(mixed $serviceMasterPk): string
    {
        if (! $serviceMasterPk || ! Schema::hasTable('service_master')) {
            return '';
        }

        $nameCol = Schema::hasColumn('service_master', 'service_name')
            ? 'service_name'
            : (Schema::hasColumn('service_master', 'service_short_name') ? 'service_short_name' : null);

        if ($nameCol === null) {
            return '';
        }

        return trim((string) (DB::table('service_master')
            ->where('pk', $serviceMasterPk)
            ->value($nameCol) ?? ''));
    }

    private function courseNameForStudent(StudentMaster $student): ?string
    {
        if (! $student->course_master_pk || ! Schema::hasTable('course_master')) {
            return null;
        }

        return trim((string) (DB::table('course_master')
            ->where('pk', $student->course_master_pk)
            ->value('course_name') ?? '')) ?: null;
    }
}
