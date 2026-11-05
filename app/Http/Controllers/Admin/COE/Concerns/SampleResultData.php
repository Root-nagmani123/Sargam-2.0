<?php

namespace App\Http\Controllers\Admin\COE\Concerns;

/**
 * Sample results behind the design-first Merit & Ranking, Marksheet Generation and
 * Verified Marksheet screens. Result Compilation has moved to real marks
 * (coe_evaluation_marks, via App\Services\COE\ResultMarksService).
 *
 * Marks are derived from a hash of (course, participant, component), so every
 * screen shows the same numbers for the same OT on every request.
 *
 * shortcut: sample data only, replace with ResultMarksService::courseSheets() once
 * the merit rules (which subjects are Director Assessment, which are qualifying) are stored.
 */
trait SampleResultData
{
    /** @return array<string, array{name:string, short:string, batch:string, year:int, archived:bool}> */
    protected function resultCourses(): array
    {
        return [
            'FC-101' => ['name' => 'FC-101 · Foundation Course', 'short' => 'FC 101', 'batch' => '2026 Batch Phase-II', 'year' => 2026, 'archived' => false],
            'PH-IV-2025' => ['name' => 'Phase IV 2025', 'short' => 'Phase IV 2025', 'batch' => '2025 Batch Phase-IV', 'year' => 2025, 'archived' => false],
            'PH-III-2024' => ['name' => 'Phase III 2024', 'short' => 'Phase III 2024', 'batch' => '2024 Batch Phase-III', 'year' => 2024, 'archived' => true],
        ];
    }

    /**
     * Subject => [code, components [name => max]]. The written subjects add up to
     * Total (A) / 450; Director Assessment is (B) / 172.50.
     *
     * @return array<string, array{code:string, assessment:bool, components:array<string, float>}>
     */
    protected function resultSubjects(): array
    {
        return [
            'Public Administration' => ['code' => 'PA-101', 'assessment' => false, 'components' => [
                'Urban Assignment' => 30, 'IGOT Assessment' => 30, 'FSRP Group Work' => 30]],
            'Basic Economics for Administrators' => ['code' => 'ECO-101', 'assessment' => false, 'components' => [
                'Economics Objective' => 45, 'Economics Descriptive' => 45]],
            'Constitutional Law' => ['code' => 'LAW-101', 'assessment' => false, 'components' => [
                'Law' => 90]],
            'Political Concept & Constitution of India' => ['code' => 'POL-101', 'assessment' => false, 'components' => [
                'Ethics' => 30, 'Essay on Viksit Bharat' => 30]],
            'Indian History and Culture' => ['code' => 'HIS-101', 'assessment' => false, 'components' => [
                'Video Making on HST' => 10, 'F and PM' => 50]],
            'Management and Behavioural Science' => ['code' => 'MBS-101', 'assessment' => false, 'components' => [
                'Management & Behavioural' => 60]],
            'Director Assessment' => ['code' => 'DA-101', 'assessment' => true, 'components' => [
                'ICT' => 15, 'Journaling' => 30, 'Physical Fitness and Sports' => 20,
                'Participation in Extra Curricular Activities' => 20, 'Peer Evaluation' => 10,
                'Discipline' => 40, "Director's Overall" => 37.5]],
        ];
    }

    /** @return list<array{code:string, name:string, service:string}> */
    protected function resultParticipants(): array
    {
        $names = ['Ishita Rao', 'Aditya Sen', 'Rohan Das', 'Sana Qureshi', 'Vikram Joshi', 'Tara Singh', 'Arjun Pillai',
            'Aakriti Sethi', 'Aakip Khan', 'Harsh Chaurasiya', 'Jagrati Jain', 'Aamir Khan', 'Aakansha Anand',
            'Abhinav Dwivedi', 'Aakanksha Singh', 'Aashna Chaudhary', 'Priya Nair', 'Rishabh Verma', 'Aisha Khan',
            'Maya Patel'];
        $services = ['IAS', 'IAS', 'IPS', 'IFS', 'IRS(CCE)', 'IRMS'];

        return array_map(fn (string $name, int $i) => [
            'code' => sprintf('A%02d', $i + 1),
            'name' => $name,
            'service' => $services[$i % count($services)],
        ], $names, array_keys($names));
    }

