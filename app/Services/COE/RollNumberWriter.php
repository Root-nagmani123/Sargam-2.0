<?php

namespace App\Services\COE;

use App\Models\COE\RollNumber;
use App\Models\COE\RollNumberAllocation;
use Illuminate\Support\Facades\Auth;

/**
 * Writes a roll number that an administrator supplied by hand.
 *
 * Two screens do this — the bulk import, and Add Single Record — and both must attach the
 * number to the same allocation, derive the same serial and keep participant_count
 * honest. Keeping it here means they cannot drift into doing it differently.
 *
 * Generate does NOT come through here: it writes a whole run at once from
 * RollNumberAllocator, which is a different shape of problem.
 */
class RollNumberWriter
{
    /** Separators a hand-typed roll number is allowed to use between prefix and serial. */
    private const SEPARATORS = ['-', '/', '_'];

    /**
     * Per-instance caches, so a bulk import does not re-ask the same questions per row.
     *
     * A 2,700-row upload was costing an allocation lookup, a serial-collision check and a
     * participant recount for EVERY row — the queries-inside-a-loop the guidelines call
     * out (G5). Resolving each of those once per allocation instead takes the import from
     * roughly seven queries a row to two.
     *
     * The caches live only as long as the service instance, which Laravel resolves fresh
     * per request, so nothing can go stale between uploads.
     *
     * @var array<string, RollNumberAllocation>
     */
    private array $allocationCache = [];

    /** @var array<int, array<int, true>> allocation pk => serials already in use */
    private array $serialCache = [];

    /** @var array<int, RollNumberAllocation> allocations whose count still needs writing */
    private array $pendingCounts = [];

    /**
     * Give one participant a roll number, creating it or correcting the existing one.
     *
     * @param  object  $participant  needs pk, course_master_pk, batch
     * @param  bool  $deferCount  a bulk caller passes true and calls flushCounts() once at
     *                            the end, instead of recounting the allocation per row
     */
    public function assign(object $participant, string $rollNumber, ?RollNumber $existing = null, bool $deferCount = false): RollNumber
    {
        $rollNumber = trim($rollNumber);
        $parsed = $this->parse($rollNumber);
        $allocation = $this->resolveAllocation((int) $participant->course_master_pk, (int) $participant->batch, $parsed);

        $record = $existing ?? RollNumber::firstOrNew(['student_master_pk' => (int) $participant->pk]);
        $now = now();
        $actor = Auth::id();

        if (! $record->exists) {
            $record->created_by = $actor;
            $record->created_date = $now;
        }

        $record->fill([
            'roll_number_allocation_pk' => $allocation->pk,
            'student_master_pk' => (int) $participant->pk,
            'roll_number' => $rollNumber,
            // The serial is what the doc's faculty ranges ("Roll Numbers 001-010") are cut
            // from, so it follows the number the administrator typed wherever it can.
            'serial_number' => $this->resolveSerial($allocation, $parsed, $record),
            'modified_by' => $actor,
            'modified_date' => $now,
        ])->save();

        $this->rememberSerial($allocation, (int) $record->serial_number);

        if ($deferCount) {
            $this->pendingCounts[(int) $allocation->pk] = $allocation;
        } else {
            $this->refreshParticipantCount($allocation);
        }

        return $record;
    }

    /**
     * Write the counts a deferred run has been accumulating.
     *
     * Safe to call when nothing is pending, so a caller never has to check first.
     */
    public function flushCounts(): void
    {
        foreach ($this->pendingCounts as $allocation) {
            $this->refreshParticipantCount($allocation);
        }

        $this->pendingCounts = [];
    }

    /**
     * Split a roll number into the pieces an allocation stores.
     *
     * "FC101-0012" gives prefix FC101, separator "-", serial 12, padding 4. A number with
     * no trailing digits gives a null serial, and the caller falls back to the next free
     * one rather than inventing a position.
     *
     * @return array{prefix: string, separator: string, serial: int|null, padding: int}
     */
    public function parse(string $rollNumber): array
    {
        $rollNumber = trim($rollNumber);

        if (! preg_match('/^(.*?)(\d+)$/', $rollNumber, $m)) {
            return ['prefix' => $rollNumber, 'separator' => '-', 'serial' => null, 'padding' => 3];
        }

        $head = $m[1];
        $digits = $m[2];
        $separator = '-';

        $lastChar = substr($head, -1);
        if ($lastChar !== '' && in_array($lastChar, self::SEPARATORS, true)) {
            $separator = $lastChar;
            $head = substr($head, 0, -1);
        }

        return [
            'prefix' => $head,
            'separator' => $separator,
            // Leading zeros are the format, so the padding is the digit COUNT, not the value.
            'serial' => (int) $digits,
            'padding' => strlen($digits),
        ];
    }

