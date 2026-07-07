<?php

namespace App\Services\FC;

use Illuminate\Support\Facades\DB;

/**
 * Locks the identity fields that the academy provides through the "first Excel
 * upload" (RegistrationImportController → fc_registration_master). When a trainee
 * fills a dynamic registration form, any field that maps to one of these imported
 * columns is pre-filled from their roster row and rendered non-editable, so the
 * academy-provided data can be shown but never changed by the trainee.
 *
 * The dynamic form stores identity data in student_master_firsts, whose column
 * names differ slightly from fc_registration_master — this map bridges the two.
 */
class FcImportedProfileLockService
{
    /**
     * Dynamic-form field (target_table = student_master_firsts) → fc_registration_master column.
     *
     * Keyed by the form field's target_column and intentionally scoped to
     * student_master_firsts so we never lock the mother / father / guardian name
     * fields (which live on student_master_seconds and share labels like "First Name").
     *
     * @var array<string, string>
     */
    public const MASTER_MAP = [
        'first_name'  => 'first_name',
        'middle_name' => 'middle_name',
        'last_name'   => 'last_name',
        'email'       => 'email',
        'mobile_no'   => 'contact_no',
        'service_id'  => 'service_master_pk',
    ];

    private const LOCK_TABLE = 'student_master_firsts';

    /** Per-instance cache of the resolved roster row, keyed by userId. */
    private array $rosterRowCache = [];

    /**
     * Resolve which of the given fields are academy-provided and their locked value.
     *
     * @param  iterable<object>  $fields  A step's active fields (FcFormField instances)
     * @return array<string, mixed>  [field_name => locked value] for matched, non-empty fields
     */
    public function lockedValuesForFields(iterable $fields, int $userId): array
    {
        $row = $this->rosterRow($userId);
        if (! $row) {
            return [];
        }

        $locked = [];
        foreach ($fields as $field) {
            $targetTable = $field->target_table ?? null;
            $targetColumn = $field->target_column ?? null;

            if ($targetTable !== self::LOCK_TABLE || ! isset(self::MASTER_MAP[$targetColumn])) {
                continue;
            }

            $masterColumn = self::MASTER_MAP[$targetColumn];
            $value = $row->{$masterColumn} ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            // A 0 service_master_pk means "not set" on the roster — leave it editable.
            if ($masterColumn === 'service_master_pk' && (int) $value === 0) {
                continue;
            }

            $locked[$field->field_name] = $value;
        }

        return $locked;
    }

    /**
     * The trainee's imported fc_registration_master row, resolved from the auth id.
     *
     * Mirrors fc_user_val(): staged /fc/login users have a negative auth id equal to
     * -roster.pk; migrated users are linked via user_credentials.user_name = roster.user_id.
     */
    private function rosterRow(int $userId): ?object
    {
        if (array_key_exists($userId, $this->rosterRowCache)) {
            return $this->rosterRowCache[$userId];
        }

        $query = DB::table('fc_registration_master');

        if ($userId < 0) {
            $row = $query->where('pk', abs($userId))->first();
        } else {
            $username = DB::table('user_credentials')->where('pk', $userId)->value('user_name');
            $row = ($username !== null && trim((string) $username) !== '')
                ? $query->where('user_id', trim((string) $username))->first()
                : null;
        }

        return $this->rosterRowCache[$userId] = $row;
    }
}
