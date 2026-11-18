<?php

namespace App\Services\COE;

use App\Models\COE\OmrResponse;
use Generator;
use RuntimeException;

/**
 * Reads the CSV an OMR scanner exports — one line per answer sheet:
 *
 *     NCS Header, BARCODE, ENR_NO, BOOKLET, ANS1 … ANSn [, SCORE]
 *
 * Both exports seen so far are handled: with a heading line ("NCS Header",
 * "BARCODE", …) and without one, and with or without a trailing score column
 * ("0050", "-002"). Without a heading the score column is recognised by the
 * lines ending in a whole number, which an answer cell never is. The score is
 * kept exactly as the scanner wrote it, negative included.
 *
 * Files run to tens of MB, so nothing holds the whole file: inspect() streams it
 * twice to check it, rows() streams it once more to hand over the sheets. Only
 * counters, the first few problems and (in rows()) the values seen so far for
 * duplicate checks are kept in memory.
 *
 * Two kinds of check:
 *
 *   Refused — the file is not a scanner export, or is damaged: ragged lines, a
 *   heading that does not name the expected columns, a character the scanner
 *   never writes (this also keeps "=", "+", "@" … out, so nothing in the stored
 *   values can run as a formula when the grid is downloaded to Excel), an answer
 *   or booklet that is not an option, a score that is not a whole number. Nothing
 *   is imported, and the lines are listed.
 *
 *   Flagged — the file is fine but one sheet was not read cleanly: blank or
 *   partly unread ('*') barcode / roll no. / booklet, a value whose length
 *   differs from the rest of the file, a value seen twice, no answer marked, the
 *   score missing. The sheet is imported with has_error and the reasons, so COE
 *   can see and correct it.
 */
class OmrCsvParser
{
    public const MAX_ROWS = 200000;

    public const MAX_QUESTIONS = 500;

    /** Options a bubble can carry. OMR answer sheets print at most A–E. */
    public const OPTIONS = 'ABCDE';

    /** NCS Header, BARCODE, ENR_NO, BOOKLET. */
    private const FIXED_COLUMNS = 4;

    private const MAX_REPORTED_PROBLEMS = 10;

    /** [label, max length, allowed characters] of the fixed columns. */
    private const FIXED = [
        'header' => ['NCS Header', 100, '/^[0-9A-Za-z *<>.\/\-]*$/'],
        'barcode' => ['Barcode', 30, '/^[0-9A-Za-z*]*$/'],
        // A roll number may be typed with a separator (RollNumberWriter: - / _), e.g. FC101-001.
        'roll' => ['Roll No.', 50, '/^[0-9A-Za-z*\/_-]*$/'],
    ];

    /** Shown in the error when a fixed column is headed wrongly. */
    private const HEADING_LABELS = [1 => 'BARCODE', 2 => 'ENR_NO / ROLL_NO', 3 => 'BOOKLET'];

    /** Accepted heading names of the fixed columns (upper case, spaces and _ removed). */
    private const HEADINGS = [
        1 => ['BARCODE'],
        2 => ['ENRNO', 'ROLLNO', 'ROLLNUMBER', 'ENROLLMENTNO', 'ENROLMENTNO'],
        3 => ['BOOKLET', 'BOOKET', 'BOOKLETNO', 'BOOKLETSERIES', 'SERIES', 'SET'],
    ];

    private const SCORE_PATTERN = '/^[+-]?\d{1,5}$/';

    /** Problems of the current pass: the first few messages, and how many in all. */
    private array $problems = [];

    private int $problemCount = 0;