    /**
     * The allocation a hand-written roll number belongs to.
     *
     * A course+batch that has never been generated has no allocation row, and
     * coe_roll_numbers.roll_number_allocation_pk is NOT NULL — so one is created from
     * whatever the first roll number looks like. An allocation that already exists is
     * left exactly as it is: its prefix and starting number describe the run that
     * produced it, and a single manual addition should not rewrite that history.
     *
     * @param  array{prefix: string, separator: string, serial: int|null, padding: int}  $parsed
     */
    public function resolveAllocation(int $courseId, int $batch, array $parsed): RollNumberAllocation
    {
        $key = $courseId . ':' . $batch;
        if (isset($this->allocationCache[$key])) {
            return $this->allocationCache[$key];
        }

        $allocation = RollNumberAllocation::where('course_master_pk', $courseId)
            ->where('batch', $batch)
            ->first();

        if ($allocation !== null) {
            return $this->allocationCache[$key] = $allocation;
        }

        $created = RollNumberAllocation::create([
            'course_master_pk' => $courseId,
            'batch' => $batch,
            'prefix' => $parsed['prefix'] !== '' ? mb_substr($parsed['prefix'], 0, 20) : 'MANUAL',
            'start_number' => max(1, (int) ($parsed['serial'] ?? 1)),
            'number_padding' => max(1, (int) $parsed['padding']),
            'strategy' => RollNumberAllocation::STRATEGY_ALLOCATION_RULES,
            'generated_at' => now(),
            'created_by' => Auth::id(),
            'created_date' => now(),
        ]);

        // number_separator comes from a column default, so it is not on the model yet —
        // and formatRollNumber() reads it.
        return $this->allocationCache[$key] = $created->refresh();
    }

    /**
     * The serial to store against this roll number.
     *
     * Prefers the digits the administrator typed, because that is what makes a printed
     * list read in order. Falls back to the next free position when the number has no
     * digits, or when those digits are already taken by somebody else in the same run —
     * two participants sharing a serial would make the faculty ranges ambiguous.
     *
     * @param  array{prefix: string, separator: string, serial: int|null, padding: int}  $parsed
     */
    private function resolveSerial(RollNumberAllocation $allocation, array $parsed, RollNumber $record): int
    {
        $used = $this->usedSerials($allocation);

        // The row's own serial is not a collision with itself.
        $own = $record->exists ? (int) $record->serial_number : null;
        $wanted = $parsed['serial'];

        if ($wanted !== null && $wanted > 0 && (! isset($used[$wanted]) || $wanted === $own)) {
            return $wanted;
        }

        $next = ($used === [] ? 0 : max(array_keys($used))) + 1;

        return $next;
    }

    /**
     * Serials already taken inside one allocation, loaded once per allocation.
     *
     * @return array<int, true>
     */
    private function usedSerials(RollNumberAllocation $allocation): array
    {
        $key = (int) $allocation->pk;

        if (! isset($this->serialCache[$key])) {
            $this->serialCache[$key] = RollNumber::where('roll_number_allocation_pk', $key)
                ->pluck('serial_number')
                ->mapWithKeys(fn ($serial) => [(int) $serial => true])
                ->all();
        }

        return $this->serialCache[$key];
    }

    private function rememberSerial(RollNumberAllocation $allocation, int $serial): void
    {
        $key = (int) $allocation->pk;
        $this->serialCache[$key] ??= [];
        $this->serialCache[$key][$serial] = true;
    }

    /**
     * Keep the allocation's headline count in step with the rows under it.
     *
     * One aggregate, and only written when it actually changed.
     */
    public function refreshParticipantCount(RollNumberAllocation $allocation): void
    {
        $count = (int) RollNumber::where('roll_number_allocation_pk', $allocation->pk)->count();

        if ($count !== (int) $allocation->participant_count) {
            $allocation->fill(['participant_count' => $count])->save();
        }
    }
}
