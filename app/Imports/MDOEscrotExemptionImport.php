<?php

namespace App\Imports;

use App\Models\{MDOEscotDutyMap, StudentMaster, StudentMasterCourseMap, ClassSessionMaster};
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Bulk import for MDO/Escort Exemption duty assignments.
 *
 * Shared values (course, duty type, faculty, remark) are chosen in the form and
 * passed to the constructor. Each Excel row provides: Name, OT Code, Date, Session.
 *  - OT Code  -> resolved to a student enrolled in the selected course.
 *  - Date     -> mdo_date.
 *  - Session  -> matched to class_session_master.shift_name to derive Time_from/Time_to.
 */
class MDOEscrotExemptionImport implements ToCollection, WithHeadingRow
{
    private int $importedCount = 0;
    private int $skippedCount = 0;
    private array $errors = [];

    /** @var array<int, array{record: \App\Models\MDOEscotDutyMap, student_id: int}> */
    private array $insertedRecords = [];

    /** OT code (upper-cased) => ['pk' => student_master_pk, 'name' => display_name], for the selected course. */
    private array $courseOtMap = [];

    /** shift_name (lower-cased) => ['from' => H:i:s, 'to' => H:i:s]. */
    private array $sessionMap = [];

    public function __construct(
        private int $coursePk,
        private int $dutyTypePk,
        private ?int $facultyPk = null,
        private ?string $remark = null
    ) {}

    public function collection(Collection $rows)
    {
        $this->preloadCourseStudents();
        $this->preloadSessions();

        foreach ($rows as $index => $row) {
            // +2 accounts for the heading row + 1-based indexing, matching the user's sheet.
            $rowNumber = $index + 2;

            try {
                $name    = $this->cleanString($row['name'] ?? null);
                $otCode  = $this->cleanString($row['ot_code'] ?? $row['otcode'] ?? null);
                $dateRaw = $row['date'] ?? null;
                $session = $this->cleanString($row['session'] ?? null);

                // Skip fully empty rows silently (trailing blanks in spreadsheets).
                if (empty($name) && empty($otCode) && empty($dateRaw) && empty($session)) {
                    continue;
                }

                if (empty($otCode)) {
                    $this->fail($rowNumber, 'OT Code is required.');
                    continue;
                }

                $entry = $this->courseOtMap[strtoupper($otCode)] ?? null;
                if (!$entry) {
                    $this->fail($rowNumber, "OT Code '{$otCode}' is not enrolled in the selected course.");
                    continue;
                }
                $studentId = $entry['pk'];

                // Name must be present and match the OT Code's enrolled student.
                if (empty($name)) {
                    $this->fail($rowNumber, "Name is required for OT Code '{$otCode}'.");
                    continue;
                }
                if (!$this->namesMatch($name, $entry['name'])) {
                    $this->fail($rowNumber, "Name '{$name}' does not match OT Code '{$otCode}' (expected '{$entry['name']}').");
                    continue;
                }

                $mdoDate = $this->parseDate($dateRaw);
                if (!$mdoDate) {
                    $this->fail($rowNumber, 'Date is missing or invalid (use DD-MM-YYYY).');
                    continue;
                }

                $times = $this->resolveSession($session);
                if (!$times) {
                    $this->fail($rowNumber, "Session '{$session}' could not be matched to a class session.");
                    continue;
                }

                // Skip only if this student already has a duty for the SAME course + date + time slot
                // (mirrors single-add exclusion). Different event times on the same day are allowed.
                $exists = MDOEscotDutyMap::where('course_master_pk', $this->coursePk)
                    ->where('selected_student_list', $studentId)
                    ->whereDate('mdo_date', $mdoDate)
                    ->where('Time_from', $times['from'])
                    ->where('Time_to', $times['to'])
                    ->exists();

                if ($exists) {
                    $this->fail($rowNumber, "OT Code '{$otCode}' already has a duty assigned on {$mdoDate} from {$times['from']} to {$times['to']}.");
                    continue;
                }

                $record = MDOEscotDutyMap::create([
                    'course_master_pk'         => $this->coursePk,
                    'mdo_duty_type_master_pk'  => $this->dutyTypePk,
                    'mdo_date'                 => $mdoDate,
                    'Time_from'                => $times['from'],
                    'Time_to'                  => $times['to'],
                    'Remark'                   => $this->remark,
                    'selected_student_list'    => $studentId,
                    'faculty_master_pk'        => $this->facultyPk,
                ]);

                $this->insertedRecords[] = ['record' => $record, 'student_id' => (int) $studentId];
                $this->importedCount++;
            } catch (\Throwable $e) {
                $this->fail($rowNumber, $e->getMessage());
                Log::error("MDO bulk import row {$rowNumber} failed: " . $e->getMessage());
            }
        }
    }