    /**
     * Checks the whole file without keeping it.
     *
     * @return array{question_count:int, has_score:bool, total:int, heading_line:?int, usual:array{barcode:?int, roll_number:?int}}
     *
     * @throws RuntimeException with a message an administrator can act on
     */
    public function inspect(string $path): array
    {
        // Pass 1: shape — heading, column count, which kind of value ends each line,
        // and how long the numeric barcodes / roll numbers usually are.
        $this->resetProblems();
        $heading = null;
        $headingLine = null;
        $width = null;
        $total = 0;
        $last = ['numeric' => [], 'other' => [], 'numeric_count' => 0, 'other_count' => 0];
        $lengths = ['barcode' => [], 'roll_number' => []];

        foreach ($this->records($path) as $record) {
            $cells = $record['cells'];
            if ($width === null) {
                $width = count($cells);
                if ($this->isHeading($cells)) {
                    $heading = $cells;
                    $headingLine = $record['line'];

                    continue;
                }
            }

            if (++$total > self::MAX_ROWS) {
                throw new RuntimeException('The file has more than ' . number_format(self::MAX_ROWS) . ' answer sheets; split it and upload the parts.');
            }
            if (count($cells) !== $width) {
                $this->problem('Line ' . $record['line'] . ' has ' . count($cells) . ' columns; expected ' . $width . '.');

                continue;
            }

            $end = (string) end($cells);
            if (preg_match(self::SCORE_PATTERN, $end) === 1) {
                $last['numeric_count']++;
                if (count($last['numeric']) < self::MAX_REPORTED_PROBLEMS) {
                    $last['numeric'][] = $record['line'];
                }
            } elseif ($end !== '') {
                $last['other_count']++;
                if (count($last['other']) < self::MAX_REPORTED_PROBLEMS) {
                    $last['other'][] = $record['line'];
                }
            }

            foreach (['barcode' => 1, 'roll_number' => 2] as $key => $i) {
                if (preg_match('/^\d+$/', $cells[$i] ?? '') === 1) {
                    $len = strlen($cells[$i]);
                    $lengths[$key][$len] = ($lengths[$key][$len] ?? 0) + 1;
                }
            }
        }

        if ($width === null) {
            throw new RuntimeException('The file is empty.');
        }
        if ($total === 0) {
            throw new RuntimeException('The file has a heading line but no answer sheets.');
        }
        $this->refuseIfProblems();

        $hasScore = $heading ? $this->headingHasScore($heading) : $this->dataHasScore($last);
        $questionCount = $width - self::FIXED_COLUMNS - ($hasScore ? 1 : 0);
        if ($questionCount < 1) {
            throw new RuntimeException('Expected NCS Header, Barcode, Roll No. (ENR_NO), Booklet and at least one answer column; found ' . $width . ' columns.');
        }
        if ($questionCount > self::MAX_QUESTIONS) {
            throw new RuntimeException('The file has ' . $questionCount . ' answer columns; at most ' . self::MAX_QUESTIONS . ' are supported.');
        }
        if ($heading) {
            $this->checkHeading($heading, $questionCount, $hasScore);
        }

        // Pass 2: every value of every line.
        $this->resetProblems();
        foreach ($this->records($path) as $record) {
            if ($record['line'] !== $headingLine) {
                $this->row($record['cells'], $record['line'], $questionCount, $hasScore);
            }
        }
        $this->refuseIfProblems();

        return [
            'question_count' => $questionCount,
            'has_score' => $hasScore,
            'total' => $total,
            'heading_line' => $headingLine,
            'usual' => ['barcode' => $this->usualLength($lengths['barcode']), 'roll_number' => $this->usualLength($lengths['roll_number'])],
        ];
    }

    /**
     * The answer sheets of a file inspect() accepted, one at a time, each with its
     * issues — duplicates within the file and odd lengths included.
     *
     * @param  array  $meta  what inspect() returned for this file
     * @return Generator<int, array<string, mixed>>
     */
    public function rows(string $path, array $meta): Generator
    {
        $this->resetProblems();
        $firstLine = ['ncs_header' => [], 'barcode' => [], 'roll_number' => []];
        $labels = ['ncs_header' => 'NCS Header', 'barcode' => 'Barcode', 'roll_number' => 'Roll No.'];

        foreach ($this->records($path) as $record) {
            if ($record['line'] === $meta['heading_line']) {
                continue;
            }
            $row = $this->row($record['cells'], $record['line'], $meta['question_count'], $meta['has_score']);
            if ($row === null) {
                // The file changed after inspect() — never import half a file.
                throw new RuntimeException('The file changed while it was being read. Upload it again.');
            }

            foreach ($labels as $key => $label) {
                $value = $row[$key];
                if ($value === null || str_contains($value, '*')) {
                    continue;
                }
                if (isset($firstLine[$key][$value])) {
                    $row['issues'][] = 'Duplicate ' . $label . ' (also on line ' . $firstLine[$key][$value] . ')';
                } else {
                    $firstLine[$key][$value] = $row['row_no'];
                }
            }
            foreach (['barcode' => 'Barcode', 'roll_number' => 'Roll No.'] as $key => $label) {
                $usual = $meta['usual'][$key];
                if ($usual !== null && $row[$key] !== null && preg_match('/^\d+$/', $row[$key]) === 1 && strlen($row[$key]) !== $usual) {
                    $row['issues'][] = $label . ' has ' . strlen($row[$key]) . ' digits; most sheets have ' . $usual;
                }
            }

            yield $row;
        }
    }

