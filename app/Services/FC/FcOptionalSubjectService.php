<?php

namespace App\Services\FC;

use Illuminate\Support\Facades\DB;

/**
 * Service-aware options for the "Optional Subject First / Second" dropdowns on the
 * Education Summary group.
 *
 * IFoS (Indian Forest Service) trainees choose TWO optional subjects (from the IFoS
 * subject list); everyone else chooses ONE (from the CSE list) and the second dropdown
 * is disabled. Subjects live in upse_subject_master keyed by service_type ('IFoS'|'CSE').
 */
class FcOptionalSubjectService
{
    /** Group field names for the two dropdowns (the first keeps its original "optonal" spelling). */
    public const FIRST_FIELD  = 'optonal_subject_first';
    public const SECOND_FIELD = 'optional_subject_second';

    private const SUBJECT_TABLE = 'upse_subject_master';

    public function __construct(private FcImportedProfileLockService $lock) {}

    /**
     * Rewrite the optional-subject dropdown options inside a group's lookup map to the
     * trainee's service, and return the field names that must be rendered disabled.
     *
     * @param  array<string, mixed>  $groupLookups  field_name => lookup rows (mutated in place)
     * @return list<string>  field names to disable (second subject for non-IFoS)
     */
    public function applyGroupOverrides(array &$groupLookups, int $userId): array
    {
        $hasFirst  = array_key_exists(self::FIRST_FIELD, $groupLookups);
        $hasSecond = array_key_exists(self::SECOND_FIELD, $groupLookups);
        if (! $hasFirst && ! $hasSecond) {
            return [];
        }

        $isIfos = $this->isIfosUser($userId);
        $items  = $this->subjectRows($isIfos ? 'IFoS' : 'CSE');

        if ($hasFirst) {
            $groupLookups[self::FIRST_FIELD] = $items;
        }
        if ($hasSecond) {
            $groupLookups[self::SECOND_FIELD] = $items;
        }

        // Second optional subject only applies to IFoS.
        return $isIfos ? [] : [self::SECOND_FIELD];
    }

    /**
     * Active subjects for an exam/service type ('IFoS' | 'CSE'), ordered by name.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function subjectRows(string $serviceType)
    {
        if (! fc_schema_has_table(self::SUBJECT_TABLE)) {
            return collect();
        }

        $query = DB::table(self::SUBJECT_TABLE)->where('service_type', $serviceType);
        if (fc_schema_has_column(self::SUBJECT_TABLE, 'active_inactive')) {
            $query->where('active_inactive', 1);
        }

        return $query->orderBy('subject_name')->get();
    }

    /** Whether the trainee's service is the Indian Forest Service (IFoS). */
    public function isIfosUser(int $userId): bool
    {
        $servicePk = $this->userServicePk($userId);

        return $servicePk !== null && in_array($servicePk, $this->ifosServiceIds(), true);
    }

    /**
     * service_master pk(s) that represent the Indian Forest Service. Matched by the
     * short code and the full name so it survives either being edited.
     *
     * @return list<int>
     */
    public function ifosServiceIds(): array
    {
        return DB::table('service_master')
            ->where(function ($q) {
                $q->where('service_short_name', 'IFS(AIS)')
                  ->orWhereRaw("UPPER(TRIM(service_name)) = 'INDIAN FOREST SERVICES'");
            })
            ->pluck('pk')
            ->map(fn ($pk) => (int) $pk)
            ->all();
    }

    /**
     * The trainee's service_master pk — prefers the value already captured on the form
     * (student_master_firsts.service_id), falling back to the academy roster.
     */
    public function userServicePk(int $userId): ?int
    {
        if (fc_schema_has_table('student_master_firsts') && fc_schema_has_column('student_master_firsts', 'service_id')) {
            $value = DB::table('student_master_firsts')
                ->where(fc_user_col('student_master_firsts'), fc_user_val('student_master_firsts', $userId))
                ->value('service_id');
            if (filled($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return $this->lock->serviceMasterPk($userId);
    }
}