    private function preloadCourseStudents(): void
    {
        $studentIds = StudentMasterCourseMap::where('course_master_pk', $this->coursePk)
            ->where('active_inactive', 1)
            ->pluck('student_master_pk')
            ->all();

        if (empty($studentIds)) {
            return;
        }

        StudentMaster::whereIn('pk', $studentIds)
            ->whereNotNull('generated_OT_code')
            ->get(['pk', 'display_name', 'generated_OT_code'])
            ->each(function ($student) {
                $code = strtoupper(trim((string) $student->generated_OT_code));
                if ($code !== '') {
                    $this->courseOtMap[$code] = [
                        'pk'   => $student->pk,
                        'name' => (string) $student->display_name,
                    ];
                }
            });
    }

    private function preloadSessions(): void
    {
        ClassSessionMaster::where('active_inactive', 1)
            ->get(['shift_name', 'shift_time', 'start_time', 'end_time'])
            ->each(function ($session) {
                // Prefer the human-facing shift_time label (e.g. "09:00 to 10:25") so the
                // imported times match what the user sees when picking a session. The
                // start_time/end_time columns can drift out of sync with that label.
                $times = $this->parseRange($session->shift_time);

                if (!$times && $session->start_time && $session->end_time) {
                    $times = [
                        'from' => date('H:i:s', strtotime($session->start_time)),
                        'to'   => date('H:i:s', strtotime($session->end_time)),
                    ];
                }

                if (!$times) {
                    return;
                }

                foreach ([$session->shift_name, $session->shift_time] as $key) {
                    $key = strtolower(trim((string) $key));
                    if ($key !== '') {
                        $this->sessionMap[$key] = $times;
                    }
                }
            });
    }

    /**
     * Parse a "HH:MM to HH:MM" / "HH:MM - HH:MM" style range into Time_from/Time_to.
     */
    private function parseRange(?string $value): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach ([' to ', ' - ', '-', ' to', 'to '] as $sep) {
            if (strpos($value, $sep) !== false) {
                $parts = array_map('trim', explode($sep, $value, 2));
                if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
                    $from = strtotime($parts[0]);
                    $to   = strtotime($parts[1]);
                    if ($from !== false && $to !== false && $to > $from) {
                        return ['from' => date('H:i:s', $from), 'to' => date('H:i:s', $to)];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Resolve the "Session" cell to Time_from/Time_to.
     * Matches a class_session_master shift name/time, or a direct "HH:MM - HH:MM" range.
     */
    private function resolveSession(?string $session): ?array
    {
        if (empty($session)) {
            return null;
        }

        $session = trim($session);
        $key = strtolower($session);
        if (isset($this->sessionMap[$key])) {
            return $this->sessionMap[$key];
        }

        // Combined "Session 1 (06:00 to 14:00)" form produced by the template dropdown:
        // the time inside the parenthesis is authoritative, so parse it first. The
        // label (e.g. "Session 1") is just a friendly name and may not correspond to a
        // class_session_master row, so only fall back to a name lookup if no time is given.
        if (preg_match('/^(.*?)\s*\((.+)\)\s*$/', $session, $m)) {
            if ($range = $this->parseRange($m[2])) {
                return $range;
            }
            $namePart = strtolower(trim($m[1]));
            if ($namePart !== '' && isset($this->sessionMap[$namePart])) {
                return $this->sessionMap[$namePart];
            }
        }

        // Fallback: explicit time range like "10:00 to 11:00", "10:00-11:00", "10:00 AM - 11:00 AM".
        return $this->parseRange($session);
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Excel may deliver dates as serial numbers.
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                // fall through to string parsing
            }
        }

        $value = trim((string) $value);

        // Prefer day-first formats (DD-MM-YYYY / DD/MM/YYYY) to match the template.
        // strtotime() reads slash dates as US month-first, so parse these explicitly.
        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $value, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        $ts = strtotime($value);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    /**
     * Compare the sheet's Name against the OT code's student name.
     * Case-insensitive and whitespace-tolerant; both must be non-empty.
     */
    private function namesMatch(string $provided, string $expected): bool
    {
        $a = $this->normalizeName($provided);
        $b = $this->normalizeName($expected);

        return $a !== '' && $a === $b;
    }

    private function normalizeName(string $value): string
    {
        return preg_replace('/\s+/', ' ', strtolower(trim($value)));
    }

    private function cleanString($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function fail(int $rowNumber, string $message): void
    {
        $this->errors[] = "Row {$rowNumber}: {$message}";
        $this->skippedCount++;
    }

    public function getInsertedRecords(): array { return $this->insertedRecords; }
    public function getImportedCount(): int { return $this->importedCount; }
    public function getSkippedCount(): int { return $this->skippedCount; }
    public function getErrors(): array { return $this->errors; }
}