    /** Roll number as printed on the marksheet, e.g. FC101-014. */
    protected function rollNumber(string $courseKey, int $index): string
    {
        // FC-101 → FC101, PH-IV-2025 → PHIV
        return str_replace('-', '', preg_replace('/-\d{4}$/', '', $courseKey)).'-'.sprintf('%03d', $index + 1);
    }

    /** One component mark, or null while it is still pending evaluation. */
    protected function componentMark(string $courseKey, string $otCode, string $component, float $max): ?float
    {
        $h = crc32($courseKey.'|'.$otCode.'|'.$component);
        // A few marks still awaiting the faculty, only on running courses.
        if (! $this->resultCourses()[$courseKey]['archived'] && $h % 97 === 0) {
            return null;
        }

        return round($max * (0.36 + ($h % 1000) / 1000 * 0.59), 2);
    }

    /**
     * Every participant's marks on a course, with subject totals and the merit figures.
     *
     * @return list<array<string, mixed>>
     */
    protected function resultSheet(string $courseKey): array
    {
        $subjects = $this->resultSubjects();
        $sheet = [];

        foreach ($this->resultParticipants() as $i => $p) {
            $row = $p + ['index' => $i, 'roll' => $this->rollNumber($courseKey, $i), 'marks' => [], 'subjects' => [], 'pending' => []];
            $written = 0.0;
            $pass = true;

            foreach ($subjects as $subject => $s) {
                $total = 0.0;
                foreach ($s['components'] as $component => $max) {
                    $mark = $this->componentMark($courseKey, $p['code'], $component, $max);
                    $row['marks'][$subject][$component] = $mark;
                    if ($mark === null) {
                        $row['pending'][] = $subject;
                    }
                    $total += $mark ?? 0;
                }
                $max = array_sum($s['components']);
                $row['subjects'][$subject] = round($total, 2);
                $pass = $pass && $total >= 0.4 * $max;
                if (! $s['assessment']) {
                    $written += $total;
                }
            }

            $row['pending'] = array_values(array_unique($row['pending']));
            $row['written'] = round($written, 2);
            $row['assessment'] = $row['subjects']['Director Assessment'];
            $row['grand'] = round($written + $row['assessment'], 2);
            $row['ict'] = round(50 * (0.55 + (crc32($courseKey.$p['code'].'ict') % 400) / 1000), 2);
            $row['result'] = $pass ? 'Pass' : 'Fail';
            $sheet[] = $row;
        }

        return $sheet;
    }

    protected function subjectMax(string $subject): float
    {
        return (float) array_sum($this->resultSubjects()[$subject]['components']);
    }

    /**
     * Marksheets of the fully evaluated OTs on a course, ranked by Grand Total.
     *
     * @return list<array<string, mixed>>
     */
    protected function marksheets(string $courseKey): array
    {
        $course = $this->resultCourses()[$courseKey];
        $types = ['Director Assessment', 'Offline MCQ', 'Online MCQ', 'Offline Descriptive'];

        $rows = array_values(array_filter($this->resultSheet($courseKey), fn ($r) => $r['pending'] === []));
        usort($rows, fn ($a, $b) => $b['grand'] <=> $a['grand']);

        foreach ($rows as $rank => &$r) {
            $r['rank'] = $rank + 1;
            $r['course_key'] = $courseKey;
            $r['course'] = $course['name'];
            $r['batch'] = $course['batch'];
            $r['exam_term'] = 'Final Term';
            $r['exam_type'] = $types[crc32($r['code']) % count($types)];
            $r['examination'] = 'Final Examination · '.$course['year'];
            $r['number'] = sprintf('MS/%s/%d/%04d', str_replace('-', '', $courseKey), $course['year'], $r['index'] + 1);
            $r['generated_on'] = sprintf('%02d Sep %d', 10 + $r['index'] % 5, $course['year']);
            // Every third marksheet has been through Verify & Publish.
            $r['published'] = $course['archived'] || $r['index'] % 3 === 0;
        }

        return $rows;
    }

    /** One marksheet by its course and OT code, or 404. */
    protected function findMarksheet(string $courseKey, string $otCode): array
    {
        abort_unless(isset($this->resultCourses()[$courseKey]), 404);
        foreach ($this->marksheets($courseKey) as $m) {
            if ($m['code'] === $otCode) {
                return $m;
            }
        }
        abort(404);
    }

    /** ?scope=archived → archived courses, anything else → active ones. */
    protected function scopedCourses(string $scope): array
    {
        return array_filter($this->resultCourses(), fn ($c) => $c['archived'] === ($scope === 'archived'));
    }
}
