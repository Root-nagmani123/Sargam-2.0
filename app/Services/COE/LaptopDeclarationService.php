<?php

namespace App\Services\COE;

use App\Models\COE\LaptopResponse;
use App\Models\COE\ScriberMaster;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The one place a laptop status is validated and written.
 *
 * Three paths record the same answer — the administrator's "Add Single Record" dialog,
 * the bulk import behind it, and the Officer Trainee's own form. Keeping the rule and the
 * write here means they cannot drift into accepting different things or stamping
 * different audit columns.
 */
class LaptopDeclarationService
{
    /**
     * Validation for an answer, wherever it comes from.
     *
     * The reason is REQUIRED on a Yes and OPTIONAL on a No, and is kept either way.
     * A Yes has to justify itself — "Does not own a laptop", "Personal laptop not
     * working" — because somebody has to act on it. A No may still carry an explanation
     * ("I have my own PC") and that is worth keeping, but demanding one would block the
     * common case where there is nothing to say.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'laptop_required' => ['required', Rule::in(['0', '1'])],
            'reason' => ['nullable', 'string', 'max:500', 'required_if:laptop_required,1'],

            // The scriber half of the form. "OT with Disabilities" gates it: answer No and
            // the two controls are not on the page at all, so nothing is posted and
            // nothing is demanded. Answer Yes and the scriber question becomes compulsory,
            // and a Yes to THAT has to carry its remarks — the same shape as the laptop
            // answer and its reason, one level down.
            // Disability Type is per OT and arrives in the Scribers Details rows — see
            // scriberRules(); the caller hands each OT's type to record().
            'has_disability' => ['required', Rule::in(['0', '1'])],
            'scriber_required' => ['nullable', Rule::in(['0', '1']), 'required_if:has_disability,1'],
            'scriber_remarks' => ['nullable', 'string', 'max:500', 'required_if:scriber_required,1'],
            'additional_remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'laptop_required.required' => 'Please answer whether a laptop is required.',
            'reason.required_if' => 'Please give a reason when a laptop is required.',
            'has_disability.required' => 'Please answer whether the OT has a disability.',
            'scriber_required.required_if' => 'Please answer whether a scriber is required.',
            'scriber_remarks.required_if' => 'Please give the remarks for the scriber.',
        ];
    }

    /**
     * Write one answer, creating it or correcting the existing one.
     *
     * Keyed on the participant, which is a UNIQUE index — so a correction updates the
     * existing row rather than adding a second answer, and created_by / created_date are
     * stamped on the insert only, leaving "when did this trainee first answer" intact
     * through any number of corrections.
     *
     * ── Why the retry ──
     * find-then-insert is a read-then-write race: a trainee double-clicking Submit can get
     * two requests past the "does a row exist" check, and the second insert then hits the
     * unique key and 500s. Catching that collision and re-running as an update turns the
     * double click into what the user expected — one answer — instead of an error page.
     * One retry is enough: after a unique violation the row demonstrably exists.
     *
     * @param  array<string, mixed>  $validated
     */
    public function record(int $studentPk, array $validated, int $source): LaptopResponse
    {
        try {
            return $this->write($studentPk, $validated, $source);
        } catch (QueryException $e) {
            if (! str_contains($e->getMessage(), 'coe_lr_student_unique')) {
                throw $e;
            }

            return $this->write($studentPk, $validated, $source);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function write(int $studentPk, array $validated, int $source): LaptopResponse
    {
        $required = (int) $validated['laptop_required'] === LaptopResponse::LAPTOP_REQUIRED_YES;
        $now = now();
        $actor = Auth::id();

        $response = LaptopResponse::firstOrNew(['student_master_pk' => $studentPk]);

        if (! $response->exists) {
            $response->created_by = $actor;
            $response->created_date = $now;
        }

        // Kept on a No as well as a Yes: "I have my own PC" explains a No and the screens
        // show it. Only a genuinely blank box becomes NULL, so the grid's "—" means the
        // person said nothing rather than that the answer threw the reason away.
        $reason = trim((string) ($validated['reason'] ?? ''));

        $response->fill([
            'laptop_required' => $required,
            'reason' => $reason === '' ? null : $reason,
            'modified_by' => $actor,
            'modified_date' => $now,
        ]);

        // The scriber half is only written by the paths that ASK for it — the two forms.
        // The bulk import posts a laptop answer alone, and array_key_exists (not ??) is
        // what stops it blanking a disability answer the trainee already gave.
        if (array_key_exists('has_disability', $validated)) {
            $response->fill($this->scriberFields($validated));
        }

        // Same guard: only the dialog asks for it, so the import leaves it alone.
        if (array_key_exists('additional_remarks', $validated)) {
            $remarks = trim((string) $validated['additional_remarks']);
            $response->additional_remarks = $remarks === '' ? null : $remarks;
        }

        // Outside $fillable on purpose: who answered and how is the service's to set,
        // never the request's.
        $response->forceFill([
            'source' => $source,
            'responded_at' => $now,
            'responded_by' => $actor,
        ]);

        $response->save();

        return $response;
    }

    /**
     * The three scriber columns, derived from one form submission.
     *
     * No disability means the scriber question was never put, so both of its answers are
     * cleared rather than kept: a correction from Yes to No has to take the stale scriber
     * answer with it, or the seat plan reads a scriber request from someone who just said
     * they do not need one.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function scriberFields(array $validated): array
    {
        $hasDisability = (int) $validated['has_disability'] === LaptopResponse::HAS_DISABILITY_YES;

        if (! $hasDisability) {
            return [
                'has_disability' => false,
                'disability_type' => null,
                // NULL, not 0 — "never asked" is not the same answer as "asked, said No".
                'scriber_required' => null,
                'scriber_remarks' => null,
                'primary_scriber_pk' => null,
                'secondary_scriber_pk' => null,
            ];
        }

        $scriberRequired = (int) ($validated['scriber_required'] ?? LaptopResponse::LAPTOP_REQUIRED_NO) === 1;
        // Kept on a No as well, exactly like `reason` above: "OT will write themselves"
        // explains a No and the screens show it.
        $remarks = trim((string) ($validated['scriber_remarks'] ?? ''));

        $fields = [
            'has_disability' => true,
            // Supplied per OT by the caller, from that OT's Scribers Details row.
            'disability_type' => isset($validated['disability_type']) ? (int) $validated['disability_type'] : null,
            'scriber_required' => $scriberRequired,
            'scriber_remarks' => $remarks === '' ? null : $remarks,
        ];

        // No scriber needed, so none assigned: a Yes → No correction takes the
        // assignment with it, the same way it takes the remarks.
        if (! $scriberRequired) {
            $fields['primary_scriber_pk'] = null;
            $fields['secondary_scriber_pk'] = null;
        }

        return $fields;
    }

    /**
     * Validation for the dialog's Scribers Details rows.
     *
     * Every row carries its OT's Disability Type. Each picked scriber must hold the role
     * of the slot it is put in — a Secondary in the Primary slot would read back into a
     * dropdown that cannot show it.
     *
     * @return array<string, mixed>
     */
    public function scriberRules(): array
    {
        return [
            'scribers_present' => ['nullable', Rule::in(['1'])],
            'scribers' => ['nullable', 'array'],
            'scribers.*.student_master_pk' => ['required', 'integer', 'distinct'],
            'scribers.*.disability_type' => ['required', Rule::in(array_map('strval', array_keys(LaptopResponse::disabilityTypes())))],
            'scribers.*.primary_scriber_pk' => ['nullable', 'integer',
                Rule::exists('coe_scriber_master', 'pk')->where('scriber_role', ScriberMaster::ROLE_PRIMARY)],
            'scribers.*.secondary_scriber_pk' => ['nullable', 'integer',
                Rule::exists('coe_scriber_master', 'pk')->where('scriber_role', ScriberMaster::ROLE_SECONDARY)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function scriberMessages(): array
    {
        return [
            'scribers.*.student_master_pk.distinct' => 'An OT appears twice in Scribers Details.',
            'scribers.*.disability_type.required' => 'Select the disability type for every OT in Scribers Details.',
            'scribers.*.disability_type.in' => 'Select a valid disability type in Scribers Details.',
            'scribers.*.primary_scriber_pk.exists' => 'A Primary Scriber picked is not a Primary scriber.',
            'scribers.*.secondary_scriber_pk.exists' => 'A Secondary Scriber picked is not a Secondary scriber.',
        ];
    }

    /**
     * Write the Scribers Details table for one course: each row's Disability Type and
     * scribers.
     *
     * The table is the whole picture for the course's OTs with disabilities: it opens
     * with every OT already assigned, so an OT that is not in it was removed, and loses
     * its assignment. An OT that is not on the course, or has no disability on record,
     * cannot be listed at all; one that does not need a scriber keeps its type but gets
     * no scribers, whatever was posted.
     *
     * Run after the dialog's own OTs have been recorded, so their fresh answers are
     * what decide whether they qualify.
     *
     * @param  array<int, array<string, mixed>>  $rows
     *
     * @throws ValidationException
     */
    public function assignScribers(int $courseId, array $rows): void
    {
        // student_master_pk => scriber_required, for the course's OTs with disabilities.
        $eligible = LaptopResponse::query()
            ->join('student_master', 'student_master.pk', '=', 'coe_laptop_responses.student_master_pk')
            ->where('student_master.course_master_pk', $courseId)
            ->where('coe_laptop_responses.has_disability', 1)
            ->pluck('coe_laptop_responses.scriber_required', 'coe_laptop_responses.student_master_pk')
            ->mapWithKeys(fn ($required, $pk) => [(int) $pk => (int) $required === 1])
            ->all();

        $listed = [];
        foreach ($rows as $row) {
            $studentPk = (int) $row['student_master_pk'];
            if (! array_key_exists($studentPk, $eligible)) {
                throw ValidationException::withMessages([
                    'scribers' => ['An OT in Scribers Details is not on this course or has no disability recorded.'],
                ]);
            }
            $listed[] = $studentPk;
            $scribes = $eligible[$studentPk];

            LaptopResponse::where('student_master_pk', $studentPk)->update([
                'disability_type' => (int) $row['disability_type'],
                'primary_scriber_pk' => $scribes ? ($row['primary_scriber_pk'] ?? null) : null,
                'secondary_scriber_pk' => $scribes ? ($row['secondary_scriber_pk'] ?? null) : null,
                'modified_by' => Auth::id(),
                'modified_date' => now(),
            ]);
        }

        $eligible = array_keys($eligible);

        $removed = array_diff($eligible, $listed);
        if ($removed !== []) {
            LaptopResponse::whereIn('student_master_pk', $removed)
                ->where(fn ($q) => $q->whereNotNull('primary_scriber_pk')->orWhereNotNull('secondary_scriber_pk'))
                ->update([
                    'primary_scriber_pk' => null,
                    'secondary_scriber_pk' => null,
                    'modified_by' => Auth::id(),
                    'modified_date' => now(),
                ]);
        }
    }
}