    /** @return Generator<int, array{line:int, cells:list<string>}> non-empty records, 1-based line numbers */
    private function records(string $path): Generator
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('The file could not be opened.');
        }

        $line = 0;
        try {
            while (($cells = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $line++;
                if ($cells === [null] || $cells === []) {
                    continue; // blank line
                }
                if ($line === 1 && isset($cells[0])) {
                    $cells[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cells[0]); // UTF-8 BOM
                }
                foreach ($cells as $cell) {
                    if (! mb_check_encoding((string) $cell, 'UTF-8') || preg_match('/[\x00-\x08\x0B-\x1F\x7F]/', (string) $cell) === 1) {
                        throw new RuntimeException('Line ' . $line . ' is not readable text. Upload the CSV exactly as the OMR software exported it.');
                    }
                }
                $cells = array_map(fn ($c) => trim((string) $c), $cells);
                if (implode('', $cells) === '') {
                    continue; // a line of empty cells, e.g. ",,,," left by a spreadsheet
                }
                yield ['line' => $line, 'cells' => $cells];
            }
        } finally {
            fclose($handle);
        }
    }

    /** The first line is a heading when its Barcode cell says so (a real barcode is digits). */
    private function isHeading(array $cells): bool
    {
        return strcasecmp($cells[1] ?? '', 'BARCODE') === 0
            || stripos($cells[0] ?? '', 'header') !== false
            || preg_match('/^ANS\s*_?1$/i', $cells[self::FIXED_COLUMNS] ?? '') === 1;
    }

    private function headingHasScore(array $cells): bool
    {
        $last = (string) end($cells);

        return preg_match('/^ANS/i', $last) !== 1 && preg_match('/score|mark|total|result/i', $last) === 1;
    }

    /**
     * Every line ends in a whole number. A file where only some lines do is
     * refused — those lines lost their score, or a column was shifted.
     */
    private function dataHasScore(array $last): bool
    {
        if ($last['numeric_count'] > 0 && $last['other_count'] > 0) {
            $fewer = $last['other_count'] <= $last['numeric_count'] ? $last['other'] : $last['numeric'];
            $more = max(0, min($last['other_count'], $last['numeric_count']) - count($fewer));
            throw new RuntimeException('The file was not imported. The last column holds a score on some lines and an answer on others (lines '
                . implode(', ', $fewer) . ($more > 0 ? ' …and ' . $more . ' more' : '')
                . '). Export the file again from the OMR software.');
        }

        return $last['numeric_count'] > 0;
    }

    /** The heading must name the columns in the expected order: Barcode, Roll No. (ENR_NO), Booklet, ANS1 … ANSn [, Score]. */
    private function checkHeading(array $cells, int $questionCount, bool $hasScore): void
    {
        $this->resetProblems();
        $key = fn (string $name) => strtoupper(preg_replace('/[\s_.\-]+/', '', $name));

        foreach (self::HEADINGS as $i => $accepted) {
            if (! in_array($key($cells[$i]), $accepted, true)) {
                $this->problem('Column ' . ($i + 1) . ' is headed "' . mb_substr($cells[$i], 0, 20) . '"; expected ' . self::HEADING_LABELS[$i] . '.');
            }
        }
        for ($q = 1; $q <= $questionCount; $q++) {
            $name = $cells[self::FIXED_COLUMNS + $q - 1];
            if ($key($name) !== 'ANS' . $q) {
                $this->problem('Column ' . (self::FIXED_COLUMNS + $q) . ' is headed "' . mb_substr($name, 0, 20) . '"; expected ANS' . $q . '.');
            }
        }

        if ($this->problemCount > 0) {
            array_unshift($this->problems, 'The heading line does not match an OMR export.');
            $this->refuseIfProblems();
        }
    }

    /** One answer sheet, or null when its line is malformed (the reason is recorded as a problem). */
    private function row(array $cells, int $line, int $questionCount, bool $hasScore): ?array
    {
        [$header, $barcode, $roll, $booklet] = array_slice($cells, 0, self::FIXED_COLUMNS);
        $barcode = strtoupper($barcode);
        $roll = strtoupper($roll);
        $booklet = strtoupper($booklet);
        $before = $this->problemCount;

        foreach (['header' => $header, 'barcode' => $barcode, 'roll' => $roll] as $field => $value) {
            [$label, $max, $pattern] = self::FIXED[$field];
            if (mb_strlen($value) > $max) {
                $this->problem('Line ' . $line . ': ' . $label . ' is longer than ' . $max . ' characters.');
            } elseif (preg_match($pattern, $value) !== 1) {
                $this->problem('Line ' . $line . ': ' . $label . ' "' . mb_substr($value, 0, 20) . '" has characters an OMR scanner does not write.');
            }
        }
        if ($booklet !== '' && $booklet !== OmrResponse::MULTI_MARKED && ! $this->isOption($booklet, 'A-Z')) {
            $this->problem('Line ' . $line . ': booklet "' . mb_substr($booklet, 0, 10) . '" is not a single letter.');
        }

        $answers = [];
        $attempted = $blank = $multi = 0;
        foreach (array_slice($cells, self::FIXED_COLUMNS, $questionCount) as $i => $value) {
            $value = strtoupper($value);
            if ($value === OmrResponse::BLANK) {
                $blank++;
            } elseif ($value === OmrResponse::MULTI_MARKED) {
                $multi++;
            } elseif ($this->isOption($value, self::OPTIONS)) {
                $attempted++;
            } else {
                $this->problem('Line ' . $line . ', question ' . ($i + 1) . ': "' . mb_substr($value, 0, 10) . '" is not an option ('
                    . implode(', ', str_split(self::OPTIONS)) . ', blank or *).');
            }
            $answers[] = $value;
        }

        $score = null;
        if ($hasScore) {
            $raw = (string) end($cells);
            if (preg_match(self::SCORE_PATTERN, $raw) === 1 && abs((int) $raw) <= 32767) {
                $score = (int) $raw;
            } elseif ($raw !== '') {
                $this->problem('Line ' . $line . ': score "' . mb_substr($raw, 0, 10) . '" is not a whole number.');
            }
        }

        if ($this->problemCount > $before) {
            return null;
        }

        $issues = [];
        if ($header === '') {
            $issues[] = 'NCS Header blank';
        }
        $this->checkNumber($barcode, 'Barcode', $issues);
        // A roll number need not be all digits (FC101-001), so only blank / unread is flagged.
        if ($roll === '') {
            $issues[] = 'Roll No. blank';
        } elseif (str_contains($roll, '*')) {
            $issues[] = 'Roll No. not read clearly (*)';
        }
        if ($booklet === '') {
            $issues[] = 'Booklet series blank';
        } elseif ($booklet === OmrResponse::MULTI_MARKED) {
            $issues[] = 'Booklet series multi-marked (*)';
        } elseif (! $this->isOption($booklet, self::OPTIONS)) {
            $issues[] = 'Booklet series "' . $booklet . '" is not ' . implode('/', str_split(self::OPTIONS));
        }
        if ($attempted === 0 && $multi === 0) {
            $issues[] = 'No answer marked';
        }
        if ($hasScore && $score === null) {
            $issues[] = 'Score missing';
        }

        return [
            'row_no' => $line,
            'ncs_header' => $header === '' ? null : $header,
            'barcode' => $barcode === '' ? null : $barcode,
            'roll_number' => $roll === '' ? null : $roll,
            'booklet_series' => $booklet === '' ? null : $booklet,
            'answers' => $answers,
            'attempted_count' => $attempted,
            'blank_count' => $blank,
            'multi_marked_count' => $multi,
            'score' => $score,
            'issues' => $issues,
        ];
    }

    /** Barcode: digits as read, or a flag saying why not. */
    private function checkNumber(string $value, string $label, array &$issues): void
    {
        if ($value === '') {
            $issues[] = $label . ' blank';
        } elseif (str_contains($value, '*')) {
            $issues[] = $label . ' not read clearly (*)';
        } elseif (preg_match('/^\d+$/', $value) !== 1) {
            $issues[] = $label . ' is not numeric';
        }
    }

    /** One character out of $options ('ABCDE', or a range such as 'A-Z'). */
    private function isOption(string $value, string $options): bool
    {
        return preg_match('/^[' . $options . ']$/', $value) === 1;
    }

    /**
     * The length most numeric values have — a value of another length is a digit
     * missed or added by the scanner. Needs a clear majority (≥ 80 % of at least 5
     * values one length) to say what "right" is; otherwise null and nothing is flagged.
     *
     * @param  array<int, int>  $counts  length => how many values have it
     */
    private function usualLength(array $counts): ?int
    {
        $all = array_sum($counts);
        if ($all < 5) {
            return null;
        }
        arsort($counts);
        $usual = (int) array_key_first($counts);

        return $counts[$usual] / $all >= 0.8 ? $usual : null;
    }

    private function resetProblems(): void
    {
        $this->problems = [];
        $this->problemCount = 0;
    }

    private function problem(string $message): void
    {
        $this->problemCount++;
        if (count($this->problems) < self::MAX_REPORTED_PROBLEMS) {
            $this->problems[] = $message;
        }
    }

    private function refuseIfProblems(): void
    {
        if ($this->problemCount === 0) {
            return;
        }

        $more = $this->problemCount - count($this->problems);

        throw new RuntimeException('The file was not imported. ' . implode(' ', $this->problems)
            . ($more > 0 ? ' …and ' . $more . ' more.' : ''));
    }
}
