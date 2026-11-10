<?php

namespace App\Imports\COE;

use App\Exports\COE\EvaluationMarksTemplateExport;
use App\Services\COE\ResultMarksService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Reads the Evaluation & Marks Entry template into the shape ResultMarksService::save()
 * takes. It writes nothing itself: the controller saves what it read as a draft,
 * through the same all-or-nothing path as the sheet's Save as Draft.
 *
 * A row is found on the sheet by OT Unique Code + Course + Subject / Component.
 *
 *   Absent                                   saved as AB, marks ignored
 *   Present (or blank) with Marks            saved as the marks
 *   no Marks and no Remarks                  skipped
 *   an approved row                          skipped — it is locked
 *   a row not on the sheet                   rejected
 */
class EvaluationMarksImport implements ToCollection, WithHeadingRow
{
    private const ABSENT = ['absent', 'ab', 'a'];

    private const PRESENT = ['present', 'p', ''];

    /** @var array<string, array<int, string>> column key => student pk => number | 'AB' */
    public array $marks = [];

    /** @var array<string, array<int, string>> */
    public array $remarks = [];

    /** @var array<string, int> "key|student" => spreadsheet row, to report save() errors by row */
    public array $rowOf = [];

    /** @var list<string> */
    public array $failures = [];

    public int $skipped = 0;

    public int $locked = 0;

    /** @var array<string, list<array>> match key => sheet entries */
    private array $index = [];

    /**
     * @param  list<array<string, mixed>>  $entries  every entry of the sheet on screen, approved ones too
     */
    public function __construct(array $entries)
    {
        foreach ($entries as $e) {
            $this->index[self::match($e['code'], $e['course'], EvaluationMarksTemplateExport::label($e))][] = $e;
        }
    }

    private static function match(string $code, string $course, string $label): string
    {
        $norm = fn (string $s) => mb_strtolower(preg_replace('/\s+/u', ' ', trim($s)));

        return $norm($code).'|'.$norm($course).'|'.$norm($label);
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            $this->failures[] = 'The file has no data rows.';

            return;
        }

        foreach ($rows as $i => $row) {
            $line = $i + 2;
            // "Marks / 50" arrives as marks_50; take whichever marks column the file has.
            $marksField = $row->keys()->first(fn ($k) => str_starts_with((string) $k, 'marks'));
            $code = trim((string) ($row['ot_unique_code'] ?? ''));
            $attendance = mb_strtolower(trim((string) ($row['attendance'] ?? '')));
            $marks = trim((string) ($marksField === null ? '' : ($row[$marksField] ?? '')));
            $note = trim((string) ($row['remarks'] ?? ''));

            if ($code === '' && $marks === '' && $note === '') {
                continue;
            }

            $found = $this->index[self::match($code, (string) ($row['course'] ?? ''), (string) ($row['subject_component'] ?? ''))] ?? [];
            if (count($found) !== 1) {
                $this->failures[] = $found === []
                    ? "Row {$line}: {$code} with this Course and Subject / Component is not on your sheet. Download the template again."
                    : "Row {$line}: {$code} matches more than one row of your sheet. Enter these marks on the screen.";

                continue;
            }
            $entry = $found[0];
            if ($entry['mark']?->isApproved()) {
                $this->locked++;

                continue;
            }

            if (in_array($attendance, self::ABSENT, true)) {
                $value = 'AB';
            } elseif (! in_array($attendance, self::PRESENT, true)) {
                $this->failures[] = "Row {$line}: Attendance must be Present or Absent.";

                continue;
            } elseif ($marks === '' && $note === '') {
                $this->skipped++;

                continue;
            } else {
                $value = $marks;
            }

            $cell = $entry['key'].'|'.$entry['student'];
            if (isset($this->rowOf[$cell])) {
                $this->failures[] = "Row {$line}: repeats row {$this->rowOf[$cell]}.";

                continue;
            }

            $this->rowOf[$cell] = $line;
            $this->marks[$entry['key']][$entry['student']] = $value;
            if (ResultMarksService::remarksEnabled()) {
                $this->remarks[$entry['key']][$entry['student']] = $note;
            }
        }
    }
}
